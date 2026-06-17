<?php

namespace App\Entity;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;

abstract class BaseEntity
{
    use TimestampableEntity;

    use BlameableEntity;

    public const VISIBILITY_AUTO = 0; // Use other fields to determine visibility
    public const VISIBILITY_ADMIN = 1; // Admin only
    public const VISIBILITY_VIP = 2; // Admin or VIP only
    public const VISIBILITY_NORMAL = 3; // Registered users only (usually better to rely on VISIBILITY_AUTO)
    public const VISIBILITY_ALL = 4; // No restrictions (usually better to rely on VISIBILITY_AUTO)

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * @var
     */
    #[ORM\Column(type: 'integer', nullable: true, options: ['default' => 0])]
    protected $createdById;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return integer
     */
    public function getCreatedById()
    {
        return $this->createdById;
    }

    /**
     * @param integer $createdById
     */
    public function setCreatedById($createdById)
    {
        $this->createdById = $createdById;
    }
}
