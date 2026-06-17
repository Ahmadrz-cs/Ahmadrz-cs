<?php

namespace App\Controller\Admin;

use App\Form\Type\UtilitiesSharePriceType;
use App\Service\AssetService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/utilities')]
#[IsGranted('ROLE_ANALYST')]
class UtilitiesController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private AssetService $assetService,
    ) {}

    #[Route(path: '/asset-share-price', name: 'admin_utilities_asset_share_price')]
    #[IsGranted('ROLE_ANALYST')]
    public function assetSharePrice(Request $request): Response
    {
        $form = $this->createForm(UtilitiesSharePriceType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->logger->info('Getting asset share price suggestions');
            $formData = $form->getData();
            // Convert pounds to pence for calculations
            $fundingGoal = (int) round($formData['fundingGoal'] * 100);
            $sharePriceUserCap = (int) round($formData['sharePriceCap'] * 100);
            $sharePriceUserFloor = (int) round($formData['sharePriceFloor'] * 100);

            $sharePriceRange = $this->assetService->generateSharePriceRange(
                $fundingGoal,
                $sharePriceUserFloor,
                $sharePriceUserCap,
            );
            $suggestions = $this->assetService->suggestSharePrice(
                $fundingGoal,
                $sharePriceRange['min'],
                $sharePriceRange['max'],
            );
            $results['fundingGoal'] = $fundingGoal;
            $results['suggestions'] = $suggestions;
            $results['searchPriceMin'] = $sharePriceRange['min'];
            $results['searchPriceMax'] = $sharePriceRange['max'];
        }
        return $this->render('admin/pages/utilities/asset_share_price.html.twig', [
            'form' => $form->createView(),
            'results' => $results ?? [],
        ]);
    }
}
