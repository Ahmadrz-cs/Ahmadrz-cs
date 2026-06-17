<?php

namespace App\Tests\Service\Email;

use App\Test\MailcatcherTestCase;

class InvestmentManagerV2EmailTest extends MailcatcherTestCase
{
    /** @var \App\Service\Manager\InvestmentManagerV2 */
    private $investmentManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|\App\Service\MangoPay */
    private $mockMangopay;

    protected function setUp(): void
    {
        parent::setUp();

        $transferSuccess = new \MangoPay\Transfer();
        $transferSuccess->CreditedFunds = new \MangoPay\Money();
        $transferSuccess->CreditedFunds->Currency = 'GBP';
        $transferSuccess->Id = 1234;
        $transferSuccess->Status = 'SUCCEEDED';
        $wallet = new \MangoPay\Wallet();
        $wallet->Owners = [1111];
        $mockedMangopay = $this->createMock(\App\Service\MangoPay::class);

        /** @var \PHPUnit\Framework\MockObject\MockObject $mockedMangopay */
        $mockedMangopay
            ->expects($this->any())
            ->method('createGenericTransfer')
            ->willReturn($transferSuccess);
        $mockedMangopay
            ->expects($this->any())
            ->method('getSingleWallet')
            ->willReturn($wallet);

        /**
         * Any call to createGenericTransfer() will return a successful mp transfer object
         * Any call to getSingleWallet() will return a mp wallet object
         * @var \App\Service\MangoPay $mockedMangopay
         */
        $this->mockMangopay = $mockedMangopay;

        $this->investmentManager = new \App\Service\Manager\InvestmentManagerV2(
            static::getContainer()->get(\App\Repository\InvestmentRepository::class),
            static::getContainer()->get(\App\Repository\InvestmentDocumentRepository::class),
            static::getContainer()->get(\App\Repository\UserRepository::class),
            static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class),
            static::getContainer()->get(\Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface::class),
            static::getContainer()->get(\Symfony\Bundle\SecurityBundle\Security::class),
            static::getContainer()->get(\App\Dto\DocumentAssembler::class),
            static::getContainer()->get(\App\Service\Manager\DocumentManager::class),
            static::getContainer()->get(\App\Dto\InvestmentAssembler::class),
            static::getContainer()->get(\App\Service\MailerService::class),
            $this->mockMangopay,
            static::getContainer()->get(\Psr\Log\LoggerInterface::class),
        );
    }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testSendInvestmentCreatedMail(): void
    {
        $investment = $this->searchFixtures(
            \App\Entity\Investment::class,
            [],
            false,
            false,
        )[0];

        $this->investmentManager->sendInvestmentCreatedMail($investment);

        $emails = $this->getMessages();
        $this->assertEquals('Thank You for Investing', $emails[0]->subject);
        $this->assertEquals(
            '<' . $investment->getUser()->getEmail() . '>',
            $emails[0]->recipients[0],
        );
        $this->assertEquals('[Admin] Thank You for Investing', $emails[1]->subject);
        $this->assertEquals('<adminteam@yielders.co.uk>', $emails[1]->recipients[0]);
    }

    // #[\PHPUnit\Framework\Attributes\Group('email')]
    // public function testAddInvestmentStampDutyIsAdded(): void
    // {
    //     $offerings = $this->searchFixtures(
    //         \App\Entity\Offering::class,
    //         ["type" => 'retail', "additionalType" => ""],
    //         false,
    //         false
    //     );
    //     $user = $this->searchFixtures(
    //         \App\Entity\User::class,
    //         ["status" => 'approved'],
    //         false,
    //         false
    //     )[0];

    //     foreach ($offerings as $off) {
    //         //choose an offering in regular asset
    //         $asset = $off->getAsset();
    //         if ($asset->getAdditionalType() == "") {
    //             $offering = $off;
    //         }
    //     }

    //     //calculate the total investment value inclduing the stamp duty. This will be checked in the mocked method createGenericTransfer
    //     $numberOfShares = 2000;
    //     $investmentValue = $offering->getPricePerShare() * $numberOfShares;
    //     if ($investmentValue > 999) {
    //         $investmentValue += ceil($investmentValue / 1000) * 5;
    //     }

    //     $transferSuccess = new \MangoPay\Transfer();
    //     $transferSuccess->CreditedFunds = new \MangoPay\Money();
    //     $transferSuccess->CreditedFunds->Currency = 'GBP';
    //     $transferSuccess->Id = 1234;
    //     $transferSuccess->Status = 'SUCCEEDED';

    //     //check tag passed to createGenericTransfer is correct
    //     $mpTag = "AstName:" . $offering->getAsset()->getName() . ";AstCode:" . $offering->getAsset()->getCompanyNumber() . ';Type:Investment';
    //     $this->mockMangopay->expects($this->once())
    //         ->method('createGenericTransfer')
    //         ->with($user->getMangoPayUserId(), $user->getMangoPayWalletId(), $offering->getAsset()->getMangoPayWalletId(), $investmentValue, 0, $mpTag)
    //         ->willReturn($transferSuccess);

    //     $investmentDTO = new \App\Dto\InvestmentPostDTO(
    //         $offering->getId(),
    //         $numberOfShares,
    //         'normal',
    //         $user->getId(),
    //         0,
    //         0,
    //         '',
    //         ''
    //     );
    //     $this->investmentManager->addInvestment($investmentDTO);

    //     //check investment confirmation emails are sent
    //     $emails = $this->getMessages();
    //     $this->assertEquals("Thank You for Investing", $emails[0]->subject);
    //     $this->assertEquals('<' . $user->getEmail() . '>', $emails[0]->recipients[0]);
    //     $this->assertEquals("[Admin] Thank You for Investing", $emails[1]->subject);
    //     $this->assertEquals('<adminteam@yielders.co.uk>', $emails[1]->recipients[0]);
    // }

    // #[\PHPUnit\Framework\Attributes\Group('email')]
    // public function testAddInvestmentStampDutyIsExempt(): void
    // {
    //     $offering = $this->searchFixtures(
    //         \App\Entity\Offering::class,
    //         ["type" => 'prefunding'],
    //         false,
    //         false
    //     )[0];
    //     $user = $this->searchFixtures(
    //         \App\Entity\User::class,
    //         ["status" => 'approved'],
    //         false,
    //         false
    //     )[0];

    //     //calculate the total investment value excluding stamp duty. This will be checked in the mocked method createGenericTransfer
    //     $numberOfShares = 2000;
    //     $investmentValue = $offering->getPricePerShare() * $numberOfShares;

    //     $transferSuccess = new \MangoPay\Transfer();
    //     $transferSuccess->CreditedFunds = new \MangoPay\Money();
    //     $transferSuccess->CreditedFunds->Currency = 'GBP';
    //     $transferSuccess->Id = 1234;
    //     $transferSuccess->Status = 'SUCCEEDED';

    //     //check tag passed to createGenericTransfer is correct
    //     $mpTag = "AstName:" . $offering->getAsset()->getName() . ";AstCode:" . $offering->getAsset()->getCompanyNumber() . ';Type:Investment';
    //     $this->mockMangopay->expects($this->once())
    //         ->method('createGenericTransfer')
    //         ->with($user->getMangoPayUserId(), $user->getMangoPayWalletId(), $offering->getAsset()->getMangoPayWalletId(), $investmentValue, 0, $mpTag)
    //         ->willReturn($transferSuccess);

    //     $investmentDTO = new \App\Dto\InvestmentPostDTO(
    //         $offering->getId(),
    //         $numberOfShares,
    //         'normal',
    //         $user->getId(),
    //         null,
    //         null,
    //         null,
    //         null
    //     );
    //     $this->investmentManager->addInvestment($investmentDTO);

    //     //check investment confirmation emails are sent
    //     $emails = $this->getMessages();
    //     $this->assertEquals("Thank You for Investing", $emails[0]->subject);
    //     $this->assertEquals('<' . $user->getEmail() . '>', $emails[0]->recipients[0]);
    //     $this->assertEquals("[Admin] Thank You for Investing", $emails[1]->subject);
    //     $this->assertEquals('<adminteam@yielders.co.uk>', $emails[1]->recipients[0]);
    // }

    #[\PHPUnit\Framework\Attributes\Group('email')]
    public function testSendInvestmentSettledEmail(): void
    {
        $investment = $this->searchFixtures(
            \App\Entity\Investment::class,
            [],
            false,
            false,
        )[0];

        $this->investmentManager->sendInvestmentSettledEmail($investment);

        $emails = $this->getMessages();
        $this->assertEquals('Investment Settled', $emails[0]->subject);
        $this->assertEquals(
            '<' . $investment->getUser() . '>',
            $emails[0]->recipients[0],
        );
    }
}
