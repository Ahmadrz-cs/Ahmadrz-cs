<?php

namespace App\Tests\Repository;

use App\Entity\ContegoLog;
use App\Entity\User;
use App\Repository\ContegoLogRepository;
use App\Test\FixtureTestCase;

class ContegoLogRepositoryTest extends FixtureTestCase
{
    private ContegoLogRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->entityManager->getRepository(ContegoLog::class);
    }

    public function testCreateIfValid(): void
    {
        $testcontego = $this->getValidContegoLog();
        $fieldValue = $testcontego->getProfileName();
        $this->repository->save($testcontego, true);

        $createdTestContego = $this->getCreatedLog($fieldValue);
        $this->compareObjects($testcontego, $createdTestContego);
    }

    private function getValidContegoLog(): ContegoLog
    {
        $con_log = new ContegoLog();
        $con_log->setProfileName('AML Check');
        $con_log->setExtReferenceId(12345);
        $con_log->setRAG('RED');
        $con_log->setKycScore(175);
        $con_log->setKycType('Person Check');
        $con_log->setPdfReportUrl('http://');
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_ADMIN]);
        $con_log->setUser($user);
        return $con_log;
    }

    private function getCreatedLog(string $profileName): ?ContegoLog
    {
        $con_log = $this->repository->findOneBy(['profile_name' => $profileName]);
        return $con_log;
    }

    public function compareObjects(ContegoLog $comparelog, ContegoLog $createdLog): void
    {
        $this->assertEquals($comparelog, $createdLog);
        $this->assertEquals(
            $comparelog->getProfileName(),
            $createdLog->getProfileName(),
        );
        $this->assertEquals($comparelog->getRAG(), $createdLog->getRAG());
        $this->assertEquals($comparelog->getKycType(), $createdLog->getKycType());
        $this->assertEquals(
            $comparelog->getExtReferenceId(),
            $createdLog->getExtReferenceId(),
        );
        $this->assertEquals($comparelog->getUser(), $createdLog->getUser());
        $this->assertEquals($comparelog->getKycScore(), $createdLog->getKycScore());
        $this->assertEquals(
            $comparelog->getPdfReportUrl(),
            $createdLog->getPdfReportUrl(),
        );
    }
}
