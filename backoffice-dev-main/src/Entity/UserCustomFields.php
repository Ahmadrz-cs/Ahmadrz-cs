<?php

namespace App\Entity;

use App\Entity\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 */
#[JMS\ExclusionPolicy('all')]
#[ORM\Table(name: 'user_cus_fields')]
#[ORM\Entity]
// ForDBAL4 #[Gedmo\Loggable]
class UserCustomFields extends BaseEntity
{
    /**
     * @var \App\Entity\User $user
     */
    #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'customFields')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $user;

    /**
     * @var string $key
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column]
    // ForDBAL4 #[Gedmo\Versioned]
    #[Assert\NotBlank]
    private $fieldKey;

    /**
     * @var string $value
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column]
    // ForDBAL4 #[Gedmo\Versioned]
    private $fieldValue;

    /**
     * Set key
     *
     * @param string $key
     *
     * @return UserCustomFields
     */
    public function setFieldKey($key)
    {
        $this->fieldKey = $key;

        return $this;
    }

    /**
     * Get key
     *
     * @return string
     */
    public function getFieldKey()
    {
        return $this->fieldKey;
    }

    /**
     * Set value
     *
     * @param string $value
     *
     * @return UserCustomFields
     */
    public function setFieldValue($value)
    {
        $this->fieldValue = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getFieldValue()
    {
        return $this->fieldValue;
    }

    /**
     * Set user
     *
     * @param \App\Entity\User $user
     *
     * @return UserCustomFields
     */
    public function setUser(?\App\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \App\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }
}
