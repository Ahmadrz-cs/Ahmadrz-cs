<?php

namespace App\Tests\Functional\Cms;

use App\Tests\Support\FunctionalTester;

class RouteCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkLogOut(FunctionalTester $I)
    {
        $I->amOnPage('/logout');
        $I->seeResponseCodeIs(200);
    }

    public function checkRootRedirect(FunctionalTester $I)
    {
        $I->amOnPage('/');
        $I->seeResponseCodeIs(200);
        $I->seeCurrentUrlEquals('/admin');
    }

    // NOW THAT THEY ARE SPLIT APPLY ARRAYS TO LOOP ROUTES
    // ALSO CHECK FOR DUPLICATION
    //(I.E ASSET/INVESTMENTS ROUTES)
    // public function checkTopNavRoutes(FunctionalTester $I)
    // {
    //     $routes = [
    //         '/admin',
    //         '/admin/profile',
    //         '/admin/users/staff'
    //     ];

    //     foreach ($routes as $route) {
    //         $I->amOnPage($route);
    //         $I->seeResponseCodeIs(200);
    //     }
    // }

    public function checkAdministrationRoutes(FunctionalTester $I)
    {
        $routes = [
            '/admin/administration/documents',
            '/admin/investment/add',
            // '/admin/administration/directdebit/all',
            // '/admin/administration/directdebit',
            // '/admin/administration/directdebit/settled',
            '/admin/administration/clients',
        ];

        foreach ($routes as $route) {
            $I->amOnPage($route);
            $I->seeResponseCodeIs(200);
        }
    }

    public function checkUserRoutes(FunctionalTester $I)
    {
        $routes = [
            '/admin/users',
            '/admin/users/1/edit',
            '/admin/users/1/dashboard',
            '/admin/users/1/dashboard/onboarding',
            '/admin/users/1/dashboard/kyc',
            '/admin/users/1/dashboard/documents',
            '/admin/users/1/dashboard/investments',
            '/admin/users/1/dashboard/relistings',
            '/admin/users/1/dashboard/payments',
            '/admin/users/1/dashboard/portfolio',
            '/admin/users/1/dashboard/statements',
            '/admin/users/1/dashboard/bank-accounts',
            '/admin/users/1/dashboard/event-logs',
            '/admin/users/3/dashboard/mangopay-sca', // Ben user
        ];

        foreach ($routes as $route) {
            $I->amOnPage($route);
            $I->seeResponseCodeIs(200);
        }
    }

    public function checkUserDocRoutes(FunctionalTester $I)
    {
        $routes = [
            '/admin/userdocument',
            '/admin/userdocument/add',
            '/admin/userdocument/1/edit',
        ];

        foreach ($routes as $route) {
            $I->amOnPage($route);
            $I->seeResponseCodeIs(200);
        }
    }

    public function checkAssetRoutes(FunctionalTester $I)
    {
        $routes = [
            '/admin/asset',
            '/admin/asset/add',
            '/admin/asset/1/edit',
        ];

        foreach ($routes as $route) {
            $I->amOnPage($route);
            $I->seeResponseCodeIs(200);
        }
    }

    public function checkAssetDocRoutes(FunctionalTester $I)
    {
        $routes = [
            '/admin/assetdocument',
            '/admin/assetdocument/add',
            '/admin/assetdocument/1/edit',
        ];

        foreach ($routes as $route) {
            $I->amOnPage($route);
            $I->seeResponseCodeIs(200);
        }
    }

    public function checkOfferingRoutes(FunctionalTester $I)
    {
        $routes = [
            '/admin/offering',
            '/admin/offering/1/edit',
        ];

        foreach ($routes as $route) {
            $I->amOnPage($route);
            $I->seeResponseCodeIs(200);
        }
    }

    public function checkOfferingDocRoutes(FunctionalTester $I)
    {
        $routes = [
            '/admin/offeringdocument',
            '/admin/offeringdocument/add',
            '/admin/offeringdocument/1/edit',
        ];

        foreach ($routes as $route) {
            $I->amOnPage($route);
            $I->seeResponseCodeIs(200);
        }
    }

    public function checkInvestmentRoutes(FunctionalTester $I)
    {
        $routes = [
            '/admin/investment',
            '/admin/investment/1/edit',
        ];

        foreach ($routes as $route) {
            $I->amOnPage($route);
            $I->seeResponseCodeIs(200);
        }
    }

    public function checkInvestmentDocRoutes(FunctionalTester $I)
    {
        $routes = [
            '/admin/investmentdocument',
            '/admin/investmentdocument/add',
            '/admin/investmentdocument/1/edit',
        ];

        foreach ($routes as $route) {
            $I->amOnPage($route);
            $I->seeResponseCodeIs(200);
        }
    }

    public function checkPayoutRoutes(FunctionalTester $I)
    {
        $routes = [
            '/admin/payout',
            '/admin/payout/add',
            '/admin/payout/1/edit',
        ];

        foreach ($routes as $route) {
            $I->amOnPage($route);
            $I->seeResponseCodeIs(200);
        }
    }

    public function checkShareholdingRoutes(FunctionalTester $I)
    {
        $routes = [
            '/admin/holding',
            '/admin/holding/summary',
            '/admin/holding/trades',
        ];

        foreach ($routes as $route) {
            $I->amOnPage($route);
            $I->seeResponseCodeIs(200);
        }
    }

    public function checkMiscRoutes(FunctionalTester $I)
    {
        $routes = [
            '/admin/transactions',
            '/admin/contegoLog',
        ];

        foreach ($routes as $route) {
            $I->amOnPage($route);
            $I->seeResponseCodeIs(200);
        }
    }

    public function checkMangopaySupportRoutes(FunctionalTester $I)
    {
        // Only routes that are not already covered by permissions tests
        // These should be routes that are open to all staff (ANALYST+) users
        // Permissions tests use up too many Mangopay API calls
        // So these routes are tested here to limit them to 1 call each per test run
        $routes = [
            '/admin/wallets/wlt_m_01HW3DD8S6MFPYGVC0FPBHXAF2/transactions',
            '/admin/transactions/mangopay/transfers/xfer_m_01HWN0RFQZBDNX7SV84DA3JVRV',
            '/admin/transactions/mangopay/refunds/refund_m_01HWN12STB0WDV2BC6KSZ3M5M1',
        ];

        foreach ($routes as $route) {
            $I->amOnPage($route);
            $I->seeResponseCodeIs(200);
        }
    }
}
