<?php

/**
 * Created by PhpStorm.
 */

namespace App\Entity;

use App\Entity\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author Sayak Mukherjee <sayak.mukherjee@qtsin.net>
 */
#[ORM\Table(name: 'investment_add_fields')]
#[ORM\Entity]
// ForDBAL4 #[Gedmo\Loggable]
class InvestmentAddFields extends BaseEntity
{
    /**
     * @var $fieldKey
     */
    #[ORM\Column(type: 'string', length: 255)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $fieldKey;

    /**
     * @var $fieldValue
     */
    #[ORM\Column(type: 'string', length: 255)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $fieldValue;

    /**
     * @var $investment
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Investment', inversedBy: 'addFields')]
    #[ORM\JoinColumn(name: 'investment_id', referencedColumnName: 'id')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $investment;

    /**
     * Set fieldKey
     *
     * @param string $fieldKey
     *
     * @return InvestmentAddFields
     */
    public function setFieldKey($fieldKey)
    {
        $this->fieldKey = $fieldKey;

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
     * Set fieldValue
     *
     * @param string $fieldValue
     *
     * @return InvestmentAddFields
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
     * Set investment
     *
     * @param \App\Entity\Investment $investment
     *
     * @return InvestmentAddFields
     */
    public function setInvestment(?\App\Entity\Investment $investment = null)
    {
        $this->investment = $investment;

        return $this;
    }

    /**
     * Get investment
     *
     * @return \App\Entity\Investment
     */
    public function getInvestment()
    {
        return $this->investment;
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
