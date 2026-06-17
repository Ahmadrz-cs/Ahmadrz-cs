<?php

/**
 * Created by PhpStorm.
 */

namespace App\Entity;

use App\Entity\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints\Date;

/**
 *
 * @author Sayak Mukherjee <sayak.mukherjee@qtsin.net>
 */
#[ORM\Table(name: 'offering_add_fields')]
#[ORM\Entity]
// ForDBAL4 #[Gedmo\Loggable]
class OfferingAddFields extends BaseEntity
{
    /**
     * @var $fieldKey
     */
    #[ORM\Column(type: 'string', length: 255, unique: true, nullable: false)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $fieldKey;

    /**
     * @var $fieldValue
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $fieldValue;

    /**
     * @var $offering
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Offering', inversedBy: 'addFields')]
    #[ORM\JoinColumn(name: 'off_id', referencedColumnName: 'id')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $offering;

    /**
     * Set fieldKey
     *
     * @param string $fieldKey
     *
     * @return OfferingAddFields
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
     * @return OfferingAddFields
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
     * Set offering
     *
     * @param \App\Entity\Offering $offering
     *
     * @return OfferingAddFields
     */
    public function setOffering(?\App\Entity\Offering $offering = null)
    {
        $this->offering = $offering;

        return $this;
    }

    /**
     * Get offering
     *
     * @return \App\Entity\Offering
     */
    public function getOffering()
    {
        return $this->offering;
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
