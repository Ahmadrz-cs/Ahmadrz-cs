<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Enum\UserCategory;
use AppBundle\Entity\OnboardingProfile;
use AppBundle\Entity\UserCategorisation;
use AppBundle\Form\CategoryHnwType;
use AppBundle\Form\CategoryRestrictedType;
use AppBundle\Form\CategorySophisticatedType;
use AppBundle\Form\RiskAcceptanceType;
use AppBundle\Form\UserAssessmentType;
use AppBundle\Form\UserCategorisationType;
use ClientBundle\Service\OnboardingService;
use ClientBundle\Service\UserService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route(path: '/checkup')]
class CheckupController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private UrlGeneratorInterface $router,
        private OnboardingService $onboardingService,
        private UserService $userService,
    ) {
        $authenticated = $this->requestStack->getSession()->get('authenticated');
        if (!$authenticated) {
            header('Location: ' . $this->router->generate('login'));
            exit;
        }
    }

    #[Route(path: '', name: 'checkup_index', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        $this->logger->info("IN checkup index");

        $form = $this->createFormBuilder(null, ['csrf_protection' => false])
            ->add('submit', SubmitType::class, ['label' => 'Update Now'])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->userService->refreshUserInfo();
            $obp = $this->onboardingService->getOnboardingProfileFromSession();
            return $this->redirectToRoute(
                $this->onboardingService->getNextStep($obp),
                [],
                Response::HTTP_SEE_OTHER
            );
        }
        return $this->render('@AppBundle/Checkup/index.html.twig', [
            'form' => $form
        ]);
    }

    #[Route(path: '/risk', name: 'checkup_risk', methods: ['GET', 'POST'])]
    public function risk(Request $request): Response
    {
        $this->logger->info("IN checkup risk acknowledgement");
        $obp = $this->onboardingService->getOnboardingProfileFromSession();

        $form = $this->createForm(RiskAcceptanceType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var ClickableInterface $accept */
            $accept = $form->get('yes');
            /** @var ClickableInterface $reject */
            $reject = $form->get('no');
            $decision = null;
            if ($accept->isClicked()) {
                $decision = true;
            } elseif ($reject->isClicked()) {
                $decision = false;
            }
            $onboardingProfile = new OnboardingProfile();
            $onboardingProfile->cooloffAccepted = $decision;
            $onboardingProfile->riskWarningAccepted = $decision;
            try {
                $this->onboardingService->updateOnboardingProfile($onboardingProfile);
                $this->userService->refreshUserInfo();
            } catch (\Exception $e) {
                $this->logger->error("Update onboarding profile error", [$e->getMessage()]);
            }
            $obp = $this->onboardingService->getOnboardingProfileFromSession();
            // $this->logger->debug("Current user onboarding profile", [$obp]);
            if (!$decision) {
                return $this->redirectToRoute(
                    'homepage',
                    [],
                    Response::HTTP_SEE_OTHER
                );
            }
            return $this->redirectToRoute(
                $this->onboardingService->getNextStep($obp),
                [],
                Response::HTTP_SEE_OTHER
            );
        }
        return $this->render('@AppBundle/Checkup/risk.html.twig', [
            'form' => $form,
            'onboardingProfile' => $obp,
        ]);
    }

    #[Route(path: '/categorisation', name: 'checkup_categorisation', methods: ['GET', 'POST'])]
    public function categorisation(Request $request): Response
    {
        $this->logger->info("IN checkup categorisation");
        $userCategorisation = new UserCategorisation();
        $obp = $this->onboardingService->getOnboardingProfileFromSession();
        $userCategorisation->category = $obp->category ?? UserCategory::Restricted;
        if ($userCategorisation->category == UserCategory::None) {
            $userCategorisation->category = UserCategory::Restricted;
        }
        $form = $this->createForm(UserCategorisationType::class, $userCategorisation);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->debug("Category chosen", [$form->get('category')->getData()]);
            return $this->redirectToRoute('checkup_categorisation_confirm', [
                'category' => $userCategorisation->category->value
            ], Response::HTTP_SEE_OTHER);
        }
        return $this->render('@AppBundle/Checkup/categorisation.html.twig', [
            'form' => $form,
            'obp' => $obp,
            'currentCategory' => $obp->category,
        ]);
    }

    #[Route(path: '/categorisation/{category}', name: 'checkup_categorisation_confirm', methods: ['GET', 'POST'])]
    public function confirmCategorisation(Request $request, UserCategory $category): Response
    {
        $this->logger->info("IN checkup confirm categorisation");
        $userCategorisation = new UserCategorisation();
        $userCategorisation->category = $category;
        $formClass = match ($category) {
            UserCategory::Restricted => CategoryRestrictedType::class,
            UserCategory::Sophisticated => CategorySophisticatedType::class,
            UserCategory::HighNetWorth => CategoryHnwType::class,
            default => null,
        };
        $template = match ($category) {
            UserCategory::Restricted => 'categorisation_restricted',
            UserCategory::Sophisticated => 'categorisation_sophisticated',
            UserCategory::HighNetWorth => 'categorisation_hnw',
            default => null,
        };
        if (is_null($formClass)) {
            return $this->redirectToRoute('checkup_categorisation_confirm', [
                'category' => UserCategory::Restricted->value
            ], Response::HTTP_SEE_OTHER);
        }
        $form = $this->createForm($formClass);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $userCategorisation->details = $form->getData();
            try {
                $this->onboardingService->addUserCategorisation($userCategorisation);
                $this->userService->refreshUserInfo();
                $obp = $this->onboardingService->getOnboardingProfileFromSession();
                return $this->redirectToRoute(
                    $this->onboardingService->getNextStep($obp),
                    [],
                    Response::HTTP_SEE_OTHER
                );
            } catch (\Exception $e) {
                $this->logger->error("Issue adding user categorisation", [$e->getMessage()]);
            }
            $this->logger->debug("Category chosen", $userCategorisation->details);
        }
        return $this->render("@AppBundle/Checkup/{$template}.html.twig", [
            'form' => $form,
        ]);
    }

    #[Route(path: '/assessment', name: 'checkup_assessment', methods: ['GET'])]
    public function assessment(Request $request): Response
    {
        $this->logger->info("IN checkup assessment");
        return $this->render('@AppBundle/Checkup/assessment.html.twig');
    }

    #[Route(path: '/assessment/quiz', name: 'checkup_assessment_quiz', methods: ['GET', 'POST'])]
    public function assessmentQuiz(Request $request): Response
    {
        $this->logger->info("IN checkup assessment quiz");

        $obp = $this->onboardingService->getOnboardingProfileFromSession();
        if ($obp->assessmentPassed) {
            return $this->redirectToRoute(
                $this->onboardingService->getNextStep($obp),
                [],
                Response::HTTP_SEE_OTHER
            );
        }
        if (!$this->onboardingService->canTakeAssessment($obp)) {
            return $this->redirectToRoute('checkup_assessment_fail');
        }

        // $questionnaire = $this->onboardingService->getQuestionnaire();
        // $userAssessment = $this->onboardingService->prepareQuestionnaire($questionnaire);
        $userAssessment = $this->onboardingService->getCurrentUserAssessment();
        // $this->logger->debug("Questionnaire", [$userAssessment]);

        $form = $this->createForm(UserAssessmentType::class, null, ['responses' => $userAssessment->responses]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // $this->logger->debug("Questionnaire", [$userAssessment]);
            $userAssessment->responses = $form->getData();
            $userAssessment->complete = true;
            try {
                $response = $this->onboardingService->addUserAssessment($userAssessment);
                $this->userService->refreshUserInfo();
                $this->logger->info("Assessment result", $response);
                if ($response['passed']) {
                    $obp = $this->onboardingService->getOnboardingProfileFromSession();
                    return $this->redirectToRoute(
                        $this->onboardingService->getNextStep($obp),
                        [],
                        Response::HTTP_SEE_OTHER
                    );
                } else {
                    return $this->redirectToRoute('checkup_assessment_fail');
                }
            } catch (\Exception $e) {
                $this->logger->error("Issue adding user assessment", [$e->getMessage()]);
                $this->addFlash('error', 'Unable to process questionnaire');
            }
            return $this->redirectToRoute('checkup_assessment');
        }
        return $this->render('@AppBundle/Checkup/assessment_quiz.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route(path: '/assessment/fail', name: 'checkup_assessment_fail', methods: ['GET'])]
    public function assessmentFail(Request $request): Response
    {
        $this->logger->info("IN checkup completion");
        $obp = $this->onboardingService->getOnboardingProfileFromSession();
        return $this->render('@AppBundle/Checkup/assessment_fail.html.twig', [
            'obp' => $obp,
            'canTakeAssessment' => $this->onboardingService->canTakeAssessment($obp),
        ]);
    }

    #[Route(path: '/completion', name: 'checkup_completion', methods: ['GET'])]
    public function completion(Request $request): Response
    {
        $this->logger->info("IN checkup completion");
        return $this->render('@AppBundle/Checkup/completion.html.twig');
    }
}
