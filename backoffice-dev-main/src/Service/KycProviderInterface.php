<?php

namespace App\Service;

use App\Entity\KycReport;
use App\Entity\User;
use App\Entity\UserDocument;

interface KycProviderInterface
{
    public function isUserKycReady(User $user): bool;

    public function isCompanyKycReady(User $user): bool;

    public function createUser(User $user): KycReport;

    public function createCompany(User $user): KycReport;

    public function submitDocument(UserDocument $userdocument): KycReport;

    public function viewReport(
        User $user,
        string $reference,
        ?string $notes = null,
    ): KycReport;
}
