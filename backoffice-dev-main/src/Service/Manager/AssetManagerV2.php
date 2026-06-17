<?php

namespace App\Service\Manager;

use App\Dto\AssetAssembler;
use App\Dto\AssetDTO;
use App\Entity\Asset;
use App\Entity\AssetAddress;
use App\Entity\AssetMember;
use App\Entity\BaseEntity;
use App\Entity\Lifecycle\AssetLifecycle;
use App\Repository\AssetRepository;
use App\Service\MangoPay;
use App\Service\MangopayWalletService;
use Doctrine\ORM\EntityManagerInterface;
use Pagerfanta\Pagerfanta;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AssetManagerV2
{
    public const MIN_SUPPORTED_WALLETS = [
        'hold',
        'settlement',
    ];

    public const SUPPORTED_WALLETS = [
        'hold',
        'settlement',
        'deposit',
        'expenses',
        'tax',
        'distribution',
        'treasury',
    ];

    public function __construct(
        private LoggerInterface $logger,
        private AssetRepository $assetRepository,
        private EntityManagerInterface $entityManager,
        private AssetAssembler $assetAssembler,
        private AuthorizationCheckerInterface $authorizationChecker,
        private MangopayWalletService $walletService,
    ) {}

    public function getAssets(
        $page,
        $limit,
        $idFilter = '',
        $assetTypeFilter = '',
        $statusFilter = '',
    ): ?Pagerfanta {
        $idArray = [];

        if (!empty($idFilter)) {
            $idArray = explode(',', $idFilter);
        }

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $this->assetRepository->findAllPagerfanta(
                $page,
                $limit,
                $idArray,
                $assetTypeFilter,
                $statusFilter,
            );
        } else {
            return $this->assetRepository->findAllPublished(
                $page,
                $limit,
                $idArray,
                $assetTypeFilter,
            );
        }
    }

    public function getAsset($assetId): ?Asset
    {
        $asset = $this->assetRepository->find($assetId);

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $asset;
        } else {
            if (!empty($asset)) {
                if ($asset->getLifecycleStatus() == 'published') {
                    return $asset;
                } else {
                    throw new AccessDeniedHttpException(sprintf('You do not have access to view Asset with id '
                    . $assetId));
                }
            }
        }

        return null;
    }

    public function addAsset(AssetDTO $assetDTO): Asset
    {
        $asset = $this->assetAssembler->createAsset($assetDTO);
        $this->assetRepository->save($asset);
        $this->entityManager->flush();

        return $asset;
    }

    public function updateAsset(int $assetId, AssetDTO $assetDTO): Asset
    {
        $asset = $this->assetRepository->find($assetId);
        if (!$asset) {
            return null;
        }
        $asset = $this->assetAssembler->updateAsset($asset, $assetDTO);
        $this->assetRepository->save($asset);
        $this->entityManager->flush();

        return $asset;
    }

    public function getWalletIdByType(Asset $asset, string $walletType): ?string
    {
        /**
         * Use Symfony PropertyAccess component to access the wallet ids
         * Disable exception mode, so it returns null if property doesn't exist
         * This allows consistent handling of cases where either the wallet
         * - Property doesn't exist
         * - Property wallet exists but is not set
         * https://symfony.com/doc/current/components/property_access.html
         */
        $propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->disableExceptionOnInvalidPropertyPath()
            ->getPropertyAccessor();
        return $propertyAccessor->getValue($asset, $walletType . 'WalletId');
    }

    public function getAssetWalletByType(Asset $asset, string $walletType): array
    {
        /**
         * Throw exception for any invalid state
         * - Unknown wallet type
         * - No wallet configured for type
         * - Failure to get the wallet because it is either invalid or Mangopay is unavailable
         *   - Log this since it is a system error, not a userland mistake
         */
        $walletType = strtolower($walletType);
        if (!in_array($walletType, self::SUPPORTED_WALLETS)) {
            throw new \Exception("Wallet type {$walletType} is not supported");
        }

        $walletId = $this->getWalletIdByType($asset, $walletType);

        if (is_null($walletId)) {
            throw new \Exception(
                "Wallet type {$walletType} is empty and has not been configured",
            );
        }

        try {
            $providerWallet = $this->walletService->getWallet(
                $walletId,
                'USER_NOT_PRESENT',
            );
            return [
                'type' => $walletType,
                'walletId' => $walletId,
                'balance' => number_format(
                    $providerWallet->Balance->Amount / 100,
                    2,
                    '.',
                    '',
                ),
                'currency' => (string) $providerWallet->Currency,
                'description' => (string) $providerWallet->Description,
                // Mangopay provide an array of owners, we only want the first one
                'owner' => !empty($providerWallet->Owners)
                    ? (string) reset($providerWallet->Owners)
                    : 'Not found',
                // 'lastChecked' => new \DateTime(),
            ];

            // Note that this array is a rudimentary replacement for an actual class/object
            // See https://gitlab.com/yielders2/backoffice-dev/-/issues/2055#note_1020499076
            // return $this->walletService->getWallet($walletId);
        } catch (\Exception $e) {
            // Log the error and rethrow exception
            $this->logger->error(
                "Wallet with id {$walletId} could not be retrieved",
                [$e->getMessage(), $e->getCode()],
            );
            if (str_contains($e->getMessage(), 'Not found')) {
                throw new \Exception(
                    "Wallet with id {$walletId} could not be found. Please check the id is valid",
                );
            } else {
                throw $e;
            }
        }
    }

    public function getAssetWallets(Asset $asset, bool $loadWallets = true): array
    {
        $assetWallets = [];

        foreach (self::SUPPORTED_WALLETS as $walletType) {
            try {
                if ($loadWallets) {
                    $assetWallets[] = $this->getAssetWalletByType($asset, $walletType);
                } else {
                    $assetWallets[] = [
                        'type' => $walletType,
                        'walletId' => $this->getWalletIdByType($asset, $walletType),
                        // "lastChecked" => new \DateTime(),
                        'balance' => null,
                        'currency' => null,
                        'description' => null,
                        'owner' => null,
                    ];
                }
            } catch (\Exception $e) {
                $assetWallets[] = [
                    'type' => $walletType,
                    'walletId' => $this->getWalletIdByType($asset, $walletType),
                    // "lastChecked" => new \DateTime(),
                    'balance' => null,
                    'currency' => null,
                    'description' => null,
                    'owner' => null,
                ];
            }
        }
        return $assetWallets;
    }

    public function createAssetWalletObject(
        Asset $asset,
        string $walletName,
    ): ?\MangoPay\Wallet {
        if (is_null($asset->getContactPoint())) {
            return null;
        }
        if (is_null($asset->getContactPoint()->getMangoPayUserId())) {
            return null;
        }

        $wallet = new \MangoPay\Wallet();
        $wallet->Owners = [$asset->getContactPoint()->getMangoPayUserId()];
        $wallet->Description =
            $asset->getCompanyNumber()
            . ' '
            . $asset->getId()
            . ' '
            . $asset->getName()
            . ' '
            . $walletName;
        $wallet->Currency = 'GBP';

        return $wallet;
    }

    /**
     * Create an e-wallet for the asset.
     *
     * @throws \MangoPay\Libraries\ResponseException $e
     * @throws \MangoPay\Libraries\Exception $e
     */
    public function createWallet(Asset $asset, string $type): void
    {
        $type = strtolower($type);
        $this->logger->notice($type, ['asset' => $asset->getName()]);

        $propertyAccessor =
            PropertyAccess::createPropertyAccessorBuilder()->getPropertyAccessor();

        if (in_array($type, self::SUPPORTED_WALLETS)) {
            $this->logger->debug('making new wallet');
            $walletId = $propertyAccessor->getValue($asset, $type . 'WalletId');
            if (is_null($walletId)) {
                $walletName = ucwords($type . ' wallet');
                $wallet = $this->createAssetWalletObject($asset, $walletName);
                $result = $this->walletService->createWallet($wallet);
                if (isset($result->Id)) {
                    $propertyAccessor->setValue(
                        $asset,
                        $type . 'WalletId',
                        $result->Id,
                    );
                    $this->entityManager->flush();
                }
            }
        }
    }

    /**
     * Creates all e-wallets for the asset.
     *
     * @throws \MangoPay\Libraries\ResponseException $e
     * @throws \MangoPay\Libraries\Exception $e
     * @return string[]
     */
    public function createAllWallets(Asset $asset, bool $onlyMinimum = false): array
    {
        $walletsToCreate = $onlyMinimum
            ? self::MIN_SUPPORTED_WALLETS
            : self::SUPPORTED_WALLETS;
        // $this->logger->debug("Wallets to create if missing", $walletsToCreate);
        foreach ($walletsToCreate as $walletType) {
            $this->createWallet($asset, $walletType);
        }
        return $walletsToCreate;
    }
}
