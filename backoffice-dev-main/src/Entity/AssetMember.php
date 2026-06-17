<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 14/01/17
 * Time: 20:44
 */

namespace App\Entity;

use App\Entity\BaseEntity;
use App\Entity\User;
use App\Repository\AssetMemberRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 *
 * @author keesh
 */
#[ORM\Table(name: 'asset_members')]
#[ORM\Entity(repositoryClass: AssetMemberRepository::class)]
// ForDBAL4 #[Gedmo\Loggable]
class AssetMember extends BaseEntity implements \JsonSerializable
{
    public const MEMBER_TYPE_NORMAL = 'Member';
    public const MEMBER_TYPE_AUTHOR = 'Author';
    public const MEMBER_TYPE_POINT_CONTACT = 'PointOfContact';

    /**
     * @var User $user
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User', inversedBy: 'assets')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $user;

    /**
     * @var string $membertype
     */
    #[ORM\Column(nullable: true)]
    private $membertype;

    /**
     * @var $asset
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Asset', inversedBy: 'members')]
    #[ORM\JoinColumn(name: 'asset_id', referencedColumnName: 'id')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $asset;

    /**
     * @return string
     */
    public function getMembertype()
    {
        return $this->membertype;
    }

    /**
     * @param string $membertype
     */
    public function setMembertype($membertype)
    {
        $this->membertype = $membertype;
    }

    /**
     * Get Is this a member a Contact point
     *
     * @return bool
     */
    public function getIsContactPoint()
    {
        if ($this->membertype === self::MEMBER_TYPE_NORMAL) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return Asset
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * @param Asset $asset
     */
    public function setAsset($asset)
    {
        $this->asset = $asset;
    }

    public function publicView()
    {
        return [
            'organization_id' => $this->getAsset()->getId(),
            'organization_name' => $this->getAsset()->getName(),
            'status' => true, //@todo missing field
            'user_id' => $this->user->getId(),
            'member_type' => $this->membertype,
            'user_full_name' => $this->user->getFullname(),
            'contact_point' => $this->getIsContactPoint(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): mixed
    {
        return [
            'organization_id' => $this->getAsset()->getId(),
            'organization_name' => $this->getAsset()->getName(),
            'status' => true, //@todo missing field
            'user_id' => $this->user->getId(),
            'member_type' => $this->membertype,
            'user_full_name' => $this->user->getFullname(),
            'contact_point' => $this->getIsContactPoint(),
        ];
    }
}
