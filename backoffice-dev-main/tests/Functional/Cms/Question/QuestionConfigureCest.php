<?php

namespace App\Tests\Functional\Cms\Question;

use App\Tests\Support\FunctionalTester;

class QuestionConfigureCest
{
    public function _before(FunctionalTester $I)
    {
        $I->loginAdmin();
    }

    /**
     * @group detailview
     */
    public function createAndConfigureQuestion(FunctionalTester $I)
    {
        $uniqueSequence = bin2hex(random_bytes(8));
        $I->amOnPage('/admin/questions');
        $I->seeLink(
            'Go to Assessment Builder',
            '/admin/onboarding/assessments/questions',
        );
        $I->click('Create Question');
        $I->fillField(
            '#question_content',
            "Automated test creating new question {$uniqueSequence}",
        );
        $I->selectOption('#question_section', 'ContractualNature');
        $I->click('Create Question');

        // Check newly created question
        $id = $I->grabTextFrom('[data-field-name="question-id"]');
        $I->seeCurrentUrlEquals("/admin/questions/{$id}");
        $I->see('Appropriateness', '[data-field-name="question-type"]'); // This is the default question type
        $I->see('Contractual nature', '[data-field-name="section"]');
        $I->see(
            "Automated test creating new question {$uniqueSequence}",
            '[data-field-name="question-text"]',
        );
        $I->see('No', '[data-field-name="active"]');

        // Add some question choices
        $I->createQuestionChoice("Automated test choice {$uniqueSequence}_1");
        $I->see(
            "Automated test choice {$uniqueSequence}_1",
            '#choices-list tbody tr:first-child [data-field="content"]',
        );
        $I->see('Yes', '#choices-list tbody tr:first-child [data-field="active"]');
        $I->see('No', '#choices-list tbody tr:first-child [data-field="correct"]');

        $I->createQuestionChoice(
            "Automated test choice {$uniqueSequence}_2",
            active: false,
            correct: true,
        );
        $I->see(1, '[data-field-name="correct-choices"]');
        $I->see('No', '#choices-list tbody tr:nth-child(2) [data-field="active"]');
        $I->see('Yes', '#choices-list tbody tr:nth-child(2) [data-field="correct"]');

        // Make changes to created question
        $I->click('Edit Question');
        $I->seeCurrentUrlEquals("/admin/questions/{$id}/edit");
        $I->fillField(
            '#question_content',
            "Automated test edited question {$uniqueSequence}",
        );
        $I->selectOption('#question_section', 'None');
        $I->selectOption('#question_questionType', 'Aml');
        $I->checkOption('#question_active');
        $I->click('Save Changes');

        $I->seeCurrentUrlEquals("/admin/questions/{$id}");
        $I->see('Aml', '[data-field-name="question-type"]'); // This is the default question type
        $I->see('None', '[data-field-name="section"]');
        $I->see(
            "Automated test edited question {$uniqueSequence}",
            '[data-field-name="question-text"]',
        );
        $I->see('Yes', '[data-field-name="active"]');
    }
}
