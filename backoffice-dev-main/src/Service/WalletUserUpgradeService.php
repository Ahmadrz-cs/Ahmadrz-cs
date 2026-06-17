<?php

namespace App\Service;

use App\Entity\Enum\WalletUserVersion;
use App\Entity\User;
use Psr\Log\LoggerInterface;

/**
 * Service solely for performing a Mangopay User Category update
 * See https://gitlab.com/yielders2/backoffice-dev/-/issues/2107
 */
class WalletUserUpgradeService
{
    public function __construct(
        private LoggerInterface $logger,
        private MangopayWalletService $mangopayWalletService,
    ) {}

    public function upgradeUserCategory(User $user): void
    {
        $this->logger->debug(
            "Attempting Mangopay user category upgrade on user #{$user->getId()}",
        );
        $mangopayUser = $this->mangopayWalletService->getScaUser($user->getMangoPayUserId());
        $mangopayUser->UserCategory = 'Owner';
        $mangopayUser->TermsAndConditionsAccepted = true;
        $this->mangopayWalletService->updateScaUser($mangopayUser);
        $user->setWalletUserVersion(WalletUserVersion::UserCategoryUpdate);
    }
}
