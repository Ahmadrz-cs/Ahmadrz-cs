<?php

namespace App\Event\Kyc;

use App\Entity\KycReport;
use Symfony\Contracts\EventDispatcher\Event;

class KycReportCreatedEvent extends Event
{
    public function __construct(
        protected KycReport $kycReport,
    ) {
        $this->kycReport = $kycReport;
    }

    public function getKycReport()
    {
        return $this->kycReport;
    }
}
