<?php

namespace App\Tests\Functional\Cms;

use App\Tests\Support\FunctionalTester;

class NavigationCest
{
    public function _before(FunctionalTester $I): void
    {
        $I->loginAdmin();
    }

    public function testNavigationalLinks(FunctionalTester $I): void
    {
        $I->amOnPage('/admin');

        $cmsLinks = [
            [
                'text' => 'Assets',
                'path' => '/admin/asset',
            ],
            [
                'text' => 'Offerings',
                'path' => '/admin/offering',
            ],
            [
                'text' => 'Investments',
                'path' => '/admin/investment',
            ],
            [
                'text' => 'Payouts',
                'path' => '/admin/payout',
            ],
            [
                'text' => 'Users',
                'path' => '/admin/users',
            ],
            [
                'text' => 'Trade Orders',
                'path' => '/admin/trade-orders',
            ],
            [
                'text' => 'Share Trades',
                'path' => '/admin/share-trades',
            ],
            [
                'text' => 'Asset Documents',
                'path' => '/admin/assetdocument',
            ],
            [
                'text' => 'Offering Documents',
                'path' => '/admin/offeringdocument',
            ],
            [
                'text' => 'Investment Documents',
                'path' => '/admin/investmentdocument',
            ],
            [
                'text' => 'User Documents',
                'path' => '/admin/userdocument',
            ],
            [
                'text' => 'Shareholdings',
                'path' => '/admin/holding',
            ],
            [
                'text' => 'Share Trades',
                'path' => '/admin/holding/trades',
            ],
            [
                'text' => 'Payment Orders',
                'path' => '/admin/payment-order',
            ],
            [
                'text' => 'Transfer Orders',
                'path' => '/admin/transfer-orders',
            ],
            [
                'text' => 'Transactions',
                'path' => '/admin/transactions',
            ],
            [
                'text' => 'Report Sets',
                'path' => '/admin/reports/sets',
            ],
            [
                'text' => 'Reports',
                'path' => '/admin/reports',
            ],
            [
                'text' => 'KYC Reports',
                'path' => '/admin/kyc-reports',
            ],
            [
                'text' => 'Contego Log',
                'path' => '/admin/contegoLog',
            ],
            [
                'text' => 'Activity Timeline',
                'path' => '/admin/activity',
            ],
            [
                'text' => 'Questions',
                'path' => '/admin/questions',
            ],
        ];
        $opsLinks = [
            [
                'text' => 'Product Hub',
                'path' => '/admin/products',
            ],
            [
                'text' => 'Monthend Hub',
                'path' => '/admin/monthend',
            ],
            [
                'text' => 'KYC Hub',
                'path' => '/admin/kyc',
            ],
            [
                'text' => 'Bank Account Registrations',
                'path' => '/admin/bank-accounts',
            ],
            [
                'text' => 'Share Certificate Uploader',
                'path' => '/admin/investmentdocument/certificate-uploader',
            ],
            [
                'text' => 'Direct Debits (Mothballed)',
                'path' => '/admin/administration/directdebit/all',
            ],
        ];
        $toolsLinks = [
            [
                'text' => 'Exports and Reports',
                'path' => '/admin/export/hub',
            ],
            [
                'text' => 'Wallet Reporting',
                'path' => '/admin/reports/mangopay/sets',
            ],
            [
                'text' => 'Analytics',
                'path' => '/admin/analytics',
            ],
            [
                'text' => 'Asset Shares Tracker',
                'path' => '/admin/holding/summary',
            ],
            [
                'text' => 'Mangopay Events',
                'path' => '/admin/events/mangopay',
            ],
            [
                'text' => 'Asset Wallet Manager',
                'path' => '/admin/asset/wallets',
            ],
            [
                'text' => 'User Comms Cleanup',
                'path' => '/admin/maintenance/user-comms',
            ],
            [
                'text' => 'OAuth2 Artifact Cleanup',
                'path' => '/admin/maintenance/oauth2/cleanup',
            ],
            [
                'text' => 'Activity Log Cleanup',
                'path' => '/admin/maintenance/activity-logs/cleanup',
            ],
            [
                'text' => 'Manage Transactional Emails',
                'path' => '/admin/mailchimp/rejects',
            ],
            [
                'text' => 'Mangopay User Upgrade',
                'path' => '/admin/upgrades/mangopay-user-category',
            ],
            [
                'text' => 'Mangopay User Card Cleanup',
                'path' => '/admin/maintenance/card/cleanup',
            ],
            [
                'text' => 'Documents Sync',
                'path' => '/admin/administration/documents',
            ],
        ];
        $configLinks = [
            [
                'text' => 'API Clients',
                'path' => '/admin/administration/clients',
            ],
            [
                'text' => 'Mangopay Webhooks',
                'path' => '/admin/webhooks/mangopay',
            ],
            [
                'text' => 'User Roles and Permissions',
                'path' => '/admin/users/staff',
            ],
        ];
        $profileLinks = [
            [
                'text' => 'Profile',
                'path' => '/admin/profile/',
            ],
            [
                'text' => 'Log Out',
                'path' => '/logout',
            ],
        ];
        foreach ($cmsLinks as $link) {
            $I->seeLink($link['text'], $link['path']);
        }
        foreach ($opsLinks as $link) {
            $I->seeLink($link['text'], $link['path']);
        }
        foreach ($toolsLinks as $link) {
            $I->seeLink($link['text'], $link['path']);
        }
        foreach ($configLinks as $link) {
            $I->seeLink($link['text'], $link['path']);
        }
        foreach ($profileLinks as $link) {
            $I->seeLink($link['text'], $link['path']);
        }
        $I->seeLink('Check Service Statuses', '/admin/status');
    }

    public function checkDDSubNav(FunctionalTester $I): void
    {
        $I->amOnPage('/admin/administration/directdebit/all');
        $I->seeLink('Due Direct Debits', '/admin/administration/directdebit');
        $I->seeLink(
            'Settled Direct Debits',
            '/admin/administration/directdebit/settled',
        );
    }
}
