<?php

namespace App\Tests\Functional\Ops\Onboarding;

use App\Tests\Support\FunctionalTester;

class OnboardingDashboardCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    public function checkDashboards(FunctionalTester $I)
    {
        // Main dashboard
        $I->amOnPage('/admin/onboarding');

        $elements = [
            'Id',
            'Name',
            'Username',
            'Signed up',
            'Has Onboarding Profile',
            'Cool Off Accepted',
            'Risk Warning Accepted',
            'Assessment Passed',
            'Category',
            'Category Last Reviewed',
            'Actions',
        ];
        // check table headers
        $I->loopCheckElements($elements, 'thead th');

        $I->click('Categorisations', '#hub-nav');
        $I->seeCurrentUrlEquals('/admin/onboarding/categorisations');
        $I->seeElement('#categorisations-list');
        $I->seeElement('#categorisations-summary');

        $I->click('Assessments', '#hub-nav');
        $I->seeCurrentUrlEquals('/admin/onboarding/assessments');
        $I->seeElement('#assessments-list');
        $I->seeElement('#assessments-summary');

        $I->click('Assessment Question Areas', '#hub-nav');
        $I->seeCurrentUrlEquals('/admin/onboarding/assessments/questions');
        $I->see('Contractual nature', '#question-area-ContractualNature');
        $I->seeLink('Add Question', '/admin/questions/new?area=1&builderMode=1');
        $I->seeElement('#question-area-summary');

        $I->click('Assessment Question Sets', '#hub-nav');
        $I->seeCurrentUrlEquals('/admin/onboarding/assessments/set/1');
        $I->seeElement('#question-set-links');
        $I->see('Question and Choices', '#question-1');

        $I->click('Dashboard', '#hub-nav');
        $I->seeCurrentUrlEquals('/admin/onboarding');

        // Check the experimental exit route modifier
        $I->amOnPage('/admin/questions/new?area=1&builderMode=1');
        $I->seeLink('Back', '/admin/onboarding/assessments/questions');
        $I->seeLink('Cancel', '/admin/onboarding/assessments/questions');
        // Even the edit page will have new back route
        $I->amOnPage('/admin/questions/1');
        $I->seeLink('Back', '/admin/onboarding/assessments/questions');
        // Going to CMS list view will reset things
        $I->amOnPage('/admin/questions');
        $I->amOnPage('/admin/questions/new');
        $I->seeLink('Back', '/admin/questions');
        $I->seeLink('Cancel', '/admin/questions');
        $I->amOnPage('/admin/questions/1');
        $I->seeLink('Back', '/admin/questions');
    }

    public function checkProfileCategorisation(FunctionalTester $I)
    {
        // Main dashboard
        $I->amOnPage('/admin/onboarding/1');

        $startTime = time();

        $I->click('Add Categorisation');
        $I->selectOption('#user_categorisation_category', 'Sophisticated');
        $I->click('Create Categorisation');

        $I->see(
            'Sophisticated',
            '#onboarding-profile [data-field-name="categorisation"]',
        );
        $I->see(
            'No',
            '#categorisations-history tbody tr:first-child [data-field="is-verified"]',
        );
        $I->see(
            '-',
            '#categorisations-history tbody tr:first-child [data-field="verified-by"]',
        );
        $lastReviewed = strtotime($I->grabTextFrom(
            '[data-field-name="last-reviewed-at"]',
        ));
        $I->assertGreaterThanOrEqual($startTime, $lastReviewed);

        $jsonString = json_encode(['fieldexample' => bin2hex(random_bytes(6))]);
        $notes = 'Testing notes ' . bin2hex(random_bytes(6));
        $I->click('Edit', '#categorisations-history tbody tr:first-child');
        $I->seeInField('#user_categorisation_category', 'Sophisticated');
        $I->seeElement('#user_categorisation_category', ['disabled' => 'disabled']);
        $I->fillField('#user_categorisation_details', $jsonString);
        $I->selectOption('#user_categorisation_verified', 'Yes');
        $I->fillField('#user_categorisation_notes', $notes);
        $I->click('Save Changes');

        $I->see(
            'Sophisticated',
            '#onboarding-profile [data-field-name="categorisation"]',
        );
        $I->see(
            'Sophisticated',
            '#categorisations-history tbody tr:first-child [data-field="category"]',
        );
        $I->see(
            $jsonString,
            '#categorisations-history tbody tr:first-child [data-field="details"]',
        );
        $I->see(
            $notes,
            '#categorisations-history tbody tr:first-child [data-field="notes"]',
        );
        $I->see(
            'Yes',
            '#categorisations-history tbody tr:first-child [data-field="is-verified"]',
        );
        $I->see(
            $I::USER_SUPER_ADMIN,
            '#categorisations-history tbody tr:first-child [data-field="verified-by"]',
        );
    }

    public function checkProfileAssessment(FunctionalTester $I)
    {
        // Main dashboard
        $I->amOnPage('/admin/onboarding/1');

        $I->click('Add Assessment');
        $I->seeElement('#user_assessment_passed', ['disabled' => 'disabled']);
        $I->click('Create Assessment');

        $I->see(
            'Pending',
            '#assessments-history tbody tr:first-child [data-field="result"]',
        );

        $notes = 'Testing notes ' . bin2hex(random_bytes(6));
        $I->click('Edit', '#assessments-history tbody tr:first-child');
        $I->dontSeeElement('#user_assessment_passed', ['disabled' => 'disabled']);
        $I->selectOption('#user_assessment_passed', 'No');
        $I->checkOption('#user_assessment_complete');
        $I->fillField('#user_assessment_notes', $notes);
        $I->click('Save Changes');

        $I->see(
            'Fail',
            '#assessments-history tbody tr:first-child [data-field="result"]',
        );
        $I->see(
            'Yes',
            '#assessments-history tbody tr:first-child [data-field="complete"]',
        );
        $I->see(
            $notes,
            '#assessments-history tbody tr:first-child [data-field="notes"]',
        );
    }
}
