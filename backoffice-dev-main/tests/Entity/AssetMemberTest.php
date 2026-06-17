<?php

/**
 * Created by PhpStorm.
 * User: plok
 * Date: 14/01/17
 * Time: 21:21
 */

namespace App\Tests\Entity;

use App\Entity\Asset;
use App\Entity\AssetMember;
use App\Entity\User;

/**
 * Class AssetMembersTest
 * @package App\Tests\Entity
 */
class AssetMemberTest extends \PHPUnit\Framework\TestCase
{
    public function testCreateNormal(): void
    {
        try {
            $asset_member = new AssetMember();

            $user = new User();
            $asset = new Asset();

            $asset_member->setAsset($asset);
            $asset_member->setUser($user);
            $asset_member->setMembertype(AssetMember::MEMBER_TYPE_NORMAL);

            $this->assertNotNull($asset_member->getAsset());
            $this->assertNotNull($asset_member->getUser());
            $this->assertNotNull($asset_member->getMembertype());
            $this->assertEquals(
                AssetMember::MEMBER_TYPE_NORMAL,
                $asset_member->getMembertype(),
            );
            $this->assertEquals(false, $asset_member->getIsContactPoint());
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }

    public function testCreatePointOfContact(): void
    {
        try {
            $asset_member = new AssetMember();

            $user = new User();
            $asset = new Asset();

            $asset_member->setAsset($asset);
            $asset_member->setUser($user);
            $asset_member->setMembertype(AssetMember::MEMBER_TYPE_POINT_CONTACT);

            $this->assertNotNull($asset_member->getAsset());
            $this->assertNotNull($asset_member->getUser());
            $this->assertNotNull($asset_member->getMembertype());
            $this->assertEquals(
                AssetMember::MEMBER_TYPE_POINT_CONTACT,
                $asset_member->getMembertype(),
            );
            $this->assertEquals(true, $asset_member->getIsContactPoint());
        } catch (\Exception $e) {
            echo 'Caught exception: ', $e->getMessage(), "\n";
        }
    }
}
