<?php

namespace App\Entity;

use App\Entity\User;
use App\Repository\KycReportRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: KycReportRepository::class)]
class KycReport
{
    #[ORM\Column, ORM\Id, ORM\GeneratedValue]
    public readonly int $id;

    #[ORM\Column]
    #[Gedmo\Timestampable(on: 'create')]
    public readonly \DateTime $createdAt;

    #[ORM\Column(nullable: true)]
    #[Gedmo\Blameable(on: 'create')]
    public readonly ?string $createdBy;

    public function __construct(
        #[ORM\ManyToOne(inversedBy: 'kycReports')]
        #[ORM\JoinColumn(nullable: false)]
        public readonly User $subject,
        #[ORM\Column(length: 255)]
        public readonly string $providerName,
        #[ORM\Column(length: 255)]
        public readonly string $providerReferenceId,
        #[ORM\Column(length: 255)]
        public readonly string $checkType,
        #[ORM\Column(length: 255)]
        public readonly string $result,
        #[ORM\Column(length: 255)]
        public readonly string $score,
        #[ORM\Column]
        public readonly bool $verified,
        #[ORM\Column]
        public readonly \DateTime $checkedAt = new \DateTime(),
        #[ORM\Column(length: 255, nullable: true)]
        public readonly ?string $note = null,
    ) {}
}
