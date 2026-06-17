<?php

namespace App\Tests\Functional\Cms\Users;

use App\Entity\Enum\AccountFeature;
use App\Tests\Support\FunctionalTester;

class UserDashboardCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group detailview
     */
    public function checkDashboardOverview(FunctionalTester $I)
    {
        $I->amOnPage('/admin/users/1/dashboard');
        $I->seeElement('section#kyc-overview');
        $I->seeLink('Edit User', '/admin/users/1/edit');
        $I->seeLink('Edit User Role', '/admin/users/1/roles');
        $I->seeLink('View Wallet Transactions');

        $I->seeElement('section#kyc-overview');
        $I->seeLink('Review Mangopay and Contego KYC', '/admin/users/1/dashboard/kyc');
    }

    public function checkOnboardingSection(FunctionalTester $I)
    {
        $id = $I->getUserIdByUsername($I::USER_REG1);
        $I->amOnPage("/admin/users/{$id}/dashboard/onboarding");

        // check section headings
        $I->see('About User', '#user-common');
        $I->see('Onboarding Profile', '#onboarding-profile');
        $I->see('Self Categorisation', '#self-categorisation');
        $I->see('Appropriateness Assessments', '#assessments');

        // $I->seeLink(
        //     'Toggle Manual KYC Verification',
        //     "/admin/users/{$id}/toggle-kyc-verified?redirectRoute=admin_user_dashboard_kyc"
        // );

        // Profile states
        $elements = [
            'Has Onboarding Profile',
            'Cool Off Accepted',
            'Cool Off End',
            'Risk Warning Accepted',
            'Assessment Passed',
            'Categorisation',
            'Features Unlocked',
            'Real Estate Development - Plan',
            'Real Estate Development - Build',
        ];
        $I->loopCheckElements($elements, '#onboarding-profile');

        // Check the additional features update appropriately
        $I->see('No', '[data-field-name="real-estate-development---plan"]');
        $I->see('No', '[data-field-name="real-estate-development---build"]');
        $obpId = $I->grabFromDatabase('users', 'onboardingProfile_id', ['id' => $id]);
        $I->updateInDatabase(
            'onboarding_profile',
            [
                'realEstatePlanAccess' => 1,
                'realEstateBuildAccess' => 1,
            ],
            ['id' => $obpId],
        );
        $I->amOnPage("/admin/users/{$id}/dashboard/onboarding");
        $I->see('Yes', '[data-field-name="real-estate-development---plan"]');
        $I->see('Yes', '[data-field-name="real-estate-development---build"]');
        // Clear the features unlocked
        $I->updateInDatabase(
            'onboarding_profile',
            [
                'realEstatePlanAccess' => 0,
                'realEstateBuildAccess' => 0,
            ],
            ['id' => $obpId],
        );
        $I->amOnPage("/admin/users/{$id}/dashboard/onboarding");
        $I->see('No', '[data-field-name="real-estate-development---plan"]');
        $I->see('No', '[data-field-name="real-estate-development---build"]');

        // Self categorisation section
        $elements = [
            'Id',
            'Category',
            'Details',
            'Staff Notes',
            'Manually Verified',
            'Verified By',
            'Created By',
            'Created',
            'Last Updated',
            'Actions',
        ];
        $I->loopCheckElements($elements, 'table#categorisations-history thead th');
        $I->seeElement('[data-field-name="times-categorised"]');
        $I->seeElement('[data-field-name="last-reviewed-at"]');

        // Assessment table section
        $elements = [
            'Id',
            'Result',
            'Responses',
            'Expiry',
            'Notes',
            'Created By',
            'Created',
            'Last Updated',
            'Actions',
        ];
        $I->loopCheckElements($elements, 'table#assessments-history thead th');
        $I->seeElement('[data-field-name="assessments-taken"]');
    }

    /**
     * @group detailview
     */
    public function checkKycSection(FunctionalTester $I)
    {
        $id = $I->getUserIdByUsername($I::USER_REG1);
        $I->amOnPage("/admin/users/{$id}/dashboard/kyc");
        $I->seeLink(
            'Edit Restrictions',
            "/admin/users/{$id}/dashboard/kyc/restrictions",
        );
        $I->seeLink(
            'Select Doc for Mangopay KYC Check',
            "/admin/users/{$id}/dashboard/documents",
        );
        $I->seeLink(
            'View All Mangopay KYC Documents',
            "/admin/kyc/mangopay/documents/{$id}",
        );

        // check section headings
        $I->see('KYC Overview', '#kyc-overview');
        $I->see('Mangopay', '#mangopay-kyc');
        $I->see('Contego', '#contego-kyc');
        $I->see('KYC Reviews', '#kyc-reviews');
        $I->see('KYC Reports', '#kyc-reports');

        $I->seeLink(
            'Toggle Manual KYC Verification',
            "/admin/users/{$id}/toggle-kyc-verified?redirectRoute=admin_user_dashboard_kyc",
        );

        // Limits table headings
        $elements = [
            'Max User Balance',
            'Pay-ins',
            'Transfers',
            'Cumulated Payout Per Calandar Month',
        ];
        $I->loopCheckElements($elements, 'table#mangopay-limits thead th');

        // Doc table headings
        $elements = [
            'Id',
            'Type',
            'Status',
            'Flags',
            'Refused Reason',
            'Created On',
            'Processed On',
        ];
        $I->loopCheckElements($elements, 'table#mangopay-kyc-docs thead th');

        // Mangopay info/stats
        $I->seeElement('[data-field-name="kyc-level"]');
        $I->seeElement('[data-field-name="person-type"]');
        $I->seeElement('[data-field-name="user-category"]');
        $I->seeElement('[data-field-name="terms-accepted"]');
        $I->seeElement('[data-field-name="regulatory-action-code"]');
        $I->seeElement('[data-field-name="regulatory-blocked-inflows"]');
        $I->seeElement('[data-field-name="regulatory-blocked-outflows"]');

        // Contego info/stats
        $I->seeElement('[data-field-name="rag"]');
        $I->seeElement('[data-field-name="score"]');
        $I->seeElement('[data-field-name="contego-check-created-at"]');

        // Check permissions/restrictions system
        $currentVerificationStatus = trim($I->grabTextFrom(
            '[data-field-name="kyc-verification-state"]',
        ));
        if ($currentVerificationStatus == 'Verified') {
            $I->click('Toggle Manual KYC Verification');
        }
        $I->see('No', '[data-field-name="can-buy"]');
        $I->see('No', '[data-field-name="can-sell"]');
        $I->see('No', '[data-field-name="can-deposit"]');
        $I->see('No', '[data-field-name="can-withdraw"]');

        $I->click('Toggle Manual KYC Verification');
        $I->see('Yes', '[data-field-name="can-buy"]');
        $I->see('Yes', '[data-field-name="can-sell"]');
        $I->see('Yes', '[data-field-name="can-deposit"]');
        $I->see('Yes', '[data-field-name="can-withdraw"]');

        $I->click('Edit Restrictions');
        $I->seeCurrentUrlEquals("/admin/users/{$id}/dashboard/kyc/restrictions");
        $I->seeLink('Discard Changes', "/admin/users/{$id}/dashboard/kyc");
        $I->checkOption('#kyc_restrictions_buyRestricted');
        $I->checkOption('#kyc_restrictions_sellRestricted');
        $I->checkOption('#kyc_restrictions_depositRestricted');
        $I->checkOption('#kyc_restrictions_withdrawRestricted');
        $I->click('Save Changes');
        $I->seeCurrentUrlEquals("/admin/users/{$id}/dashboard/kyc");

        $I->see('No (overridden)', '[data-field-name="can-buy"]');
        $I->see('No (overridden)', '[data-field-name="can-sell"]');
        $I->see('No (overridden)', '[data-field-name="can-deposit"]');
        $I->see('No (overridden)', '[data-field-name="can-withdraw"]');

        // If you mark verified as false, the "overridden" indicator will remain to show customisations exist
        $I->click('Toggle Manual KYC Verification');
        $I->see('No (overridden)', '[data-field-name="can-buy"]');
        $I->see('No (overridden)', '[data-field-name="can-sell"]');
        $I->see('No (overridden)', '[data-field-name="can-deposit"]');
        $I->see('No (overridden)', '[data-field-name="can-withdraw"]');
        $I->click('Toggle Manual KYC Verification');

        // Undo the restriction for easier reruns
        $I->amOnPage("/admin/users/{$id}/dashboard/kyc/restrictions");
        $I->uncheckOption('#kyc_restrictions_buyRestricted');
        $I->uncheckOption('#kyc_restrictions_sellRestricted');
        $I->uncheckOption('#kyc_restrictions_depositRestricted');
        $I->uncheckOption('#kyc_restrictions_withdrawRestricted');
        $I->click('Save Changes');

        $I->see('Yes', '[data-field-name="can-buy"]');
        $I->see('Yes', '[data-field-name="can-sell"]');
        $I->see('Yes', '[data-field-name="can-deposit"]');
        $I->see('Yes', '[data-field-name="can-withdraw"]');

        // Check syncing of KYC reports
        $startCount = count($I->grabMultiple('#kyc-reports-list tbody tr'));
        $I->click('Sync Mangopay KYC');
        $I->seeCurrentUrlEquals("/admin/users/{$id}/dashboard/kyc");
        $endCount = count($I->grabMultiple('#kyc-reports-list tbody tr'));
        $I->assertEquals($startCount + 1, $endCount);
        $I->see('KycReport created ID#');
        $newReportId = $I->grabAttributeFrom(
            '#kyc-reports-list tbody tr:last-child',
            'data-object-id',
        );
        // Resyncing should not create another report
        $I->click('Sync Mangopay KYC');
        $endCount2 = count($I->grabMultiple('#kyc-reports-list tbody tr'));
        $I->assertEquals($startCount + 1, $endCount2);
        $I->see('No new KycReport was needed');
        // Nullify to allow reruns
        $I->updateInDatabase(
            'kyc_report',
            ['providerName' => 'test_nullification'],
            ['id' => $newReportId],
        );
    }

    /**
     * @group detailview
     */
    public function checkKycSectionMangopayNotVerified(FunctionalTester $I)
    {
        $I->amOnPage('/admin/users');
        $id = $I->grabFromDatabase('users', 'id', ['mangoPayUserId' => null]);
        $I->amOnPage("/admin/users/{$id}/dashboard/kyc");
        foreach (range(1, 4) as $column) {
            $I->see('£0', "table#mangopay-limits tbody tr td:nth-child({$column})");
        }
        $I->see('N/A', '[data-field-name="kyc-level"]');
        $I->see('N/A', '[data-field-name="person-type"]');
        $I->see('N/A', '[data-field-name="user-category"]');
        $I->see('No', '[data-field-name="terms-accepted"]');
    }

    /**
     * @group detailview
     */
    public function checkKycSectionContegoNotVerified(FunctionalTester $I)
    {
        $I->amOnPage('/admin/users');
        $id = $I->grabFromDatabase('users', 'id', ['contego_score_id' => null]);

        $I->amOnPage("/admin/users/{$id}/dashboard/kyc");
        $I->seeElement('[data-field-name="rag"]');
        $I->seeElement('[data-field-name="score"]');
        $I->see('N/A', '[data-field-name="contego-check-created-at"]');
    }

    public function checkDocumentSection(FunctionalTester $I)
    {
        $id = $I->getUserIdByUsername($I::USER_REG1);
        $I->amOnPage("/admin/users/{$id}/dashboard/documents");
        $docId = $I->grabTextFrom('#documents tr:first-child [data-field="id"]');
        $I->seeLink('Download', "/admin/document/{$docId}/download?type=private");
        $I->seeLink(
            'Create Mangopay KYC Check',
            "/admin/kyc/mangopay/check/document/{$docId}",
        );

        $I->click('Create Mangopay KYC Check', '#documents tr:first-child');
        $I->seeElement('#document-info');
    }

    public function checkPortfolioSection(FunctionalTester $I)
    {
        $id = $I->getUserIdByUsername($I::USER_REG1);
        $I->amOnPage("/admin/users/{$id}/dashboard/portfolio");

        $I->seeElement('#trade-portfolio');
        $I->seeElement('#trade-prefunding');
        $I->seeElement('#performance');
    }

    public function checkTradeOrdersSection(FunctionalTester $I): void
    {
        $id = $I->getUserIdByUsername($I::USER_REG1);
        $I->amOnPage("/admin/users/{$id}/dashboard/trade-orders");

        // Note the table columns and filters differ between generic, user, product views
        $elements = [
            'Id',
            'Type',
            'Direction',
            'Asset',
            'Progress',
            'Quantity Available',
            'Quantity Traded',
            'Quantity Listed',
            'Price',
            'Derived Value',
            'Fees',
            'Taxes',
            'Status',
            'Created',
        ];
        // check table headers
        $I->loopCheckElements($elements, 'thead th');

        $filterLabels = [
            'Trade order id',
            'Asset id',
            'Asset name',
            'Direction',
            'Status',
            'Type',
            'CreatedAt Start',
            'CreatedAt End',
            'Items Per Page',
            'Order by',
            'Order Direction',
        ];
        // check table filters
        $I->loopCheckElements($filterLabels, '//form//label|//form//legend');
    }

    public function checkShareTradesSection(FunctionalTester $I): void
    {
        $id = $I->getUserIdByUsername($I::USER_REG1);
        $I->amOnPage("/admin/users/{$id}/dashboard/share-trades");

        // Note the table columns and filters differ between generic, user, product views
        $elements = [
            'Id',
            'Sell Order',
            'Buy Order',
            'Seller',
            'Buyer',
            'Quantity',
            'Price',
            'Value',
            'Fees',
            'Taxes',
            'Status',
            'Created',
        ];
        // check table headers
        $I->loopCheckElements($elements, 'thead th');

        $filterLabels = [
            'Share trade id',
            'Asset id',
            'Asset name',
            'Sell Order id',
            'Seller id',
            'Seller username',
            'Buy order id',
            'Buyer id',
            'Buyer username',
            'Sell order type',
            'Buy order type',
            'Status',
            'CreatedAt Start',
            'CreatedAt End',
            'Items Per Page',
            'Order by',
            'Order Direction',
        ];
        // check table filters
        $I->loopCheckElements($filterLabels, '//form//label|//form//legend');
    }
}
