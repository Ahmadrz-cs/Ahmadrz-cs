<?php


class PageAboutUsCest
{
    public function _before(AcceptanceTester $I)
    {
        $I->amOnPage('/about-us');
    }

    public function _after(AcceptanceTester $I)
    {
    }

    /**
     * @group about_us
     */
    public function checkTeamProfiles(\Step\Acceptance\StaticPages $I)
    {
        $I->seeElement(".hero-banner");
        $I->seeElement("#team");

        $I->scrollTo("#team");
        // $I->seeNumberOfElements("#team .team-card", 6);
    }

    /**
     * @group about_us
     */
    public function checkOurStory(\Step\Acceptance\StaticPages $I)
    {
        $I->scrollTo(".storywrap");
        $I->wait(0.5);
        for ($number = 1; $number <= 9; $number++) {
            $story = (".story-$number");
            $I->waitForElement($story);
            $I->scrollTo($story);
        }
    }
}
