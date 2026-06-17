<?php

namespace App\Service\Manager;

use App\Dto\OfferingAssembler;
use App\Dto\OfferingDTO;
use App\Entity\Lifecycle\OfferingLifecycle;
use App\Entity\Offering;
use App\Repository\OfferingRepository;
use App\Service\Manager\AssetManagerV2;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class OfferingManagerV2
{
    public function __construct(
        private OfferingRepository $offeringRepository,
        private EntityManagerInterface $entityManager,
        private AuthorizationCheckerInterface $authorizationChecker,
        private OfferingAssembler $offeringAssembler,
        private AssetManagerV2 $assetManager,
        private LoggerInterface $logger,
    ) {}

    public function getOffering($offId)
    {
        $offering = $this->offeringRepository->find($offId);

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $offering;
        } else {
            if (!empty($offering)) {
                if ($offering->getLifecycleStatus() == 'published') {
                    return $offering;
                } else {
                    throw new AccessDeniedHttpException(sprintf('You do not have access to view Offering with id '
                    . $offId));
                }
            }
        }
        return null;
    }

    public function getOfferings(
        int $page,
        int $limit,
        string $idFilter = '',
        string $statusFilter = '',
        bool $isFeaturedFilter = false,
    ) {
        $idArray = [];

        if (!empty($idFilter)) {
            $idArray = explode(',', $idFilter);
        }

        if (!empty($isFeaturedFilter)) {
            $isFeaturedFilter = $isFeaturedFilter === 'true' ? true : false;
        }

        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $this->offeringRepository->findAllPagerfanta(
                $page,
                $limit,
                $idArray,
                $statusFilter,
                $isFeaturedFilter,
            );
        } else {
            return $this->offeringRepository->findAllPagerfanta(
                $page,
                $limit,
                $idArray,
                'published',
                $isFeaturedFilter,
            );
        }
    }

    /**
     * Returns an array of offerings realted to the asset id
     * Non admin users cannot view offerings if the related asset is not published
     * Non admin users cannot view offerings which are not published
     */
    public function getOfferingByAssetId(int $assetId): array
    {
        if ($this->authorizationChecker->isGranted('ROLE_ADMIN')) {
            return $this->offeringRepository->findByAssetId($assetId);
        } else {
            //getAsset() will throw an AccessDeniedHttpException if asset is unplubished
            if ($this->assetManager->getAsset($assetId)) {
                return $this->offeringRepository->findPublishedByAssetId($assetId);
            }
        }
    }

    /**
     * @param OfferingDTO $offeringDTO
     * @return Offering
     */

    public function addOffering(OfferingDTO $offeringDTO): Offering
    {
        $offering = $this->offeringAssembler->createOffering($offeringDTO);
        $this->offeringRepository->save($offering);
        $this->entityManager->flush();

        return $offering;
    }

    /**
     * @param int $offId
     * @param OfferingDTO $offeringDTO
     * @return Offering
     */

    public function updateOffering(int $offId, OfferingDTO $offeringDTO): ?Offering
    {
        $offering = $this->offeringRepository->find($offId);

        if (!$offering) {
            return null;
        }

        $offering = $this->offeringAssembler->updateOffering($offering, $offeringDTO);
        $this->offeringRepository->save($offering);
        $this->entityManager->flush();

        return $offering;
    }

    public function setSharedFields(Offering $offering): Offering
    {
        /**
         * - If asset linked and asset has certain fields are set
         *   - Name (always set as name is guaranteed if asset linked)
         *   - Share Price
         *   - Number of Shares
         *   - Investment term
         */
        if ($offering->getAsset()) {
            $offering->setName(
                $offering->getName() ?? $offering->getAsset()->getName(),
            );
            $offering->setOfferingTerm(
                $offering->getOfferingTerm()
                ?? (int) ($offering->getAsset()->getInvestmentTerm() / 12),
            );
            if (empty($offering->getPricePerShare())) {
                $offering->setPricePerShare($offering->getAsset()->getPricePerShare());
            }
            if (empty($offering->getNoOfShares())) {
                $offering->setNoOfShares($offering->getAsset()->getAmountOfShares());
            }
        }
        return $offering;
    }

    public function setFundingGoal(Offering $offering): Offering
    {
        $offering->setFundingGoal(
            $offering->getPricePerShare() * $offering->getNoOfShares(),
        );
        return $offering;
    }

    public function roundMinMaxCommit(Offering $offering): Offering
    {
        $sharePrice = (float) $offering->getPricePerShare();
        if (empty($sharePrice)) {
            return $offering;
        }
        /**
         * Due to floating point arithmetic
         * Must round off some imprecision before applying ceil/floor
         */
        $minCommitShares = ceil(round($offering->getMinCommitUser() / $sharePrice, 2));
        $offering->setMinCommitUser(round($minCommitShares * $sharePrice, 2));

        /** Special case where max commit is empty or zero, set to fundingGoal */
        if ($offering->getMaxCommitUser()) {
            $maxCommitShares = floor(round(
                $offering->getMaxCommitUser() / $sharePrice,
                2,
            ));
            $maxCommitShares = max($minCommitShares, $maxCommitShares);
            $offering->setMaxCommitUser(round($maxCommitShares * $sharePrice, 2));
        } else {
            $offering->setMaxCommitUser($offering->getFundingGoal());
        }
        return $offering;
    }

    public function transitionToPublished(Offering $offering): Offering
    {
        // Transition through all relevant statuses until published
        if (OfferingLifecycle::STATE_DRAFT == $offering->getLifecycleStatus()) {
            $offering->setLifecycleStatus(OfferingLifecycle::STATE_SUBMITTED);
        }
        if (OfferingLifecycle::STATE_SUBMITTED == $offering->getLifecycleStatus()) {
            $offering->setLifecycleStatus(OfferingLifecycle::STATE_APPROVED);
        }
        if (OfferingLifecycle::STATE_APPROVED == $offering->getLifecycleStatus()) {
            $offering->setLifecycleStatus(OfferingLifecycle::STATE_PUBLISHED);
        }
        // Any other statuses will not result in any transitions
        return $offering;
    }

    public function processPaymentOutcome(Offering $offering, bool $success): Offering
    {
        $offeringStatus = $success
            ? OfferingLifecycle::STATE_SUBMITTED
            : OfferingLifecycle::STATE_CANCELLED;
        // Only update if necessary
        // Any state other than draft is considered beyond the scope of payment processing
        if ($offering->getLifecycleStatus() == OfferingLifecycle::STATE_DRAFT) {
            // $this->logger->debug("Updating offering to {$offeringStatus} status");
            $offering->setLifecycleStatus($offeringStatus);
        }
        return $offering;
    }
}
