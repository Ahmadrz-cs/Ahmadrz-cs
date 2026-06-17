<?php

namespace App\Service\Manager;

use App\Entity\Payout;
use App\Entity\User;
use App\Repository\AssetRepository;
use App\Repository\PayoutRepository;
use App\Repository\UserRepository;
use App\Service\MangoPay;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PayoutManagerV2
{
    private ?string $superadminPaymentProviderUserId = null;

    public function __construct(
        private PayoutRepository $payoutRepository,
        private AssetRepository $assetRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private AuthorizationCheckerInterface $authorizationChecker,
        private Security $security,
        private Mangopay $mangopay,
        private LoggerInterface $logger,
    ) {}

    public function getPayout(int $payoutId): ?Payout
    {
        /** @var User $user */
        $user = $this->security->getUser();
        $currentUserId = $user->getId();
        $payout = $this->payoutRepository->find($payoutId);

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $payout;
        } else {
            if (!empty($payout)) {
                if ($payout->getInvestment()->getUser()->getId() == $currentUserId) {
                    return $payout;
                } else {
                    throw new AccessDeniedHttpException(sprintf('You do not have access to view Payout with ID '
                    . $payoutId));
                }
            }
        }
        return null;
    }

    public function getPayouts(
        ?int $page,
        ?int $limit,
        string $idFilter = '',
    ): ?Pagerfanta {
        $idArray = [];

        if (!empty($idFilter)) {
            $idArray = explode(',', $idFilter);
        }

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $this->payoutRepository->findAllPagerfanta($page, $limit, $idArray);
        } else {
            throw new AccessDeniedHttpException(sprintf(
                'You do not have access to view Payouts',
            ));
        }
        return null;
    }

    public function getSuperAdminAuthId(): ?string
    {
        // Optimisation to cache value in memory as a property
        // Note that this only lasts for the duration of the kernel (which is rebooted between requests)
        if ($this->superadminPaymentProviderUserId) {
            return $this->superadminPaymentProviderUserId;
        }
        $users = $this->userRepository->findByRole('ROLE_SUPER_ADMIN');
        if ($users) {
            foreach ($users as $user) {
                if ($user->hasRole('ROLE_SUPER_ADMIN')) {
                    if ($user->getMangoPayUserId()) {
                        $this->superadminPaymentProviderUserId =
                            $user->getMangoPayUserId();
                        return $this->superadminPaymentProviderUserId;
                    }
                }
            }
        }
        return null;
    }
}
