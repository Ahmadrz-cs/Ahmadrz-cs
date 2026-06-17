<?php

namespace App\Tests\Functional\Ops\Analytics;

use App\Tests\Support\FunctionalTester;

class AnalyticsCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkNavs(FunctionalTester $I)
    {
        $I->amOnPage('/admin/analytics');
        $I->see('Investments Visualisation', 'a[href="/admin/analytics/investments"]');
        $I->see('Referrals Visualisation', 'a[href="/admin/analytics/referrals"]');

        $I->seeLink('View Full Analysis', '/admin/analytics/active-users');
        $I->seeLink('Referrals Visualisation', '/admin/analytics/referrals');
        $I->seeLink('Investments Visualisation', '/admin/analytics/investments');
        $I->seeLink('View Full Analysis', '/admin/analytics/general-investments');
        $I->seeLink('View Full Analysis', '/admin/analytics/dividends');
    }

    public function checkPageTitles(FunctionalTester $I)
    {
        $I->amOnPage('/admin/analytics');
        $I->see('Analytics');
        $I->see('Active Users');
        $I->see('User Registrations');
        $I->see('First Party Investments');
        $I->see('General Investments');
        $I->see('Dividends');
    }

    public function checkInvestmentsVis(FunctionalTester $I)
    {
        $I->amOnPage('/admin/analytics/investments');
        $I->see('Investments');
        $I->see('Investment Count Over Time');
        $I->see('Investment Values Over Time');
        $I->seeElement('canvas#chartArea');
    }

    public function checkReferralsVis(FunctionalTester $I)
    {
        $I->amOnPage('/admin/analytics/referrals');
        $I->see('Referrals');
        $I->see('Top 10 Referral Codes');
        $I->seeElement('canvas#chartArea');
    }

    public function checkActiveUserAnalysis(FunctionalTester $I)
    {
        $I->amOnPage('/admin/analytics/active-users');
        $I->see('Active Users');
        $I->see('User logins', '#user-logins');
        $I->see('API Sessions', '#api-sessions');
    }

    public function checkDividendAnalysis(FunctionalTester $I)
    {
        $I->amOnPage('/admin/analytics/dividends');
        $I->see('Dividends');
        $I->see('Asset Dividends', '#asset-dividends');
        $I->see('Asset Dividend Performance', '#dividend-performance');
    }

    public function checkGeneralInvestmentAnalysis(FunctionalTester $I)
    {
        $I->amOnPage('/admin/analytics/general-investments');
        $I->see('General Investments');
        $I->see('Platform Investment', '#platform-investments');
    }
}
