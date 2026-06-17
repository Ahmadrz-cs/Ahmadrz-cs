<?php

namespace App\Entity;

use App\Entity\Address;
use App\Entity\Enum\BankAccountHolderType;
use App\Entity\Enum\BankAccountStatus;
use App\Entity\Enum\BankAccountType;
use App\Entity\User;
use App\Repository\BankAccountRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BankAccountRepository::class)]
class BankAccount
{
    use BlameableEntity;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bankAccounts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 32)]
    private ?BankAccountType $accountType = null;

    #[ORM\Column(length: 34, nullable: true)]
    private ?string $accountNumber = null;

    // Only for GB and SWIFT, IBANs don't need a BIC/sort-code
    #[ORM\Column(length: 11, nullable: true)]
    private ?string $bankIdentifierCode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    // e.g. Id returned by Mangopay on creation
    #[ORM\Column(length: 64, nullable: true)]
    private ?string $providerId = null;

    #[ORM\Column(length: 32)]
    private BankAccountHolderType $accountHolderType = BankAccountHolderType::Personal;

    // Alpha2 ISO Code
    #[Assert\Country]
    #[ORM\Column(length: 3)]
    private ?string $country = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $bankName = null;

    #[ORM\Column(length: 32)]
    private BankAccountStatus $status = BankAccountStatus::Pending;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $accountHolderName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $accountHolderLastName = null;

    #[ORM\OneToOne(targetEntity: Address::class, cascade: ['persist'])]
    private ?Address $accountHolderAddress = null;

    #[ORM\ManyToOne]
    private ?User $approvedBy = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fingerprint = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $displayName = null;

    #[ORM\Column(length: 3)]
    private ?string $currency = 'GBP';

    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private ?Uuid $uuid = null;

    #[ORM\Column(nullable: true)]
    private ?array $metadata = null;

    public function __construct()
    {
        $this->uuid = Uuid::v7();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getAccountType(): ?BankAccountType
    {
        return $this->accountType;
    }

    public function setAccountType(?BankAccountType $accountType): self
    {
        $this->accountType = $accountType;

        return $this;
    }

    public function getAccountNumber(): ?string
    {
        return $this->accountNumber;
    }

    public function setAccountNumber(?string $accountNumber): self
    {
        $this->accountNumber = $accountNumber;

        return $this;
    }

    public function getBankIdentifierCode(): ?string
    {
        return $this->bankIdentifierCode;
    }

    public function setBankIdentifierCode(?string $bankIdentifierCode): self
    {
        $this->bankIdentifierCode = $bankIdentifierCode;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getProviderId(): ?string
    {
        return $this->providerId;
    }

    public function setProviderId(?string $providerId): self
    {
        $this->providerId = $providerId;

        return $this;
    }

    public function getAccountHolderType(): BankAccountHolderType
    {
        return $this->accountHolderType;
    }

    public function setAccountHolderType(BankAccountHolderType $accountHolderType): self
    {
        $this->accountHolderType = $accountHolderType;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getBankName(): ?string
    {
        return $this->bankName;
    }

    public function setBankName(?string $bankName): self
    {
        $this->bankName = $bankName;

        return $this;
    }

    public function getStatus(): BankAccountStatus
    {
        return $this->status;
    }

    public function setStatus(BankAccountStatus $status): self
    {
        $this->status = $status;
        return $this;
    }

    // Workaround for Symfony workflows only supporting strings
    public function getStatusAsString(): string
    {
        return $this->status->value;
    }

    // Workaround for Symfony workflows only supporting strings
    public function setStatusAsString(string $statusAsString): self
    {
        $this->status = BankAccountStatus::from($statusAsString);

        return $this;
    }

    public function getAccountHolderName(): ?string
    {
        return $this->accountHolderName;
    }

    public function setAccountHolderName(?string $accountHolderName): self
    {
        $this->accountHolderName = $accountHolderName;

        return $this;
    }

    public function getAccountHolderLastName(): ?string
    {
        return $this->accountHolderLastName;
    }

    public function setAccountHolderLastName(?string $accountHolderLastName): self
    {
        $this->accountHolderLastName = $accountHolderLastName;

        return $this;
    }

    public function getAccountHolderAddress(): ?Address
    {
        return $this->accountHolderAddress;
    }

    public function setAccountHolderAddress(?Address $accountHolderAddress): self
    {
        $this->accountHolderAddress = $accountHolderAddress;

        return $this;
    }

    public function getApprovedBy(): ?User
    {
        return $this->approvedBy;
    }

    public function setApprovedBy(?User $approvedBy): self
    {
        $this->approvedBy = $approvedBy;

        return $this;
    }

    public function getFingerprint(): ?string
    {
        return $this->fingerprint;
    }

    public function setFingerprint(?string $fingerprint): static
    {
        $this->fingerprint = $fingerprint;

        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): static
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;

        return $this;
    }

    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): static
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }
}
