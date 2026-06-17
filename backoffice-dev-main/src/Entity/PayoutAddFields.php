<?php

/**
 * Created by PhpStorm.
 */

namespace App\Entity;

use App\Entity\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints\Date;

/**
 *
 * @author Sayak Mukherjee <sayak.mukherjee@qtsin.net>
 */
#[JMS\ExclusionPolicy('all')]
#[ORM\Table(name: 'payout_add_fields')]
#[ORM\Entity]
// ForDBAL4 #[Gedmo\Loggable]
class PayoutAddFields extends BaseEntity
{
    /**
     * @var $fieldKey
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(type: 'string', length: 20, unique: true, nullable: false)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $fieldKey;

    /**
     * @var $fieldValue
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $fieldValue;

    /**
     * @var $payout
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Payout', inversedBy: 'addFields')]
    #[ORM\JoinColumn(name: 'payout_id', referencedColumnName: 'id')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $payout;

    /**
     * Set fieldKey
     *
     * @param string $fieldKey
     *
     * @return PayoutAddFields
     */
    public function setFieldKey($fieldKey)
    {
        $this->fieldKey = $fieldKey;

        return $this;
    }

    /**
     * Get fieldKey
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
     * @param string $fieldValue
     *
     * @return PayoutAddFields
     */
    public function setFieldValue($fieldValue)
    {
        $this->fieldValue = $fieldValue;

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
     * Set payout
     *
     * @param \App\Entity\Payout $payout
     *
     * @return PayoutAddFields
     */
    public function setPayout(?\App\Entity\Payout $payout = null)
    {
        $this->payout = $payout;

        return $this;
    }

    /**
     * Get Payout
     *
     * @return \App\Entity\Payout
     */
    public function getPayout()
    {
        return $this->payout;
    }

    /**
     * Represents a string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return (
            'Key = '
            . ($this->getFieldKey() ?: '')
            . '| Value = '
            . $this->getFieldValue()
            ?: ''
        );
    }
}
