<?php

namespace App\Entity\Enum;

enum ActionRequest: string
{
    case ProofId = 'proof_of_id';
    case ProofAddress = 'proof_of_address';
    case ProofFunds = 'proof_of_funds';

    /**
     * Actions supported for bank account (registrations)
     * @return ActionRequest[]
     */
    public static function bankAccount(): array
    {
        return [
            self::ProofAddress,
        ];
    }

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
