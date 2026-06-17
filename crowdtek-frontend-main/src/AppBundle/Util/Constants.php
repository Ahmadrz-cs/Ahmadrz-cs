<?php

namespace AppBundle\Util;

class Constants
{
    public const LIMIT_ROWS_PER_PAGE = 3;
    public const LIMIT_ITEMS_PER_PAGE = 10;
    public const STAMP_DUTY_EMAIL = 'stampduty@yielders.co.uk';
    public const STAMP_DUTY_FEE = 0.5;
    public const ORGANIZATION_SHARES = 1000000;
    public const SETUP_FEE = 2.5;
    public const ADMIN_FEE = 50;
    public const MANAGEMENT_FEE = 10;
    public const PROFIT_SHARE = 15;
    public const NO_STAMP_DUTY_ASSETS = 49; // hardcoding #756 issue - suspend stamp duty for this asset, 49 is the prod asset id
}
