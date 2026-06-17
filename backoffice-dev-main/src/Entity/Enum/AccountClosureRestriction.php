<?php

namespace App\Entity\Enum;

enum AccountClosureRestriction: string
{
    case Shareholder = 'Is an active shareholder. Must fully divest.';
    case Investments = 'Has any settled investments. Retain data for AML.';
    case MangopayUser = 'Has Mangopay user account. To be closed if not already.';
    case WalletBalance = 'Has non-empty wallet. Must empty wallet.';
    case Transactions = 'Has wallet transactions. Retain data for AML.';
    case Staff = 'Is a current staff member. Must demote staff to normal user.';

    public function isHardRestriction(): bool
    {
        // return in_array($this, $this::hardRestrictions());
        // Save having to do an array_diff if you use soft restrictions instead
        return !in_array($this, $this::softRestrictions());
    }

    /**
     * Return list of cases that are considered soft restrictions
     * @return AccountClosureRestriction[]
     */
    public static function softRestrictions(): array
    {
        return [
            AccountClosureRestriction::Investments,
            AccountClosureRestriction::MangopayUser,
            AccountClosureRestriction::Transactions,
        ];
    }

    /**
     * Return list of cases that are considered hard restrictions that prevent account closure
     * @return AccountClosureRestriction[]
     */
    public static function hardRestrictions(): array
    {
        // Anything that isn't a soft restriction is automatically a hard one
        return array_udiff(
            self::cases(),
            AccountClosureRestriction::softRestrictions(),
            fn($r1, $r2) => $r1->value <=> $r2->value,
        );
    }
}
