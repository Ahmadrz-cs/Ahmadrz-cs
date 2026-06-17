<?php

namespace ClientBundle\Service;

use AppBundle\Entity\Enum\KycReviewStatus;
use AppBundle\Entity\Enum\KycReviewType;
use AppBundle\Entity\KycReview;
use ClientBundle\Service\Yielders\ApiClient;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class VerificationService
{
    public function __construct(
        private ApiClient $client,
        private LoggerInterface $logger,
        private RequestStack $requestStack,
        private NormalizerInterface $normalizer,
        private DenormalizerInterface $denormalizer,
    ) {
    }

    public function needsIdentityVerification(): bool
    {
        $kycReviews = $this->getOpenKycReviewsFromSession();
        if (!empty($this->getIdentityKycReview($kycReviews))) {
            return true;
        }
        return false;
    }

    /**
     * @param \AppBundle\Entity\KycReview[]
     */
    public function getIdentityKycReview(array $kycReviews): ?KycReview
    {
        /**
         * Check if any KycReviews match criteria for an identity review
         * - Must be PendingSubjectAction
         * - Must have an identityReview action
         * - Must be either Adhoc or Recurring type
         */
        foreach ($kycReviews as $kycReview) {
            if (
                $kycReview instanceof KycReview
                && $kycReview->status === KycReviewStatus::PendingSubjectAction
                && $kycReview->identityReview
                && in_array($kycReview->reviewType, [KycReviewType::Adhoc, KycReviewType::Recurring])
            ) {
                return $kycReview;
            }
        }
        return null;
    }

    /**
     * @return \AppBundle\Entity\KycReview[]
     */
    public function getOpenKycReviewsFromSession(): array
    {
        $userInfo = $this->requestStack->getSession()->get('userInfo');
        $kycReviews = [];
        if (!is_null($userInfo) && array_key_exists('open_kyc_reviews', $userInfo)) {
            $kycReviews = $userInfo['open_kyc_reviews'];
        }
        // https://symfony.com/doc/current/serializer.html#handling-arrays
        return $this->denormalizer->denormalize(
            $kycReviews,
            KycReview::class . '[]',
        );
    }

    public function markKycReviewAsReady(KycReview $kycReview): array
    {
        $kycReview->status = KycReviewStatus::Ready;
        $requestBody = $this->normalizer->normalize(
            $kycReview,
            'json',
            [AbstractObjectNormalizer::SKIP_NULL_VALUES => true]
        );
        $this->logger->debug("Update kyc review status to ready with ", $requestBody);
        $response = $this->client->kycReview()->update(
            $kycReview->id,
            ['json' => $requestBody]
        );
        if (!($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            $this->logger->debug(
                "Status code: {$response->getStatusCode()}. Response: ",
                $this->client->getContent($response)
            );
            throw new BadRequestHttpException('Unable to update kyc review status to ready');
        }
        return $this->client->getContent($response);
    }
}
