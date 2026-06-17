<?php

namespace App\Entity\Enum;

enum AccountCleanupAction: string
{
    case Username = 'username';
    case Identity = 'identity info';
    case Contact = 'contact info';
    case Address = 'addresses';
    case Documents = 'documents';
    case Logs = 'logs and comms';
    case Onboarding = 'onboarding profile';
    case Company = 'company info';
    case AdditionalFields = 'additional fields';
    case Kyc = 'kyc';
    case Mangopay = 'Mangopay';
    case Salesforce = 'Salesforce';

    public function isInternalAction(): bool
    {
        // return in_array($this, $this::internalActions());
        // Save having to do an array_diff if you use external actions instead
        return !in_array($this, $this::externalActions());
    }

    /**
     * @return AccountCleanupAction[]
     */
    public static function actionsForRetentionLevel(
        AccountRetentionLevel $retentionLevel,
        bool $internalOnly = false,
    ): array {
        return match ($retentionLevel) {
            AccountRetentionLevel::None, AccountRetentionLevel::Wallet => $internalOnly
                ? self::internalActions()
                : self::cases(),
            AccountRetentionLevel::AML => $internalOnly
                ? []
                : [
                    AccountCleanupAction::Mangopay,
                    AccountCleanupAction::Salesforce,
                ],
            default => [],
        };
    }

    /**
     * @return AccountCleanupAction[]
     */
    public static function internalActions(): array
    {
        // Anything that isn't an external action is an internal one
        return array_udiff(
            self::cases(),
            AccountCleanupAction::externalActions(),
            fn($r1, $r2) => $r1->value <=> $r2->value,
        );
    }

    /**
     * @return AccountCleanupAction[]
     */
    public static function externalActions(): array
    {
        return [
            AccountCleanupAction::Mangopay,
            AccountCleanupAction::Salesforce,
        ];
    }
}
