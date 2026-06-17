<?php

namespace App\Tests\Entity;

use App\Entity\Investor;
use App\Entity\User;

class InvestorTest extends \PHPUnit\Framework\TestCase
{
    public function testCanInit(): void
    {
        $investor = new Investor();

        $this->assertNotNull($investor);
        $this->assertNull($investor->getUser());
    }

    /*
     * Test user
     */
    public function testUser(): void
    {
        $investor = new Investor();
        $this->assertNull($investor->getUser());

        $user = new User();
        $investor->setUser($user);

        $this->assertNotNull($investor->getUser());
        $this->assertEquals($user, $investor->getUser());
    }

    /*
     * Test cxbWorthInvestor
     */
    public function testCxbWorthInvestor(): void
    {
        $investor = new Investor();

        $this->assertNull($investor->getCxbWorthInvestor());
        $investor->setCxbWorthInvestor(true);

        $this->assertTrue($investor->getCxbWorthInvestor());

        $this->assertNull($investor->getCxbSophisticatedInvestor());
        $this->assertNull($investor->getCxbRestrictedUser());
        $this->assertNull($investor->getCxbLtdCompInvestor());
        $this->assertNull($investor->getCorporateInvestor());
        $this->assertNull($investor->getUser());
    }

    /*
     * Test CxbSophisticatedInvestor
     */
    public function testCxbSophisticatedInvestor(): void
    {
        $investor = new Investor();

        $this->assertNull($investor->getCxbSophisticatedInvestor());
        $investor->setCxbSophisticatedInvestor(true);

        $this->assertTrue($investor->getCxbSophisticatedInvestor());

        $this->assertNull($investor->getCxbWorthInvestor());
        $this->assertNull($investor->getCxbRestrictedUser());
        $this->assertNull($investor->getCxbLtdCompInvestor());
        $this->assertNull($investor->getCorporateInvestor());
        $this->assertNull($investor->getUser());
    }

    /*
     * Test CxbcxbRestrictedUser
     */

    public function testCxbRestrictedUser(): void
    {
        $investor = new Investor();

        $this->assertNull($investor->getCxbRestrictedUser());
        $investor->setCxbRestrictedUser(true);

        $this->assertTrue($investor->getCxbRestrictedUser());

        $this->assertNull($investor->getCxbSophisticatedInvestor());
        $this->assertNull($investor->getCxbWorthInvestor());
        $this->assertNull($investor->getCxbLtdCompInvestor());
        $this->assertNull($investor->getCorporateInvestor());
        $this->assertNull($investor->getUser());
    }

    /*
     * Test cxbLtdCompInvestor
     */

    public function testCxbLtdCompInvestor(): void
    {
        $investor = new Investor();

        $this->assertNull($investor->getCxbLtdCompInvestor());
        $investor->setCxbLtdCompInvestor(true);

        $this->assertTrue($investor->getCxbLtdCompInvestor());

        $this->assertNull($investor->getCxbWorthInvestor());
        $this->assertNull($investor->getCxbSophisticatedInvestor());
        $this->assertNull($investor->getCorporateInvestor());
        $this->assertNull($investor->getCxbRestrictedUser());
        $this->assertNull($investor->getUser());
    }

    /*
     * Test corporateInvestor
     */

    public function testCorporateInvestor(): void
    {
        $investor = new Investor();

        $this->assertNull($investor->getCorporateInvestor());
        $investor->setCorporateInvestor(true);

        $this->assertTrue($investor->getCorporateInvestor());

        $this->assertNull($investor->getCxbLtdCompInvestor());
        $this->assertNull($investor->getCxbWorthInvestor());
        $this->assertNull($investor->getCxbSophisticatedInvestor());
        $this->assertNull($investor->getCxbRestrictedUser());
        $this->assertNull($investor->getUser());
    }

    /*
     * Test alwaysGoUp
     */
    public function testAlwaysGoUp(): void
    {
        $investor = new Investor();

        $this->assertNull($investor->getAlwaysGoUp());
        $investor->setAlwaysGoUp(true);

        $this->assertTrue($investor->getAlwaysGoUp());
    }

    /*
     * Test IncomeEveryMonth
     */
    public function testIncomeEveryMonth(): void
    {
        $investor = new Investor();

        $this->assertNull($investor->getIncomeEveryMonth());
        $investor->setIncomeEveryMonth(true);

        $this->assertTrue($investor->getIncomeEveryMonth());
    }

    /*
     * Test NeverExit
     */
    public function testNeverExit(): void
    {
        $investor = new Investor();

        $this->assertNull($investor->getNeverExit());
        $investor->setNeverExit(true);

        $this->assertTrue($investor->getNeverExit());
    }

    /*
     * Test PoiFileId
     */
    public function testPoiFileId(): void
    {
        $investor = new Investor();

        $test_value = 100;
        $this->assertNotNull($test_value);

        $this->assertNull($investor->getPoiFileId());
        $investor->setPoiFileId($test_value);

        $this->assertEquals($test_value, $investor->getPoiFileId());
    }

    /*
     * Test WordsOfOwn
     */
    public function testWordsOfOwn(): void
    {
        $investor = new Investor();

        $test_value = 'Hello World';
        $this->assertNotNull($test_value);

        $this->assertNull($investor->getWordsOfOwn());
        $investor->setWordsOfOwn($test_value);

        $this->assertEquals($test_value, $investor->getWordsOfOwn());
    }
}
