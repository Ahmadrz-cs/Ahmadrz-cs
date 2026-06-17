<?php

/**
 * Created by PhpStorm.
 * User: plok
 * Date: 14/01/17
 * Time: 21:36
 */

namespace App\Tests\Repository;

use App\Entity\Asset;
use App\Entity\AssetMember;
use App\Entity\User;
use App\Repository\AssetMemberRepository;
use App\Test\FixtureTestCase;

class AssetMemberRepositoryTest extends FixtureTestCase
{
    private AssetMemberRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->entityManager->getRepository(AssetMember::class);
    }

    public function testAddMemberNormal(): void
    {
        /** @var Asset $asset */
        $asset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Neptunis Quays - Bristol']);

        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_ADMIN]);

        $assetMember = new AssetMember();
        $assetMember->setUser($user);
        $assetMember->setAsset($asset);
        $assetMember->setMembertype(AssetMember::MEMBER_TYPE_NORMAL);
        $this->repository->save($assetMember, true);

        /** @var AssetMember $assetMemberCheck */
        $assetMemberCheck = $this->repository->find($assetMember->getId());

        $this->assertNotNull($assetMemberCheck);
        $this->assertNotNull($assetMemberCheck->getAsset());
        $this->assertNotNull($assetMemberCheck->getUser());
        $this->assertEquals(
            $assetMemberCheck->getMembertype(),
            AssetMember::MEMBER_TYPE_NORMAL,
        );
    }

    public function testAddMemberAuthor(): void
    {
        /** @var Asset $asset */
        $asset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Neptunis Quays - Bristol']);

        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_ADMIN]);

        $assetMember = new AssetMember();
        $assetMember->setUser($user);
        $assetMember->setAsset($asset);
        $assetMember->setMembertype(AssetMember::MEMBER_TYPE_AUTHOR);
        $this->repository->save($assetMember, true);

        /** @var AssetMember $assetMemberCheck */
        $assetMemberCheck = $this->repository->find($assetMember->getId());

        $this->assertNotNull($assetMemberCheck);
        $this->assertNotNull($assetMemberCheck->getAsset());
        $this->assertNotNull($assetMemberCheck->getUser());
        $this->assertEquals(
            $assetMemberCheck->getMembertype(),
            AssetMember::MEMBER_TYPE_AUTHOR,
        );
    }

    public function testAddMemberPointOfContact(): void
    {
        /** @var Asset $asset */
        $asset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Neptunis Quays - Bristol']);

        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_ADMIN]);

        $assetMember = new AssetMember();
        $assetMember->setUser($user);
        $assetMember->setAsset($asset);
        $assetMember->setMembertype(AssetMember::MEMBER_TYPE_POINT_CONTACT);
        $this->repository->save($assetMember, true);

        /** @var AssetMember $assetMemberCheck */
        $assetMemberCheck = $this->repository->find($assetMember->getId());

        $this->assertNotNull($assetMemberCheck);
        $this->assertNotNull($assetMemberCheck->getAsset());
        $this->assertNotNull($assetMemberCheck->getUser());
        $this->assertEquals(
            $assetMemberCheck->getMembertype(),
            AssetMember::MEMBER_TYPE_POINT_CONTACT,
        );
    }

    public function testAddMemberMultipe(): void
    {
        /** @var Asset $asset */
        $asset = $this->entityManager
            ->getRepository(Asset::class)
            ->findOneBy(['name' => 'Neptunis Quays - Bristol']);

        /** @var User $user */
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_ADMIN]);

        /** @var User $user2 */
        $user2 = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_FINOPS]);

        /** @var User $user3 */
        $user3 = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['username' => self::USER_VENDOR]);

        $originalMemberCount = count($this->repository->findBy(['asset' =>
            $asset->getId()]));

        $assetMember1 = new AssetMember();
        $assetMember1->setUser($user);
        $assetMember1->setAsset($asset);
        $assetMember1->setMembertype(AssetMember::MEMBER_TYPE_POINT_CONTACT);
        $this->repository->save($assetMember1, true);

        $assetMember2 = new AssetMember();
        $assetMember2->setUser($user2);
        $assetMember2->setAsset($asset);
        $assetMember2->setMembertype(AssetMember::MEMBER_TYPE_NORMAL);
        $this->repository->save($assetMember2, true);

        $assetMemberList = $this->repository->findBy(['asset' => $asset->getId()]);
        $this->assertCount($originalMemberCount + 2, $assetMemberList);

        $assetMember3 = new AssetMember();
        $assetMember3->setUser($user3);
        $assetMember3->setAsset($asset);
        $assetMember3->setMembertype(AssetMember::MEMBER_TYPE_NORMAL);
        $this->repository->save($assetMember3, true);

        $assetMemberList = $this->repository->findBy(['asset' => $asset->getId()]);
        $this->assertCount($originalMemberCount + 3, $assetMemberList);

        $assetMemberList = $this->repository->findBy([
            'asset' => $asset->getId(),
            'membertype' => AssetMember::MEMBER_TYPE_NORMAL,
        ]);
        $this->assertCount(2, $assetMemberList);

        $assetMemberList = $this->repository->findBy([
            'asset' => $asset->getId(),
            'membertype' => AssetMember::MEMBER_TYPE_POINT_CONTACT,
        ]);
        $this->assertCount(1, $assetMemberList);
    }
}
