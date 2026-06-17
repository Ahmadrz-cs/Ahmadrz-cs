<?php

namespace App\Tests\Service\Manager;

use App\Entity\Document;
use App\Entity\Investment;
use App\Entity\InvestmentDocuments;
use App\Entity\User;
use App\Service\Manager\DocumentManager;
use App\Service\Manager\InvestmentDocumentManagerV2;
use App\Test\Util\EntityIdTestUtil;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class InvestmentDocumentManagerV2Test extends KernelTestCase
{
    private InvestmentDocumentManagerV2 $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = static::getContainer()->get(InvestmentDocumentManagerV2::class);
    }

    public function testCreateShareCertificate(): void
    {
        /** @var User $user */
        $user = EntityIdTestUtil::setEntityId(new User(), 1768);

        /** @var Investment $investment */
        $investment = EntityIdTestUtil::setEntityId(new Investment(), 755);

        $input = new InvestmentDocuments();
        $input->setDocument(new Document());
        $input->setInvestment($investment);

        /** @var \PHPUnit\Framework\MockObject\MockObject $documentManagerMock */
        $documentManagerMock = $this->createMock(DocumentManager::class);
        $documentManagerMock
            ->expects($this->once())
            ->method('linkDocument')
            ->with(
                $this->isInstanceOf(Document::class),
                $this->isNull(),
                $this->equalTo('private'),
                $this->equalTo('investment/755'),
            )
            ->willReturn($input->getDocument());

        /** @var DocumentManager $documentManagerMock */
        $service = new InvestmentDocumentManagerV2($documentManagerMock);
        $actual = $service->createShareCertificate($input, $user);
        $this->assertEquals('share_certificate', $actual->getDocument()->getTag());
        $this->assertEquals(1768, $actual->getDocument()->getCreatedById());
    }
}
