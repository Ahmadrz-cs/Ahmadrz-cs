<?php

namespace App\Entity\Enum;

enum AccountRetentionLevel: string
{
    case None = 'none'; // Nothing needs to be kept
    case Wallet = 'wallet'; // Wallet (Mangopay) IDs are kept
    case AML = 'aml'; // Keep most things - some historical data can be removed if superseded by more recent, e.g. address, documents
    case Full = 'full'; // Keep everything - shouldn't need this unless we suspect fraud

    /**
     * @return AccountRetentionLevel[]
     */
    public static function minimalRetention(): array
    {
        return [
            AccountRetentionLevel::None,
            AccountRetentionLevel::Wallet,
        ];
    }
}
