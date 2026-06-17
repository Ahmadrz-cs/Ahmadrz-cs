<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Enum\UserCategory;

class OnboardingProfile
{
    public ?\DateTimeInterface $cooloffEnd = null;
    public ?bool $cooloffAccepted = null;
    public ?bool $riskWarningAccepted = null;
    public ?UserCategory $category = null;
    public ?\DateTimeInterface $categoryReviewedAt = null;
    public ?bool $assessmentPassed = null;
    public ?int $assessmentAttempts = null;
    public ?\DateTimeInterface $assessmentAttemptedAt = null;
}
