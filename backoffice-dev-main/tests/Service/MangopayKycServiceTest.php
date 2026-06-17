<?php

namespace App\Tests\Service;

use App\Entity\Document;
use App\Entity\User;
use App\Entity\UserDocument;
use App\Service\DocumentService;
use App\Service\MangopayKycService;
use App\Test\Util\EntityIdTestUtil;
use MangoPay\ApiKycDocuments;
use MangoPay\ApiUsers;
use MangoPay\EventType;
use MangoPay\KycDocument;
use MangoPay\KycDocumentRefusedReasonType;
use MangoPay\KycDocumentStatus;
use MangoPay\KycDocumentType;
use MangoPay\KycLevel;
use MangoPay\KycPage;
use MangoPay\MangoPayApi;
use MangoPay\PersonType;
use MangoPay\ScopeBlocked;
use MangoPay\UserBlockStatus;
use MangoPay\UserLegalSca;
use MangoPay\UserNaturalSca;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class MangopayKycServiceTest extends KernelTestCase
{
    /** @var MangopayKycService|MockObject $service */
    private $service;

    /** @var MangoPayApi|MockObject $mangoApiStub */
    private $mangoApiStub;

    /** @var DocumentService|MockObject $mangoApiStub */
    private $documentServiceStub;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->mangoApiStub = $this->createStub(MangoPayApi::class);
        $this->mangoApiStub->Users = $this->createStub(ApiUsers::class);
        $this->mangoApiStub->KycDocuments = $this->createStub(ApiKycDocuments::class);

        $this->documentServiceStub = $this->createStub(DocumentService::class);

        $this->service = new MangopayKycService(
            static::getContainer()->get(LoggerInterface::class),
            $this->mangoApiStub,
            $this->documentServiceStub,
        );
    }

    public function testSubmitDocument(): void
    {
        $user = new User();
        $user->setUsername('testKycDocumentSubmit');
        $user->setMangoPayUserId('user_test_' . bin2hex(random_bytes(8)));

        $userDocument = new UserDocument();
        $document = new Document();
        $document->setDocumentUrl('/test/id_doc_' . bin2hex(random_bytes(8)) . '.png');

        $docString = 'id_doc_string_' . bin2hex(random_bytes(8));
        $kycPage = new KycPage();
        $kycPage->File = base64_encode($docString);

        $userDocument->setDocument($document);
        $userDocument->setUser($user);

        $kycDocumentSent = new KycDocument();
        $kycDocumentSent->Type = KycDocumentType::IdentityProof;
        $kycDocumentSent->UserId = $user->getMangoPayUserId();

        $kycDocReturned = clone $kycDocumentSent;
        $kycDocReturned->Id = 'kyc_doc_test_' . bin2hex(random_bytes(8));
        $kycDocReturned->Status = KycDocumentStatus::Created;

        $kycDocSubmitted = clone $kycDocReturned;
        $kycDocSubmitted->Status = KycDocumentStatus::ValidationAsked;

        /** @var ApiUsers|MockObject $apiUsers */
        $apiUsers = $this->mangoApiStub->Users;
        /**
         * Check what createKycDocument should be doing internally
         * by seeing what is passed to CreateKycDocument
         */
        $apiUsers
            ->method('CreateKycDocument')
            ->with($user->getMangoPayUserId(), $kycDocumentSent)
            ->willReturn($kycDocReturned);

        /**
         * Check what addPageToKycDocument should be doing internally
         * by seeing what is passed to CreateKycPage, e.g. reading the file and base64encode
         */
        $this->documentServiceStub
            ->method('read')
            ->with($document->getDocumentUrl(), 'private')
            ->willReturn($docString);
        $apiUsers->method('CreateKycPage')->with(
            $user->getMangoPayUserId(),
            $kycDocReturned->Id,
            $kycPage,
        );

        /**
         * Check submitKycDocument is passing the correct arguments to the API call
         */
        $apiUsers
            ->method('UpdateKycDocument')
            ->with($user->getMangoPayUserId(), $kycDocSubmitted)
            ->willReturn($kycDocSubmitted);

        $actual = $this->service->submitDocument($userDocument);

        $this->assertEquals($user, $actual->subject);
        $this->assertEquals(MangopayKycService::PROVIDER_NAME, $actual->providerName);
        $this->assertEquals($kycDocReturned->Id, $actual->providerReferenceId);
        $this->assertEquals($kycDocReturned->Type, $actual->checkType);
        $this->assertEquals(KycDocumentStatus::ValidationAsked, $actual->result);
        $this->assertEquals('N/A', $actual->score);
        $this->assertFalse($actual->verified);
    }

    public function testSubmitDocumentRefused(): void
    {
        $user = new User();
        $user->setUsername('testKycDocumentSubmit');
        $user->setMangoPayUserId('user_test_' . bin2hex(random_bytes(8)));

        $userDocument = new UserDocument();
        $document = new Document();
        $document->setDocumentUrl('/test/id_doc_' . bin2hex(random_bytes(8)) . '.png');

        $docString = 'id_doc_string_' . bin2hex(random_bytes(8));
        $kycPage = new KycPage();
        $kycPage->File = base64_encode($docString);

        $userDocument->setDocument($document);
        $userDocument->setUser($user);

        $kycDocumentSent = new KycDocument();
        $kycDocumentSent->Type = KycDocumentType::IdentityProof;
        $kycDocumentSent->UserId = $user->getMangoPayUserId();

        $kycDocReturned = clone $kycDocumentSent;
        $kycDocReturned->Id = 'kyc_doc_test_' . bin2hex(random_bytes(8));
        $kycDocReturned->Status = KycDocumentStatus::Created;

        $kycDocSubmitted = clone $kycDocReturned;
        $kycDocSubmitted->Status = KycDocumentStatus::ValidationAsked;

        $kycDocRefused = clone $kycDocSubmitted;
        $kycDocRefused->Status = KycDocumentStatus::Refused;
        $kycDocRefused->RefusedReasonType =
            KycDocumentRefusedReasonType::DocumentHasExpired;
        $kycDocRefused->RefusedReasonMessage = 'Test document has expired';

        /** @var ApiUsers|MockObject $apiUsers */
        $apiUsers = $this->mangoApiStub->Users;

        $apiUsers
            ->method('CreateKycDocument')
            ->with($user->getMangoPayUserId(), $kycDocumentSent)
            ->willReturn($kycDocReturned);

        $this->documentServiceStub
            ->method('read')
            ->with($document->getDocumentUrl(), 'private')
            ->willReturn($docString);
        $apiUsers->method('CreateKycPage')->with(
            $user->getMangoPayUserId(),
            $kycDocReturned->Id,
            $kycPage,
        );

        $apiUsers
            ->method('UpdateKycDocument')
            ->with($user->getMangoPayUserId(), $kycDocSubmitted)
            ->willReturn($kycDocRefused);

        $actual = $this->service->submitDocument($userDocument);

        $this->assertEquals($user, $actual->subject);
        $this->assertEquals(MangopayKycService::PROVIDER_NAME, $actual->providerName);
        $this->assertEquals($kycDocReturned->Id, $actual->providerReferenceId);
        $this->assertEquals($kycDocReturned->Type, $actual->checkType);
        $this->assertEquals(KycDocumentStatus::Refused, $actual->result);
        $this->assertEquals('N/A', $actual->score);
        $this->assertFalse($actual->verified);
        $this->assertStringContainsString(
            $kycDocRefused->RefusedReasonType,
            $actual->note,
        );
        $this->assertStringContainsString(
            $kycDocRefused->RefusedReasonMessage,
            $actual->note,
        );
    }

    public function testViewReport(): void
    {
        $user = new User();
        $user->setUsername('testKycReport');
        $user->setMangoPayUserId('testKycReportCreation');

        $kycUserRegular = new UserNaturalSca('1234');
        $kycUserRegular->KYCLevel = KycLevel::Regular;

        $kycUserLight = new UserLegalSca('5678');
        $kycUserLight->KYCLevel = KycLevel::Light;

        /** @var ApiUsers|MockObject $apiUsers */
        $apiUsers = $this->mangoApiStub->Users;
        $apiUsers
            ->method('GetSca')
            ->with('testKycReportCreation')
            ->willReturnOnConsecutiveCalls($kycUserRegular, $kycUserLight);

        $kycReport = $this->service->viewReport($user, '1234');
        $this->assertSame($user, $kycReport->subject);
        $this->assertEquals(
            MangopayKycService::PROVIDER_NAME,
            $kycReport->providerName,
        );
        // For mangopay, the reference id should equal the mangopay user id
        // They are deliberately saved separately to identify if there is something amiss during recording
        $this->assertEquals('1234', $kycReport->providerReferenceId);
        $this->assertEquals(PersonType::Natural, $kycReport->checkType);
        $this->assertEquals(KycLevel::Regular, $kycReport->result);
        $this->assertEquals('1', $kycReport->score);
        $this->assertTrue($kycReport->verified);

        $notes = 'test_report_' . bin2hex(random_bytes(6));
        $kycReport = $this->service->viewReport($user, '5678', $notes);
        $this->assertSame($user, $kycReport->subject);
        $this->assertEquals(
            MangopayKycService::PROVIDER_NAME,
            $kycReport->providerName,
        );
        $this->assertEquals('5678', $kycReport->providerReferenceId);
        $this->assertEquals(PersonType::Legal, $kycReport->checkType);
        $this->assertEquals(KycLevel::Light, $kycReport->result);
        $this->assertEquals('0', $kycReport->score);
        $this->assertFalse($kycReport->verified);
        $this->assertEquals($notes, $kycReport->note);
    }

    public function testGetAllKYCDocuments(): void
    {
        /** @var ApiKycDocuments|MockObject $apiKycDocuments */
        $apiKycDocuments = $this->mangoApiStub->KycDocuments;
        $apiKycDocuments->method('GetAll')->willReturn([new KycDocument(4)]);

        $result = $this->service->getAllKYCDocuments();
        $this->assertNotEmpty($result);
        $this->assertSame(4, $result[0]->Id);
    }

    public function testGetAllUserKYCDocuments(): void
    {
        /** @var ApiUsers|MockObject $apiUsers */
        $apiUsers = $this->mangoApiStub->Users;
        $apiUsers->method('GetKycDocuments')->with(1)->willReturn([new KycDocument(4)]);

        $result = $this->service->getAllUserKYCDocuments(1);
        $this->assertNotEmpty($result);
        $this->assertSame(4, $result[0]->Id);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('regulatoryStatusReportProvider')]
    public function testCreateUserRegulatoryReport(
        bool $expectedOutcome,
        ScopeBlocked $scopeBlocked,
        string $event,
    ): void {
        $user = EntityIdTestUtil::setEntityId(new User(), 5182);
        $user->setMangoPayUserId('testmpuser_' . bin2hex(random_bytes(8)));
        $regulatoryStatus = new UserBlockStatus($user->getMangoPayUserId());
        $regulatoryStatus->ActionCode = $expectedOutcome ? '' : '008711';
        $regulatoryStatus->ScopeBlocked = $scopeBlocked;

        $result = $this->service->createUserRegulatoryReport(
            $user,
            $event,
            $regulatoryStatus,
        );
        $this->assertEquals($user->getId(), $result->subject->getId());
        $this->assertEquals(MangopayKycService::PROVIDER_NAME, $result->providerName);
        $this->assertEquals($user->getMangoPayUserId(), $result->providerReferenceId);
        $this->assertEquals(
            MangopayKycService::CHECK_TYPE_REGULATORY_STATUS,
            $result->checkType,
        );
        $this->assertEquals(json_encode($scopeBlocked), $result->result);
        $this->assertEquals($regulatoryStatus->ActionCode, $result->score);
        $this->assertEquals($expectedOutcome, $result->verified);
        $this->assertEquals($event, $result->note);
    }

    public static function regulatoryStatusReportProvider(): \Generator
    {
        $unblocked = new ScopeBlocked();
        $unblocked->Inflows = false;
        $unblocked->Outflows = false;

        $inflowBlocked = new ScopeBlocked();
        $inflowBlocked->Inflows = true;
        $inflowBlocked->Outflows = false;

        $outflowBlocked = new ScopeBlocked();
        $outflowBlocked->Inflows = false;
        $outflowBlocked->Outflows = true;

        $allBlocked = new ScopeBlocked();
        $allBlocked->Inflows = true;
        $allBlocked->Outflows = true;

        yield 'All unblocked' => [
            true,
            $unblocked,
            EventType::UserOutflowsUnblocked,
        ];

        yield 'Inflow blocked' => [
            false,
            $inflowBlocked,
            EventType::UserInflowsBlocked,
        ];

        yield 'Outflow blocked' => [
            false,
            $outflowBlocked,
            EventType::UserOutflowsBlocked,
        ];

        yield 'All blocked' => [
            false,
            $allBlocked,
            EventType::UserInflowsBlocked,
        ];
    }
}
