<?php

namespace App\Entity;

use App\Entity\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Table(name: 'user_security')]
#[ORM\Entity]
// ForDBAL4 #[Gedmo\Loggable]
class UserSecurity extends BaseEntity
{
    #[ORM\OneToOne(targetEntity: 'User', mappedBy: 'security')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $user;

    /**
     * @var string
     */
    #[ORM\Column(name: 'mfaPreference', type: 'string', nullable: true)]
    private $mfaPreference;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'mfaTotp', type: 'boolean', options: ['default' => 0])]
    private $mfaTotp = false;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'mfaEmail', type: 'boolean', options: ['default' => 1])]
    private $mfaEmail = true;

    /**
     * @var string
     */
    #[ORM\Column(name: 'totpKey', type: 'string', length: 511, nullable: true)]
    private $totpKey;

    /**
     * @var string
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    private $emailAuthCode;

    public function setUser(?\App\Entity\User $user = null): self
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?\App\Entity\User
    {
        return $this->user;
    }

    public function getMfaPreference(): ?string
    {
        return $this->mfaPreference;
    }

    public function setMfaPreference(string $mfaPreference): self
    {
        $this->mfaPreference = $mfaPreference;
        return $this;
    }

    public function getMfaTotp(): bool
    {
        return $this->mfaTotp;
    }

    public function setMfaTotp(bool $mfaTotp): self
    {
        $this->mfaTotp = $mfaTotp;
        return $this;
    }

    public function getMfaEmail(): bool
    {
        return $this->mfaEmail;
    }

    public function setMfaEmail(bool $mfaEmail): self
    {
        $this->mfaEmail = $mfaEmail;
        return $this;
    }

    public function getTotpKey(): ?string
    {
        return $this->totpKey;
    }

    public function setTotpKey(string $totpKey): void
    {
        $this->totpKey = $totpKey;
    }

    public function getEmailAuthCode(): ?string
    {
        return $this->emailAuthCode;
    }

    public function setEmailAuthCode(string $emailAuthCode): void
    {
        $this->emailAuthCode = $emailAuthCode;
    }
}
