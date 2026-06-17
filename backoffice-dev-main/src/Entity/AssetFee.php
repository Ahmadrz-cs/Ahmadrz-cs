<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 14/01/17
 * Time: 20:44
 */

namespace App\Entity;

use App\Entity\Asset;
use App\Entity\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Table(name: 'asset_fee')]
#[ORM\Entity]
// ForDBAL4 #[Gedmo\Loggable]
class AssetFee implements \JsonSerializable
{
    use BlameableEntity;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * @var Asset $asset
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Asset', inversedBy: 'fees')]
    #[ORM\JoinColumn(name: 'asset_id', referencedColumnName: 'id')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $asset;

    /**
     * @var string $type
     */
    #[ORM\Column(type: 'string', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $type;

    /**
     * @var int $band
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $band;

    /**
     * @var int $fee
     */
    #[ORM\Column(type: 'integer', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $fee;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAsset(): ?Asset
    {
        return $this->asset;
    }

    public function setAsset(Asset $asset): void
    {
        $this->asset = $asset;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getBand(): ?int
    {
        return $this->band;
    }

    public function setBand(int $band): void
    {
        $this->band = $band;
    }

    public function getFee(): ?int
    {
        return $this->fee;
    }

    public function setFee(int $fee): void
    {
        $this->fee = $fee;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->getId(),
            'type' => $this->getType(),
            'band' => $this->getBand(),
            'fee' => $this->getFee(),
        ];
    }
}
