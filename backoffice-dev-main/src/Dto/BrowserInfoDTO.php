<?php

namespace App\Dto;

use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

final class BrowserInfoDTO
{
    #[JMS\Type('string')]
    #[Assert\NotBlank]
    private string $acceptHeader;

    #[JMS\Type('string')]
    #[Assert\NotBlank]
    private string $userAgent;

    #[JMS\Type('string')]
    #[Assert\NotBlank]
    private string $language;

    #[JMS\Type('int')]
    #[Assert\NotBlank]
    private int $screenWidth;

    #[JMS\Type('int')]
    #[Assert\NotBlank]
    private int $screenHeight;

    #[JMS\Type('int')]
    #[Assert\NotBlank]
    private int $colorDepth;

    #[JMS\Type('string')]
    #[Assert\NotBlank]
    private string $timeZoneOffset;

    #[JMS\Type('bool')]
    #[Assert\NotNull]
    private bool $javaEnabled;

    #[JMS\Type('bool')]
    #[Assert\NotNull]
    private bool $javascriptEnabled;

    public function __construct(
        string $acceptHeader,
        string $userAgent,
        string $language,
        int $screenWidth,
        int $screenHeight,
        int $colorDepth,
        string $timeZoneOffset,
        bool $javaEnabled,
        bool $javascriptEnabled,
    ) {
        $this->acceptHeader = $acceptHeader;
        $this->userAgent = $userAgent;
        $this->language = $language;
        $this->screenWidth = $screenWidth;
        $this->screenHeight = $screenHeight;
        $this->colorDepth = $colorDepth;
        $this->timeZoneOffset = $timeZoneOffset;
        $this->javaEnabled = $javaEnabled;
        $this->javascriptEnabled = $javascriptEnabled;
    }

    public function getAcceptHeader(): string
    {
        return $this->acceptHeader;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function getScreenWidth(): int
    {
        return $this->screenWidth;
    }

    public function getScreenHeight(): int
    {
        return $this->screenHeight;
    }

    public function getColorDepth(): int
    {
        return $this->colorDepth;
    }

    public function getTimeZoneOffset(): string
    {
        return $this->timeZoneOffset;
    }

    public function getJavaEnabled(): bool
    {
        return $this->javaEnabled;
    }

    public function getJavascriptEnabled(): bool
    {
        return $this->javascriptEnabled;
    }
}
