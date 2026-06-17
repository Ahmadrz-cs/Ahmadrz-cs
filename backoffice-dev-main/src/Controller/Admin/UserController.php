<?php

namespace App\Controller\Admin;

use App\Entity\CUST_FIELDS_CONSTANT;
use App\Entity\Lifecycle\UserLifecycle as UserLifecycle;
use App\Entity\OB_STEP_CONSTANT;
use App\Entity\User;
use App\Entity\UserCustomFields;
use App\Entity\UserLog;
use App\Entity\UserStatusLog;
use App\Form\Type\QueryUserType;
use App\Form\Type\UsernameType;
use App\Form\Type\UserType;
use App\Form\UserStatusLogType;
use App\Repository\UserRepository;
use App\Service\AccountClosureService;
use App\Service\MailerService;
use App\Service\Manager\UserManager;
use App\Service\Manager\UserManagerV2;
use App\Service\MangoPay;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/users')]
class UserController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ManagerRegistry $doctrine,
        private UserManagerV2 $userManager,
        private UserManager $userManagerLegacy,
        private UserRepository $userRepository,
        private MailerService $mailerService,
    ) {}

    #[Route(path: '', name: 'admin_user_index')]
    #[Route(path: '/list', name: 'admin_user_list')]
    public function list(Request $request): Response
    {
        $this->logger->info('List users');
        $form = $this->createForm(QueryUserType::class);
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
        return $this->render('admin/pages/users/list.html.twig', [
            'objects' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'admin_user_edit')]
    public function editAction(Request $request, ?User $user = null): Response
    {
        $readOnly = false;
        if (!$this->isGranted('CAN_EDIT_USER', $user)) {
            $readOnly = true;
        }

        $currentVipStatus = false;
        if ($user->getisVIP()) {
            $currentVipStatus = true;
        }

        $form = $this->createForm(UserType::class, $user, ['read_only' => $readOnly]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($form->get('customFields')->getData() as $customField) {
                if (!$customField->getUser()) {
                    $customField->setUser($form->getData());
                }
            }

            foreach ($form->get('addresses')->getData() as $customField) {
                if (!$customField->getUser()) {
                    $customField->setUser($form->getData());
                }
            }

            $newVipStatus = $form->get('isVIP')->getData();
            if ($newVipStatus and !$currentVipStatus) {
                $this->mailerService->sendMail(
                    $user,
                    MailerService::TYPE_VIP_CONFIRMATION,
                    [
                        'user' => $user,
                    ],
                );
            }

            $this->doctrine->getManager()->flush();
            $this->handleSalesforceSync($user);

            return $this->redirectToRoute('admin_user_index');
        }
        return $this->render('admin/pages/users/edit.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}/managed-users', name: 'admin_user_managed_users')]
    public function managedUsersAction(User $user): Response
    {
        $users = $user->getManagedUsers();

        return $this->render('admin/pages/users/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route(path: '/managers', name: 'admin_user_managers')]
    public function userManagers(): Response
    {
        $users = $this->userRepository->findManagers();

        return $this->render('admin/pages/users/managers.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route(
        path: '/{id}/toggle-company-approved',
        name: 'admin_user_toggle_company_approved',
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function toggleCompanyApproved(User $user): Response
    {
        if ($this->userManager->setCompanyApproved($user)) {
            $this->addFlash(
                'success',
                $user->getUserIdentifier()
                    . ' is now an approved company/institutional investor',
            );
        } else {
            $this->addFlash(
                'success',
                $user->getUserIdentifier()
                    . ' is no longer an approved company/institutional investor',
            );
        }
        return $this->redirectToRoute('admin_user_index');
    }

    #[Route(path: '/{id}/user_vip', name: 'admin_user_vip')]
    public function vipAction(Request $request, User $user): Response
    {
        $this->logger->info('==========in vipAction==========');

        if ($user->getisVIP()) {
            $user->setisVIP((int) false);
        } else {
            $user->setisVIP((int) true);
        }

        try {
            $this->doctrine->getManager()->flush();
        } catch (\Exception $e) {
            $this->logger->error('VIP update failed: ' . $e->getMessage());
        }

        if ($user->getisVIP()) {
            $this->mailerService->sendMail(
                $user,
                MailerService::TYPE_VIP_CONFIRMATION,
                [
                    'user' => $user,
                ],
            );
        }

        $this->handleSalesforceSync($user);

        return $this->redirectToRoute('admin_user_index');
    }

    #[Route(path: '/{id}/toggle-kyc-verified', name: 'admin_user_toggle_kyc_verified')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function toggleManualKycVerification(Request $request, User $user): Response
    {
        $kycProfile = $user->getKycProfile();
        $kycProfile->setVerified(!$kycProfile->isVerified());
        $kycProfile->setVerifiedBy($this->getUser());
        $kycProfile->setLastReviewedAt(new \DateTime());
        $this->addFlash(
            'success',
            'User successfully updated to kyc '
            . ($kycProfile->isVerified() ? 'verified' : 'failed'),
        );

        $this->doctrine->getManager()->flush();
        if (in_array(
            $request->query->get('redirectRoute'),
            UserDashboardController::REDIRECT_ROUTES,
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
            return $this->redirectToRoute(
                $redirectToRoute ?? 'admin_user_index',
                ['id' => $user->getId()],
                Response::HTTP_SEE_OTHER,
            );
        }
        return $this->redirectToRoute('admin_user_index', ['id' => $user->getId()]);
    }

    #[Route(path: '/{id}/user_approve', name: 'admin_user_approve')]
    public function approveAction(Request $request, User $user): Response
    {
        if ($user->getStatus()->getIsApproved()) {
            $user->getStatus()->setIsApproved(false);
        } else {
            $user->getStatus()->setLifecycleStatus(UserLifecycle::STATE_APPROVED);
        }

        $this->doctrine->getManager()->flush();
        $this->handleSalesforceSync($user);

        return $this->redirectToRoute('admin_user_index');
    }

    #[Route(path: '/{id}/user_registercomplete', name: 'admin_user_registercomplete')]
    public function registercompleteAction(Request $request, User $user): Response
    {
        if ($user->getStatus()->getIsRegCompleted()) {
            $user->getStatus()->setIsRegCompleted(false);
        } else {
            $user->getStatus()->setLifecycleStatus(UserLifecycle::STATE_REGISTRATION_COMPLETE);

            $email = $this->mailerService->sendMail(
                $user,
                MailerService::TYPE_OB_COMPLETE,
                ['user' => $user],
            );

            $email = $this->mailerService->adminMailEntry(
                $user,
                MailerService::TYPE_OB_COMPLETE_ADMIN,
                ['user' => $user, null],
                null,
            );

            /** @var User $loggedInUser */
            $loggedInUser = $this->getUser();
            $this->logger->info(
                'User : '
                    . $user->getEmail()
                    . ': Was manually moved Registration Complete'
                    . $loggedInUser->getEmail(),
            );
        }

        $this->doctrine->getManager()->flush();
        $this->handleSalesforceSync($user);

        return $this->redirectToRoute('admin_user_index');
    }

    #[Route(path: '/{id}/user_block', name: 'admin_user_block')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function blockAction(
        Request $request,
        User $user,
        AccountClosureService $accountClosureService,
    ): Response {
        $accountClosureService->toggleAccountBlock($user);
        $this->doctrine->getManager()->flush();
        $this->handleSalesforceSync($user);

        return $this->redirectToRoute('admin_user_index');
    }

    /**
     * Reset the questionanire status
     */
    #[Route(path: '/{id}/user_questionreset', name: 'admin_user_questionreset')]
    public function questionresetAction(Request $request, User $user): Response
    {
        if ($user->findCustomFieldValue(CUST_FIELDS_CONSTANT::CF_Q_ATTEMPS) == 2) {
            //user has failed 2 so reset the count so he can try again
            $field = new UserCustomFields();
            $field->setFieldKey(CUST_FIELDS_CONSTANT::CF_Q_ATTEMPS);
            $field->setFieldValue(0);
            $user->findReplaceCustomField($field);
            $user->setOBStep(OB_STEP_CONSTANT::STEP4_INT);

            $this->doctrine->getManager()->flush();

            $email = $this->mailerService->sendMail(
                $user,
                MailerService::TYPE_OB_QUESTIONNAIRE_RESET,
                [
                    'user' => $user,
                ],
            );

            /** @var User $loggedInUser */
            $loggedInUser = $this->getUser();
            $this->logger->info(
                'User : '
                    . $user->getEmail()
                    . ': Questionnaire retry state was reset and moved to Compliance step:'
                    . $loggedInUser->getEmail(),
            );
        }

        return $this->redirectToRoute('admin_user_index');
    }

    #[Route(
        path: '/{id}/wallets/create-all',
        name: 'admin_user_wallets_create_all',
        methods: ['GET'],
    )]
    #[IsGranted('ROLE_OPERATIONS')]
    public function createUserWallets(
        Request $request,
        #[MapEntity(id: 'id')] User $user,
        MangoPay $mangopayService,
    ): Response {
        $this->logger->debug('Create user wallets');

        try {
            $mangoPayUserWallet = $mangopayService->createUserWallet($user);

            $user->setMangoPayWalletId($mangoPayUserWallet->Id);
            $this->doctrine->getManager()->flush();
            $this->logger->debug("MangoPay wallet created for user {$user->getId()}");
            $this->addFlash('success', 'Wallet(s) successfully setup.');
        } catch (\Exception $e) {
            $this->logger->error(
                'Could not create wallet(s) for user id: '
                    . $user->getId()
                    . ' Error details: '
                    . $e->getMessage(),
            );
            $this->addFlash('error', 'Wallet(s) could be created.' . $e->getMessage());
        }
        return $this->redirectToRoute('admin_user_dashboard_overview', ['id' => $user->getId()]);
    }

    #[Route(path: '/{id}/status-logs/create', name: 'admin_user_status_log_create')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function createStateLog(Request $request, User $user): Response
    {
        $this->logger->debug('Create new user status log');
        $redirectToRoute = 'admin_user_edit';
        $redirectToId = $user->getId();
        if (in_array(
            $request->query->get('redirectRoute'),
            [
                'admin_user_dashboard_status_logs',
            ],
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
            $redirectToId = $request->query->get('redirectId', $redirectToId);
        }

        $userStatusLog = new UserStatusLog();
        $userStatusLog->setTransitionedBy($this->getUser());
        $form = $this->createForm(UserStatusLogType::class, $userStatusLog);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->addStatusLog($userStatusLog);
            $this->doctrine->getManager()->persist($userStatusLog);
            $this->doctrine->getManager()->flush();
            $this->addFlash('success', 'Successfully created new user status log');
            return $this->redirectToRoute($redirectToRoute, ['id' => $redirectToId]);
        }
        return $this->render('admin/pages/users/status_logs/create.html.twig', [
            'user' => $user,
            'userStatusLog' => $userStatusLog,
            'form' => $form,
            'redirectRoute' => $redirectToRoute,
            'redirectToId' => $redirectToId,
        ]);
    }

    #[Route(path: '/status-logs/{id}', name: 'admin_user_status_log_edit')]
    #[IsGranted('ROLE_OPERATIONS')]
    public function editStateLog(
        Request $request,
        UserStatusLog $userStatusLog,
    ): Response {
        $this->logger->debug('Edit new user status log');
        $redirectToRoute = 'admin_user_edit';
        $user = $userStatusLog->getUser();
        $redirectToId = $user->getId();
        if (in_array(
            $request->query->get('redirectRoute'),
            [
                'admin_user_dashboard_status_logs',
            ],
        )) {
            $redirectToRoute = $request->query->get('redirectRoute');
        }

        $form = $this->createForm(UserStatusLogType::class, $userStatusLog);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->doctrine->getManager()->flush();
            $this->addFlash('success', 'Successfully updated user status log');
            return $this->redirectToRoute($redirectToRoute, ['id' => $redirectToId]);
        }
        return $this->render('admin/pages/users/status_logs/edit.html.twig', [
            'user' => $user,
            'userStatusLog' => $userStatusLog,
            'form' => $form,
            'redirectRoute' => $redirectToRoute,
            'redirectToId' => $redirectToId,
        ]);
    }

    private function handleSalesforceSync(User $user): void
    {
        $response = $this->userManagerLegacy->syncWithSalesforce($user);
        $this->addFlash($response['type'], $response['message']);
    }
}
