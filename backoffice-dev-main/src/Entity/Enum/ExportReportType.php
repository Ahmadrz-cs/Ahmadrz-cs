<?php

namespace App\Entity\Enum;

enum ExportReportType: string
{
    case ShareTradeRegister = 'share_trade_register';
    case ShareTrades = 'share_trades';
    case TradeOrders = 'trade_orders';
}
