<?php

namespace AppBundle\Controller;

use AppBundle\Util\Util;
use ClientBundle\Service\AssetProductService;
use ClientBundle\Service\CrowdTekService;
use ClientBundle\Service\PublicService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    private $params = [];

    public function __construct(
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private CrowdTekService $crowdtekService,
        private PublicService $publicService,
        private AssetProductService $assetProductService,
        private string $network,
    ) {}

    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request): Response
    {
        $this->logger->info("==================IN indexAction=====================");

        $referer = $request->headers->get('referer');
        if (!empty($referer)) {
            $this->requestStack->getSession()->set('cv_referer', urldecode($referer));
        }

        // Check if a user has completed their on boarding stages, if not redirect them to onboarding
        $ObResponse = $this->crowdtekService->checkRegistrationStatus();

        if ($ObResponse == false) {
            return $this->redirect($this->generateUrl('Onboarding'));
        }

        $isAuth = $this->requestStack->getSession()->get('authenticated');

        $featuredAssets = [];
        try {
            $featuredAssets = $this->assetProductService->getPublicFeaturedProducts();
        } catch (\Throwable $th) {
            $this->logger->error("Unable to load featured assets");
        }

        return $this->render('@AppBundle/Default/index.html.twig', [
            'is_auth' => $isAuth,
            'menu_item' => 'homepage',
            'featuredAssets' => $featuredAssets,
        ]);
    }

    /**
     * @Route("/about-us", name="about_us")
     */
    public function viewAboutUsAction(): Response
    {
        $this->logger->info("==================IN viewAboutUsAction=====================");

        $teamData = [
            [
                'name' => 'Adnan Malik',
                'position' => 'CEO & Managing Director',
                'bio' => '',
            ],
            [
                'name' => 'Abid Karim',
                'position' => 'Co-Founder & Chairman',
                'bio' => '',
            ],
        ];
        return $this->render('@AppBundle/Default/about_us.html.twig', [
            'menu_item' => 'about_us',
            'team_members' => $teamData,
        ]);
    }

    /**
     * @Route("/contact-us", name="contact_us")
     */
    public function contactUsAction(): Response
    {
        $this->logger->info("==================IN contactUsAction=====================");

        $this->params['menu_item'] = 'contact_us';
        return $this->render('@AppBundle/Default/contact_us.html.twig', $this->params);
    }

    /**
     * @Route("/process/how-it-works", name="how_it_works")
     */
    public function howItWorksAction(): Response
    {
        $this->logger->info("==================IN howItWorksAction=====================");

        $this->params['menu_item'] = 'how-it-works';
        return $this->render('@AppBundle/Process/how_it_works.html.twig', $this->params);
    }

    /**
     * @Route("/terms-conditions", name="terms_conditions")
     */
    public function termsConditionsAction(): Response
    {
        $this->logger->info("==================IN termsConditionsAction=====================");

        $this->params['menu_item'] = 'terms-conditions';
        return $this->render('@AppBundle/Default/terms_conditions.html.twig', $this->params);
    }

    /**
     * @Route("/privacy-policy", name="privacy_policy")
     */
    public function privacyPolicyAction(): Response
    {
        $this->logger->info("==================IN privacyPolicyAction=====================");

        $this->params['menu_item'] = 'privacy-policy';
        return $this->render('@AppBundle/Default/privacy_policy.html.twig', $this->params);
    }

    /**
     * @Route("/become-top-yielder", name="become_top_yielder")
     */
    public function becomeTopYielderAction(): Response
    {
        $this->logger->info("==================IN becomeTopYielderAction=====================");

        $this->params['menu_item'] = 'become-top-yielder';
        return $this->render('@AppBundle/Default/become_top_yielder.html.twig', $this->params);
    }

    /**
     * @Route("/complete-onboarding", name="complete-onboarding")
     */
    public function completeOnboardingAction(Request $request): Response
    {
        $this->logger->info("==================onboarding not complete message=====================");

        $message = "
                    <p>Please complete your onboarding to continue with adding funds and making your first investment.</p>
                    <div style='text-align: center !important;'>
                    <button class='btn_grdnt' onclick=\"location.href='/onboarding'\" >Ok</button>
                    </div>";
        $this->addFlash('errors', $message);

        return $this->redirect($request->headers->get('referer'));
    }
}
