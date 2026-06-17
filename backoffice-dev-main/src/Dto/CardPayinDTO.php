<?php

namespace App\Dto;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

final class CardPayinDTO
{
    #[JMS\Type('int')]
    #[Assert\NotBlank]
    private int $userId;

    #[JMS\Type('int')]
    #[Assert\NotBlank]
    private int $amount;

    #[JMS\Type('string')]
    private string $currency = 'GBP';

    #[JMS\Type('string')]
    #[Assert\NotBlank]
    private string $secureModeReturnUrl;

    #[JMS\Type('string')]
    #[Assert\NotBlank]
    private string $ipAddress;

    #[JMS\Type("App\Dto\BrowserInfoDTO")]
    #[Assert\NotBlank]
    #[Assert\Valid]
    private BrowserInfoDTO $browserInfo;

    public function __construct(
        int $userId,
        int $amount,
        ?string $currency,
        string $secureModeReturnUrl,
        string $ipAddress,
        BrowserInfoDTO $browserInfo,
    ) {
        $this->userId = $userId;
        $this->amount = $amount;
        $this->currency = $currency ?? 'GBP';
        $this->secureModeReturnUrl = $secureModeReturnUrl;
        $this->ipAddress = $ipAddress;
        $this->browserInfo = $browserInfo;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function getSecureModeReturnUrl(): string
    {
        return $this->secureModeReturnUrl;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function getBrowserInfo(): BrowserInfoDTO
    {
        return $this->browserInfo;
    }
}
