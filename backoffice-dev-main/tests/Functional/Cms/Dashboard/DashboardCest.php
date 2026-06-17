<?php

namespace App\Tests\Functional\Cms\Dashboard;

use App\Entity\Enum\BankAccountStatus;
use App\Entity\Enum\TradeDirection;
use App\Entity\Enum\TradeOrderType;
use App\Entity\Lifecycle\UserLifecycle;
use App\Tests\Support\FunctionalTester;

class DashboardCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group listview
     */
    public function checkElements(FunctionalTester $I)
    {
        $I->amOnPage('/admin');

        $sections = [
            'assets' => [
                'links' => [
                    'Create New Asset' => '/admin/products/create',
                    'Show Prefunding Assets' => '/admin/products?status%5B0%5D=acquiring',
                ],
            ],
            'monthend' => [
                'links' => [
                    'Go to Monthend Hub' => '/admin/monthend',
                    'Review Monthend Activity' => '/admin/monthend/review',
                ],
            ],
            'user-onboarding' => [
                'links' => [
                    'Show Users Pending Review' => '/admin/kyc/onboarding',
                    'Show Registered Users Without KYC Profile' =>
                        '/admin/kyc/onboarding?'
                            . http_build_query([
                                'ob_step' => 5,
                                'hasKycProfile' => 0,
                                'lifecycleStatus' => [
                                    UserLifecycle::STATE_EMAIL_VERIFIED,
                                    UserLifecycle::STATE_REGISTRATION_COMPLETE,
                                    UserLifecycle::STATE_APPROVED,
                                ],
                            ]),
                ],
            ],
            'secondary-market' => [
                'links' => [
                    'Show Pending Relistings' => '/admin/trading/trade-orders/sell',
                    'View All Relistings' =>
                        '/admin/trade-orders?'
                            . http_build_query([
                                'direction' => [TradeDirection::Sell->value],
                                'type' => [
                                    TradeOrderType::Market->value,
                                    TradeOrderType::Limit->value,
                                    TradeOrderType::StopLoss->value,
                                ],
                            ]),
                    'Review All Listings' => '/admin/products/review/listings',
                ],
            ],
            'bank-account-registrations' => [
                'links' => [
                    'Show Registrations to Review' =>
                        '/admin/bank-accounts?'
                            . http_build_query([
                                'status' => [
                                    BankAccountStatus::Pending,
                                    BankAccountStatus::Validated,
                                ],
                            ]),
                    'Show All Registrations' => '/admin/bank-accounts',
                ],
            ],
            'top-yielders' => [
                'links' => [
                    'Show Top Yielders' => '/admin/users?isVIP=1',
                    'Show Top Yielder Applications' => '/admin/kyc/vip',
                ],
            ],
        ];

        foreach ($sections as $sectionName => $checks) {
            foreach ($checks['links'] as $linkText => $linkUrl) {
                $I->see($linkText, "#{$sectionName} a");
                $I->seeLink($linkText, $linkUrl);
            }
        }
    }

    public function checkTodoRefresh(FunctionalTester $I): void
    {
        $I->amOnPage('/admin');
        $lastChecked = strtotime($I->grabTextFrom(
            '[data-field-name="todos-last-checked"]',
        ));
        sleep(1);
        $I->amOnPage('/admin');
        $reloadTime = new \DateTime()->getTimestamp();
        $I->assertGreaterThan($lastChecked, $reloadTime);
        sleep(1);
        $I->click('Refresh Todos');
        $I->seeCurrentUrlEquals('/admin');
        $newChecked = strtotime($I->grabTextFrom(
            '[data-field-name="todos-last-checked"]',
        ));
        $I->assertGreaterThan($lastChecked, $newChecked);
        $I->assertGreaterThan($reloadTime, $newChecked);
    }
}
