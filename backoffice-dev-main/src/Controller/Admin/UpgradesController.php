<?php

namespace App\Controller\Admin;

use App\Entity\Enum\WalletUserVersion;
use App\Form\Type\QueryUserType;
use App\Form\Type\UpgradeUserCategory;
use App\Repository\UserRepository;
use App\Service\WalletUserUpgradeService;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/upgrades')]
#[IsGranted('ROLE_TECH_OPS')]
final class UpgradesController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private UserRepository $userRepository,
        private WalletUserUpgradeService $walletUserUpgradeService,
        private ManagerRegistry $doctrine,
    ) {}

    #[Route(
        '/mangopay-user-category',
        name: 'admin_upgrade_mangopay_user_category',
        methods: ['GET'],
    )]
    public function mangopayUserCategory(Request $request): Response
    {
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
        $sitrep = $this->userRepository->getWalletUserVersionSummary();
        $sitrep = array_combine(
            array_column($sitrep, 'walletUserVersion'),
            array_column($sitrep, 'count'),
        );

        return $this->render('admin/pages/upgrades/user_category.html.twig', [
            'sitrep' => $sitrep,
            'objects' => $results,
            'form' => $form->createView(),
        ]);
    }

    #[Route(
        '/mangopay-user-category/review',
        name: 'admin_upgrade_mangopay_user_category_review',
        methods: ['GET', 'POST'],
    )]
    public function mangopayUserCategoryReview(Request $request): Response
    {
        $form = $this->createForm(UpgradeUserCategory::class, ['amount' => 1]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();

            $this->logger->debug('Using filters: ', $filters);
            $criteria = ['walletUserVersion' => WalletUserVersion::Original];
            if (!is_null($filters['id'])) {
                $criteria['id'] = $filters['id'];
            }
            $usersToUpgrade = $this->userRepository->findPendingUserCategoryUpgrades(
                $filters['amount'],
                $filters['id'],
            );
            $this->logger->debug(count($usersToUpgrade));
            $usersUpgraded = 0;
            $issues = [];
            foreach ($usersToUpgrade as $userUpgrading) {
                try {
                    $this->walletUserUpgradeService->upgradeUserCategory(
                        $userUpgrading,
                    );
                    $usersUpgraded++;
                } catch (\Exception $e) {
                    $userIdentifier = "#{$userUpgrading->getId()} // {$userUpgrading->getUserIdentifier()}";
                    $issues[$userIdentifier] = $e->getMessage();
                    $this->logger->warning(
                        "Issue encountered when upgrading user category. {$e->getMessage()}",
                    );
                }
            }
            $this->addFlash('success', "$usersUpgraded users upgraded");
            if (!empty($issues)) {
                $this->addFlash(
                    'warning',
                    'Issue encountered when upgrading user category. See issues in table below',
                );
            }
            $this->doctrine->getManager()->flush();
            $this->logger->info("$usersUpgraded users upgraded");
        }

        $sitrep = $this->userRepository->getWalletUserVersionSummary();
        $sitrep = array_combine(
            array_column($sitrep, 'walletUserVersion'),
            array_column($sitrep, 'count'),
        );

        return $this->render('admin/pages/upgrades/user_category_review.html.twig', [
            'sitrep' => $sitrep,
            'form' => $form->createView(),
            'issues' => $issues ?? [],
        ]);
    }
}
