<?php

namespace AppBundle\Controller;

use AppBundle\Form\IdentityVerificationType;
use ClientBundle\Service\DocumentService;
use ClientBundle\Service\UserService;
use ClientBundle\Service\VerificationService;
use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route(path: '/verifications')]
class VerificationController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $router,
        private SluggerInterface $slugger,
        private VerificationService $verificationService,
        private DocumentService $documentService,
        private UserService $userService,
    ) {
        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            header('Location: ' . $this->router->generate('login'));
            exit;
        }
    }

    #[Route(path: '', name: 'verification_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $this->logger->info("IN verification index");

        $this->userService->refreshUserInfo();
        $identityVerification = $this->verificationService->needsIdentityVerification();

        if (!$identityVerification) {
            return $this->redirectToRoute('homepage');
        }

        // $form = $this->createFormBuilder(null, ['csrf_protection' => false])
        //     ->add('submit', SubmitType::class, ['label' => 'Start Verification'])
        //     ->getForm();
        // $form->handleRequest($request);
        // if ($form->isSubmitted() && $form->isValid()) {
        //     $this->userService->refreshUserInfo();
        //     $obp = $this->onboardingService->getOnboardingProfileFromSession();
        //     return $this->redirectToRoute(
        //         $this->onboardingService->getNextStep($obp),
        //         [],
        //         Response::HTTP_SEE_OTHER
        //     );
        // }
        return $this->render('@AppBundle/Verifications/index.html.twig', [
            'identityVerification' => $identityVerification,
        ]);
    }

    #[Route(path: '/identity', name: 'verification_identity', methods: ['GET', 'POST'])]
    public function identity(Request $request): Response
    {
        $this->logger->info("IN verification identity");

        $openKycReviews = $this->verificationService->getOpenKycReviewsFromSession();
        $identityReview = $this->verificationService->getIdentityKycReview($openKycReviews);
        if (is_null($identityReview)) {
            $this->logger->debug("No verification needed");
            return $this->redirectToRoute('homepage');
        }

        $userInfo = $this->requestStack->getSession()->get('userInfo');
        $detailsToConfirm = [
            'givenNames' => implode(' ', [$userInfo['given_name'], $userInfo['additional_name']]),
            'lastName' => $userInfo['family_name'],
            'nationality' => $userInfo['nationality'],
            'dateOfBirth' => new DateTime($userInfo['birth_date'] ?? 'now'),
        ];

        $form = $this->createForm(IdentityVerificationType::class, $detailsToConfirm);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                /** @var UploadedFile $identityDocument */
                $identityDocument = $form->get('identityDocument')->getData();
                $originalFilename = pathinfo($identityDocument->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $this->slugger->slug($originalFilename);
                $fileType = $identityDocument->guessExtension();
                if (in_array(strtolower($fileType), ["pdf", "jpeg", "jpg", "png"])) {
                    $fileName = $safeFilename . '_' . time() . '.' . $fileType;
                    $byteArray = file_get_contents($identityDocument);
                    $fileData = [
                        'file_name' => $fileName,
                        'file_type' => $identityDocument->getMimeType(),
                        'document_content' => base64_encode($byteArray),
                        'tag' => 'proof_of_identity',

                    ];
                    // Submit document to CMS
                    $this->documentService->create($fileData);
                    // Create and submit Mangopay KYC document
                    $checkMangopayUser = $this->userService->checkMangopayKYC();
                    $this->handleApiResponse($checkMangopayUser);
                    // Mark KYC review as ready
                    $this->verificationService->markKycReviewAsReady($identityReview);
                } else {
                    throw new \Exception("You have uploaded an unsupported file type for your identity document ({$fileType}). We currently only accept the following file types: JPG, JPEG, PNG, PDF");
                }
                return $this->redirectToRoute(
                    'verification_complete',
                    [],
                    Response::HTTP_SEE_OTHER,
                );
            } catch (\Throwable $th) {
                $this->logger->error($th->getMessage());
                return $this->redirectToRoute(
                    'verification_error',
                    [],
                    Response::HTTP_SEE_OTHER,
                );
            }
        }
        return $this->render('@AppBundle/Verifications/identity.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/complete', name: 'verification_complete', methods: ['GET'])]
    public function complete(): Response
    {
        $this->logger->info("IN verification complete");
        return $this->render('@AppBundle/Verifications/completion.html.twig');
    }

    #[Route(path: '/error', name: 'verification_error', methods: ['GET'])]
    public function error(): Response
    {
        $this->logger->info("IN verification error");
        return $this->render('@AppBundle/Verifications/error.html.twig');
    }

    /**
     * Helper for APIv1 request responses
     */
    private function handleApiResponse($apiResponse)
    {
        //Get the user details
        $userData = $this->requestStack->getSession()->get('userInfo');

        if (empty($apiResponse['outcome'])) {
            //$this->requestStack->getSession()->getFlashBag()->add('errors', "No valid response received from API");
            $this->logger->error("FAILED API response for user=[" . $userData['email'] . " ], No valid response received from API - [" . json_encode($apiResponse) . "]");

            throw new \Exception("No valid response received from API");
        }

        if ($apiResponse['outcome'] == 'fail') {
            //$this->requestStack->getSession()->getFlashBag()->add('errors', $apiResponse['data']['user_message']);
            $this->logger->error("FAILED API response for user=[" . $userData['email'] . " ], [" . json_encode($apiResponse) . "]");
            //return $this->render('@AppBundle/Onboarding/generic-failure.html.twig', array('response_error' => $contegoRes['data']['user_message']));

            throw new \Exception($apiResponse['data']['user_message']);
        } elseif ($apiResponse['outcome'] == 'success') {
            $this->logger->debug("SUCCESS API response for user=[" . $userData['email'] . "], [" . json_encode($apiResponse) . "]");
        } else {
            //Somethimg unexpected happened
            //$this->requestStack->getSession()->getFlashBag()->add('errors', "No valid response received from API");
            $this->logger->error("FAILED API response for user=[" . $userData['email'] . " ], No valid response received from API - [" . json_encode($apiResponse) . "]");

            throw new \Exception("No valid response received from API");
        }
    }
}
