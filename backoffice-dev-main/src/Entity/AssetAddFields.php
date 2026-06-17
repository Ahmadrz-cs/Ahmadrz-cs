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
#[ORM\Table(name: 'asset_add_fields')]
#[ORM\Entity]
// ForDBAL4 #[Gedmo\Loggable]
class AssetAddFields extends BaseEntity
{
    /**
     * @var string $fieldKey
     */
    #[ORM\Column(nullable: false)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $fieldKey;

    /**
     * @var string $value
     */
    #[ORM\Column(nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $value;

    /**
     * @var $asset
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Asset', inversedBy: 'addFields')]
    #[ORM\JoinColumn(name: 'asset_id', referencedColumnName: 'id')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $asset;

    /**
     * Set key
     *
     * @param string $key
     *
     * @return AssetAddFields
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
     * @param string $value
     *
     * @return AssetAddFields
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set asset
     *
     * @param \App\Entity\Asset $asset
     *
     * @return AssetAddFields
     */
    public function setAsset(?\App\Entity\Asset $asset = null)
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * Get asset
     *
     * @return \App\Entity\Asset
     */
    public function getAsset()
    {
        return $this->asset;
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
            . $this->getValue()
            ?: ''
        );
    }
}
