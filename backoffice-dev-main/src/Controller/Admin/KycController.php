<?php

namespace App\Controller\Admin;

use App\Entity\ContegoLog;
use App\Entity\Enum\KycReviewStatus;
use App\Entity\Enum\KycReviewType;
use App\Entity\KycReview;
use App\Entity\Lifecycle\UserLifecycle;
use App\Entity\User;
use App\Entity\UserDocument;
use App\Form\KycDynamicReviewType;
use App\Form\KycReviewFormType;
use App\Form\KycReviewPresetFormType;
use App\Form\Type\KycOnboardingReviewType;
use App\Form\Type\KycVipReviewType;
use App\Form\Type\QueryKycReviewType;
use App\Form\Type\QueryMangopayKycDocType;
use App\Form\Type\QueryUserType;
use App\Repository\ContegoLogRepository;
use App\Repository\KycReportRepository;
use App\Repository\KycReviewRepository;
use App\Repository\UserRepository;
use App\Service\KycReviewService;
use App\Service\MailerService;
use App\Service\Manager\UserManager;
use App\Service\Manager\UserManagerV2;
use App\Service\MangopayKycService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/kyc')]
#[IsGranted('ROLE_ANALYST')]
class KycController extends AbstractController
{
    public const PENDING_REVIEW_FILTERS = [
        'ob_step' => 5,
        'hasKycProfile' => 1,
        'hasVerifiedBy' => 0,
        'lifecycleStatus' => [
            UserLifecycle::STATE_EMAIL_VERIFIED,
            UserLifecycle::STATE_REGISTRATION_COMPLETE,
            UserLifecycle::STATE_APPROVED,
        ],
    ];
    public const FAILED_REVIEW_FILTERS = [
        'ob_step' => 5,
        'hasKycProfile' => 1,
        'hasVerifiedBy' => 1,
        'verified' => 0,
        'lifecycleStatus' => [
            UserLifecycle::STATE_EMAIL_VERIFIED,
            UserLifecycle::STATE_REGISTRATION_COMPLETE,
            UserLifecycle::STATE_APPROVED,
        ],
    ];
    public const MISSING_KYC_PROFILE_FILTERS = [
        'ob_step' => 5,
        'hasKycProfile' => 0,
        'lifecycleStatus' => [
            UserLifecycle::STATE_EMAIL_VERIFIED,
            UserLifecycle::STATE_REGISTRATION_COMPLETE,
            UserLifecycle::STATE_APPROVED,
        ],
    ];
    public const VIP_CURRENT_VILTERS = [
        'isVIP' => 1,
    ];
    public const VIP_APPLICANTS_VILTERS = [
        'isVIP' => 0,
        'wordsOfOwn' => 1,
    ];

    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private UserRepository $userRepository,
        private KycReportRepository $kycReportRepository,
        private KycReviewRepository $kycReviewRepository,
        private ContegoLogRepository $contegoLogRepository,
        private MangopayKycService $mangopayKycService,
        private KycReviewService $kycReviewService,
        private UserManagerV2 $userManager,
        private UserManager $userManagerLegacy,
        private MailerService $mailerService,
    ) {}

    #[Route(path: '', name: 'admin_kyc_index', methods: ['GET'])]
    public function index()
    {
        $usersToManuallyReview = $this->userRepository
            ->buildQueryWithAssociations(self::PENDING_REVIEW_FILTERS, ['id' => 'DESC'])
            ->getResult();
        $usersWithoutKycProfile = $this->userRepository
            ->buildQueryWithAssociations(self::MISSING_KYC_PROFILE_FILTERS, [
                'id' => 'DESC',
            ])
            ->getResult();
        $usersMissingStatusTags = $this->userRepository
            ->buildQueryWithAssociations([
                'ob_step' => 5,
                'lifecycleStatus' => [
                    UserLifecycle::STATE_EMAIL_VERIFIED,
                ],
            ], ['id' => 'DESC'])
            ->getResult();
        $topYielderApplications = $this->userRepository
            ->buildQueryWithAssociations(['isVIP' => 0, 'wordsOfOwn' => 1], [
                'id' => 'DESC',
            ])
            ->getResult();
        $recentKycReviews = $this->kycReviewRepository->findBy(
            [
                'status' => KycReviewStatus::Completed,
            ],
            ['completedAt' => 'DESC'],
            2,
        );
        $recentKycReports = $this->kycReportRepository->findBy(
            [],
            ['checkedAt' => 'DESC'],
            2,
        );
        return $this->render('admin/pages/kyc/index.html.twig', [
            'usersToManuallyReview' => $usersToManuallyReview,
            'usersWithoutKycProfile' => $usersWithoutKycProfile,
            'usersMissingStatusTags' => $usersMissingStatusTags,
            'topYielderApplications' => $topYielderApplications,
            'recentKycReviews' => $recentKycReviews,
            'recentKycReports' => $recentKycReports,
        ]);
    }

    #[Route(path: '/onboarding', name: 'admin_kyc_onboarding', methods: ['GET'])]
    public function onboarding(Request $request)
    {
        // $this->logger->debug('KYC dashboard for onboarding');
        $filters = self::PENDING_REVIEW_FILTERS;
        $form = $this->createForm(QueryUserType::class, $filters);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->userRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/kyc/onboarding/index.html.twig', [
            'objects' => $results,
            'form' => $form,
        ]);
    }

    #[Route(
        '/onboarding/{id}',
        name: 'admin_kyc_onboarding_review',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function onboardingReview(Request $request, User $user): Response
    {
        // $this->logger->debug('User KYC review for onboarding');

        // Adopt KYC Reports when they are ready and fully back ported
        $userContegoLogs = $this->contegoLogRepository->findBy(['user' => $user->getUserIdentifier()], [
            'createdAt' => 'DESC',
        ]);
        $userContegoLogs = array_filter(
            $userContegoLogs,
            fn(ContegoLog $log) => $log->getRAG() != 'WAITING',
        );

        $form = $this->createForm(KycOnboardingReviewType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $kycProfile = $user->getKycProfile();
            $kycProfile->setVerifiedBy($this->getUser());
            $kycProfile->setLastReviewedAt(new \DateTime());
            $kycProfile->setDueDiligenceLevel($form->getData()['dueDiligenceLevel']);

            $kycReview = $this->kycReviewService->createKycReview(
                KycReviewType::Onboarding,
                $user,
                $this->getUser(),
                $form->getData()['notes'],
            );

            /** @var ClickableInterface $passButton */
            $passButton = $form->get('pass');
            if ($passButton->isClicked()) {
                $kycReview->setDecision(true);
                $kycProfile->setVerified(true);
                if (!$user->getStatus()->getIsRegistrationComplete()) {
                    $user->getStatus()->setLifecycleStatus(UserLifecycle::STATE_REGISTRATION_COMPLETE);
                    $this->mailerService->sendMail(
                        $user,
                        MailerService::TYPE_OB_COMPLETE,
                        ['user' => $user],
                    );
                }
                if (!$user->getStatus()->getIsApproved()) {
                    $user->getStatus()->setLifecycleStatus(UserLifecycle::STATE_APPROVED);
                }
            }
            /** @var ClickableInterface $failButton */
            $failButton = $form->get('fail');
            if ($failButton->isClicked()) {
                $kycReview->setDecision(false);
                $kycProfile->setVerified(false);
            }

            $this->addFlash(
                'success',
                'User successfully updated to kyc '
                . ($kycProfile->isVerified() ? 'verified' : 'failed'),
            );
            $this->doctrine->getManager()->persist($kycReview);
            $this->doctrine->getManager()->flush();
            $this->handleSalesforceSync($user);

            return $this->redirectToRoute('admin_kyc_onboarding');
        }
        return $this->render('admin/pages/kyc/onboarding/review.html.twig', [
            'user' => $user,
            'kycState' => $this->userManager->getKycState($user),
            'contegoLogs' => $userContegoLogs,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/vip', name: 'admin_kyc_vip', methods: ['GET'])]
    public function vip(Request $request): Response
    {
        // $this->logger->debug('KYC dashboard for VIPs (top yielders)');
        $filters = self::VIP_APPLICANTS_VILTERS;
        $form = $this->createForm(QueryUserType::class, $filters);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }
        $results = $this->userRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        $results = $this->userRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/kyc/vip/index.html.twig', [
            'objects' => $results,
            'form' => $form,
        ]);
    }

    #[Route(path: '/vip/{id}', name: 'admin_kyc_vip_review', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_OPERATIONS')]
    public function vipReview(Request $request, User $user): Response
    {
        // $this->logger->debug('User KYC review for VIPs (top yielders)');

        $form = $this->createForm(KycVipReviewType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $kycReview = $this->kycReviewService->createKycReview(
                KycReviewType::Vip,
                $user,
                $this->getUser(),
                $form->getData()['notes'],
            );

            $currentVipStatus = (bool) $user->getisVIP();
            /** @var ClickableInterface $passButton */
            $passButton = $form->get('pass');
            if ($passButton->isClicked()) {
                $kycReview->setDecision(true);
                $user->setisVIP((int) true);
                if (!$currentVipStatus) {
                    $this->mailerService->sendMail(
                        $user,
                        MailerService::TYPE_VIP_CONFIRMATION,
                        [
                            'user' => $user,
                        ],
                    );
                }
            }
            /** @var ClickableInterface $failButton */
            $failButton = $form->get('fail');
            if ($failButton->isClicked()) {
                $kycReview->setDecision(false);
                $user->setisVIP((int) false);
            }
            $this->doctrine->getManager()->persist($kycReview);
            $this->doctrine->getManager()->flush();
            $this->handleSalesforceSync($user);

            $this->addFlash(
                'success',
                'User VIP status successfully updated to '
                . ($user->getisVIP() ? 'VIP' : 'Non-VIP'),
            );
            return $this->redirectToRoute('admin_kyc_vip');
        }
        return $this->render('admin/pages/kyc/vip/review.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/recurring', name: 'admin_kyc_recurring', methods: ['GET'])]
    public function recurring(Request $request): Response
    {
        $this->logger->debug('KYC dashboard for recurring');
        $filters = [
            'reviewType' => KycReviewType::Recurring,
            'status' => [
                KycReviewStatus::Open,
                KycReviewStatus::PendingSubjectAction,
                KycReviewStatus::Ready,
            ],
        ];
        $filters = array_merge($filters, [
            'perPage' => 10,
            'page' => $request->query->get('page', 1),
        ]);
        $results = $this->kycReviewRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/kyc/recurring/index.html.twig', [
            'pendingReviews' => $results,
        ]);
    }

    #[Route(
        path: '/recurring/quick-create',
        name: 'admin_kyc_recurring_quick_create',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function recurringQuickCreate(Request $request): Response
    {
        $this->logger->debug('Create a custom recurring KYC review with a preset');

        $kycReview = new KycReview(KycReviewType::Recurring, $this->getUser());
        $form = $this->createForm(KycReviewPresetFormType::class, $kycReview);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $preset = $form->get('preset')->getData();
            $kycReview = $this->kycReviewService->applyReviewPreset(
                $kycReview,
                $preset,
            );
            $similar = [];
            if (!$form->get('skipDuplicateCheck')->getData()) {
                $similar = $this->kycReviewRepository->findOpenReviews(
                    $kycReview->getSubject(),
                    KycReviewType::Recurring,
                    KycReviewService::KYC_REVIEW_PRESETS[$preset]['actions'],
                );
            }
            if (empty($similar)) {
                $this->doctrine->getManager()->persist($kycReview);
                $this->doctrine->getManager()->flush();
                $this->addFlash(
                    'success',
                    'Successfully created new recurring KYC review',
                );
                return $this->redirectToRoute('admin_kyc_recurring_review', ['id' => $kycReview->getId()]);
            }
            $this->addFlash(
                'warning',
                'Failed to create new recurring KYC review. Similar recurring KYC review(s) already exist with IDs: '
                . json_encode(array_map(
                    fn(KycReview $kr): ?int => $kr->getId(),
                    $similar,
                ))
                . '. Use "Skip Duplicate Check" to bypass this restriction.',
            );
        }

        return $this->render('admin/pages/kyc/recurring/quick_create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(
        path: '/recurring/{id}',
        name: 'admin_kyc_recurring_review',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function recurringReview(Request $request, KycReview $kycReview): Response
    {
        $this->logger->debug('User KYC review for recurring');

        $user = $kycReview->getSubject();
        // Adopt KYC Reports when they are ready and fully back ported
        $userContegoLogs = $this->contegoLogRepository->findBy(['user' => $user->getUserIdentifier()], [
            'createdAt' => 'DESC',
        ]);
        $userContegoLogs = array_filter(
            $userContegoLogs,
            fn(ContegoLog $log) => $log->getRAG() != 'WAITING',
        );

        $form = $this->createForm(
            KycDynamicReviewType::class,
            ['notes' => $kycReview->getNotes()],
            ['kyc_review' => $kycReview],
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $kycProfile = $user->getKycProfile();
            $kycProfile->setVerifiedBy($this->getUser());
            $kycProfile->setLastReviewedAt(new \DateTime());

            $kycReview->setReviewedBy($this->getUser());
            $kycReview->setNotes($form->getData()['notes']);

            /** @var ClickableInterface $passButton */
            $passButton = $form->get('pass');
            if ($passButton->isClicked()) {
                $kycReview->setDecision(true);
                $kycReview->setStatus(KycReviewStatus::Completed);
                $kycReview->setCompletedAt(new \DateTime());
                $kycProfile->setVerified(true);
            }
            /** @var ClickableInterface $failButton */
            $failButton = $form->get('fail');
            if ($failButton->isClicked()) {
                $kycReview->setDecision(false);
                $kycReview->setStatus(KycReviewStatus::Completed);
                $kycReview->setCompletedAt(new \DateTime());
                $kycProfile->setVerified(false);
            }

            $this->addFlash(
                'success',
                'User successfully updated to kyc '
                . ($kycProfile->isVerified() ? 'verified' : 'failed'),
            );
            $this->doctrine->getManager()->persist($kycReview);
            $this->doctrine->getManager()->flush();
            $this->handleSalesforceSync($user);

            return $this->redirectToRoute('admin_kyc_recurring');
        }
        return $this->render('admin/pages/kyc/recurring/review.html.twig', [
            'user' => $user,
            'kycReview' => $kycReview,
            'kycState' => $this->userManager->getKycState($user),
            'contegoLogs' => $userContegoLogs,
            'form' => $form,
            'canNotify' => $this->kycReviewService->canSendNotification($kycReview),
        ]);
    }

    #[Route(path: '/reviews', name: 'admin_kyc_review_index', methods: ['GET'])]
    public function reviewsIndex(Request $request): Response
    {
        // $this->logger->debug('KYC dashboard for recurring');
        $form = $this->createForm(QueryKycReviewType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();

            // $this->logger->debug('filters', $filters);
        }
        $results = $this->kycReviewRepository->findByWithAssociations(
            $filters ?? [],
            [$filters['orderBy'] ?? 'id' => $filters['orderDirection'] ?? 'DESC'],
            $filters['perPage'] ?? 10,
            $filters['page'] ?? 1,
        );
        return $this->render('admin/pages/kyc/reviews/index.html.twig', [
            'objects' => $results,
            'form' => $form,
        ]);
    }

    #[Route(
        path: '/reviews/create',
        name: 'admin_kyc_review_create',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function reviewsCreate(Request $request): Response
    {
        $this->logger->debug('Create new KYC review');
        $preset = $request->query->get('preset', null);
        $kycReview = new KycReview(KycReviewType::Adhoc, $this->getUser());
        // Only apply preset if not submitting the form to prevent overriding customisations
        if ($preset && $request->isMethod('GET')) {
            $kycReview = $this->kycReviewService->applyReviewPreset(
                $kycReview,
                $preset,
            );
        }
        $form = $this->createForm(KycReviewFormType::class, $kycReview);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->persist($kycReview);
            $this->doctrine->getManager()->flush();

            $this->addFlash('success', 'Successfully created new KYC review');
            return $this->redirectToRoute('admin_kyc_review_view', ['id' => $kycReview->getId()]);
        }

        return $this->render('admin/pages/kyc/reviews/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/reviews/{id}', name: 'admin_kyc_review_view', methods: ['GET'])]
    public function reviewsView(Request $request, KycReview $kycReview): Response
    {
        if ($request->query->get('mode') == 'review') {
            $route = match ($kycReview->getReviewType()) {
                KycReviewType::Onboarding => 'admin_kyc_onboarding_review',
                KycReviewType::Vip => 'admin_kyc_vip_review',
                KycReviewType::Recurring => 'admin_kyc_recurring_review',
                default => 'admin_kyc_review_view',
            };
            return $this->redirectToRoute($route, ['id' => $kycReview->getId()]);
        }
        return $this->render('admin/pages/kyc/reviews/view.html.twig', [
            'kycReview' => $kycReview,
        ]);
    }

    #[Route(
        '/reviews/{id}/edit',
        name: 'admin_kyc_review_edit',
        methods: ['GET', 'POST'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function reviewsEdit(Request $request, KycReview $kycReview): Response
    {
        $redirectToRoute = 'admin_kyc_review_view';
        if (in_array(
            $request->query->get('redirectRoute'),
            [
                'admin_kyc_recurring_review',
            ],
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
        }
        $form = $this->createForm(KycReviewFormType::class, $kycReview);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->persist($kycReview);
            $this->doctrine->getManager()->flush();

            $this->addFlash('success', 'Successfully updated KYC review');
            return $this->redirectToRoute($redirectToRoute, ['id' => $kycReview->getId()]);
        }

        return $this->render('admin/pages/kyc/reviews/edit.html.twig', [
            'kycReview' => $kycReview,
            'form' => $form,
        ]);
    }

    #[Route(
        '/reviews/{id}/notifications',
        name: 'admin_kyc_review_notify',
        methods: ['GET', 'POST'],
    )]
    public function reviewNotify(Request $request, KycReview $kycReview): Response
    {
        $redirectToRoute = 'admin_kyc_review_edit';
        if (in_array(
            $request->query->get('redirectRoute'),
            [
                'admin_kyc_recurring_review',
            ],
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
        }
        if (!$this->kycReviewService->canSendNotification($kycReview)) {
            $this->addFlash(
                'warning',
                'No notifications available for this KYC review',
            );
            return $this->redirectToRoute($redirectToRoute, ['id' => $kycReview->getId()]);
        }
        $form = $this
            ->createFormBuilder()
            ->add('submit', SubmitType::class, [
                'label' => 'Send Notification',
            ])
            ->getForm();
        ;
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->kycReviewService->sendIdConfirmationNotification($kycReview);
            $this->addFlash(
                'success',
                'Successfully sent notification email to subject',
            );
            return $this->redirectToRoute($redirectToRoute, ['id' => $kycReview->getId()]);
        }
        return $this->render('admin/pages/kyc/reviews/notify.html.twig', [
            'kycReview' => $kycReview,
            'redirectRoute' => $redirectToRoute,
            'form' => $form,
        ]);
    }

    private function handleSalesforceSync(User $user): void
    {
        $response = $this->userManagerLegacy->syncWithSalesforce($user);
        $this->addFlash($response['type'], $response['message']);
    }
}
