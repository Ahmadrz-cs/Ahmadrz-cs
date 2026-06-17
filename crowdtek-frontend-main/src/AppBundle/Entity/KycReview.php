<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Enum\KycReviewStatus;
use AppBundle\Entity\Enum\KycReviewType;

class KycReview
{
    public ?int $id = null;
    public ?KycReviewStatus $status = null;
    public ?bool $decision = null;
    public ?string $notes = null;
    public ?KycReviewType $reviewType = null;
    public ?int $subjectId = null;
    public ?int $reviewedById = null;
    public ?int $principalType = null;
    public ?bool $identityReview = null;
    public ?bool $addressReview = null;
    public ?bool $countryReview = null;
    public ?bool $kycProviderReview = null;
    public ?bool $dueDiligenceLevelReview = null;
    public ?bool $kycSurveyReview = null;
    public ?bool $transactionsReview = null;
}
