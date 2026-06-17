<?php

namespace App\Entity;

final class RolePermissions
{
    /**
     * Temporary class for storing the RBAC permissions list
     *
     * DO NOT use this as the basis for other/new work
     */
    public const MIN_ANALYST = [
        'analyst',
        'techops',
        'operations',
        'finops',
        'admin',
        'superadmin',
    ];
    public const MIN_OPS = [
        'operations',
        'finops',
        'admin',
        'superadmin',
    ];
    public const MIN_FIN_OPS = [
        'finops',
        'admin',
        'superadmin',
    ];
    public const MIN_TECH_OPS = [
        'techops',
        'admin',
        'superadmin',
    ];
    public const MIN_ADMIN = [
        'admin',
        'superadmin',
    ];
    public const MIN_SUPER = [
        'superadmin',
    ];

    public const USER_PERMISSIONS = [
        'view user' => self::MIN_ANALYST,
        'edit user' => self::MIN_OPS,
        'create user' => self::MIN_OPS,

        'view user document' => self::MIN_ANALYST,
        'edit user document' => self::MIN_OPS,
        'create user document' => self::MIN_OPS,
        'delete user document' => self::MIN_ADMIN,
    ];

    public const ASSET_PERMISSIONS = [
        'view asset' => self::MIN_ANALYST,
        'edit asset' => self::MIN_OPS,
        'create asset' => self::MIN_OPS,

        'view asset document' => self::MIN_ANALYST,
        'edit asset document' => self::MIN_OPS,
        'create asset document' => self::MIN_OPS,
        'delete asset document' => self::MIN_ADMIN,
    ];

    public const OFFERING_PERMISSIONS = [
        'view offering' => self::MIN_ANALYST,
        'edit offering' => self::MIN_OPS,
        'create offering' => self::MIN_OPS,

        'view offering document' => self::MIN_ANALYST,
        'edit offering document' => self::MIN_OPS,
        'create offering document' => self::MIN_OPS,
        'delete offering document' => self::MIN_ADMIN,
    ];

    public const INVESTMENT_PERMISSIONS = [
        'view investment' => self::MIN_ANALYST,
        'edit investment' => self::MIN_OPS,
        'create investment' => self::MIN_OPS,

        'view investment document' => self::MIN_ANALYST,
        'edit investment document' => self::MIN_OPS,
        'create investment document' => self::MIN_OPS,
        'delete investment document' => self::MIN_ADMIN,
    ];

    public const PAYOUT_PERMISSIONS = [
        'view payout' => self::MIN_ANALYST,
        'edit payout' => self::MIN_OPS,
        'create payout' => self::MIN_OPS,
    ];

    public const RECORDS_PERMISSIONS = [
        'view shareholdings' => self::MIN_ANALYST,
        'view transactions' => self::MIN_ANALYST,
        'view kyc log' => self::MIN_ANALYST,
        'view audit log' => self::MIN_ANALYST,
        'view analytics' => self::MIN_ANALYST,
        'export data' => self::MIN_ANALYST,
    ];

    public const RBAC_PERMISSIONS = [
        'view user roles' => self::MIN_ANALYST,
        'edit user roles' => self::MIN_ADMIN,
    ];

    public const EMAIL_PERMISSIONS = [
        'view pending dividend emails' => self::MIN_OPS,
        'send dividend emails' => self::MIN_OPS,
        'send settlement emails' => self::MIN_OPS,
    ];

    public const FINANCIAL_PERMISSIONS = [
        'view pending settlements' => self::MIN_ANALYST,
        'settle investment' => self::MIN_FIN_OPS,
        'view pending payments' => self::MIN_ANALYST,
        'create dividend payment' => self::MIN_FIN_OPS,
        'create divestment payment' => self::MIN_FIN_OPS,
    ];

    public const API_CLIENT_PERMISSIONS = [
        'view API clients' => self::MIN_ANALYST,
        'edit API clients' => self::MIN_TECH_OPS,
        'create API clients' => self::MIN_TECH_OPS,
        'delete API clients' => self::MIN_TECH_OPS,
    ];

    public static function getAllPermissionRoles()
    {
        return array_merge(
            self::USER_PERMISSIONS,
            self::ASSET_PERMISSIONS,
            self::OFFERING_PERMISSIONS,
            self::INVESTMENT_PERMISSIONS,
            self::PAYOUT_PERMISSIONS,
            self::RECORDS_PERMISSIONS,
            self::RBAC_PERMISSIONS,
            self::EMAIL_PERMISSIONS,
            self::FINANCIAL_PERMISSIONS,
            self::API_CLIENT_PERMISSIONS,
        );
    }
}
