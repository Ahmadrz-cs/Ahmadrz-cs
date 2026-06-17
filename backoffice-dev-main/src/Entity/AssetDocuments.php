<?php

/**
 * Created by PhpStorm.
 */

namespace App\Entity;

use App\Entity\BaseEntity;
use App\Entity\Document;
use App\Entity\Interface\RelatedDocumentInterface;
use App\Repository\AssetDocumentRepository;
use App\Service\Util;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @package App\Entity
 * @author Sayak Mukherjee <sayak.mukherjee@qtsin.net>
 */
#[JMS\ExclusionPolicy('all')]
#[ORM\Table(name: 'asset_docs')]
#[ORM\Entity(repositoryClass: AssetDocumentRepository::class)]
// ForDBAL4 #[Gedmo\Loggable]
class AssetDocuments implements \JsonSerializable, RelatedDocumentInterface
{
    /**
     * Hook blameable behavior
     * updates createdBy, updatedBy fields
     */
    use BlameableEntity;

    /**
     * Hook timestampable behavior
     * updates createdAt, updatedAt fields
     */
    use TimestampableEntity;

    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
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
     * @var $asset
     *
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Asset', inversedBy: 'documents')]
    #[ORM\JoinColumn(name: 'asset_id', referencedColumnName: 'id')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $asset;

    /**
     * @var Document
     */
    #[ORM\OneToOne(targetEntity: 'App\Entity\Document', cascade: ['persist', 'remove'])]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $document;

    public function __construct()
    {
        // $this->document = new ArrayCollection();
    }

    /**
     * Gets the id
     *
     * @return int
     */
    public function getId(): ?int
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
     * Get asset
     *
     * @return \App\Entity\Asset
     */
    public function getAsset()
    {
        return $this->asset;
    }

    /**
     * Get document
     *
     * @return Document
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\VirtualProperty]
    #[JMS\Inline]
    public function getDocument(): ?Document
    {
        return $this->document;
    }

    /**
     * Set asset
     *
     * @param $asset
     *
     * @return $this
     */
    public function setAsset($asset = null)
    {
        $this->asset = $asset;

        return $this;
    }

    /**
     * @param integer $createdById
     */
    public function setCreatedById($createdById)
    {
        $this->createdById = $createdById;
    }

    /**
     * Set document
     *
     * @param  $document
     *
     * @return AssetDocuments
     */
    public function setDocument(?Document $document = null)
    {
        $this->document = $document;

        return $this;
    }

    public function getRelation(): ?Asset
    {
        return $this->getAsset();
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->document->getId(),
            'file_alias' => $this->document->getAlias(),
            'file_description' => $this->document->getDescription(),
            'file_name' => $this->document->getFilename(),
            'file_type' => $this->document->getType(),
            'tag' => $this->document->getTag(),
            // specifically used to identify the
            // document id to be downloaded by the front end
            'url' => '/document-download/' . $this->document->getId(),
            // specifically used to identify the
            // s3 path to the document
            'document_url' => $this->document->getDocumentUrl(),
            'asset_document_id' => $this->getId(),
            /* audit */
            'created_at' => Util\Helper::formatDate($this->getCreatedAt()),
            'updated_at' => Util\Helper::formatDate($this->getUpdatedAt()),
            'user_id' => $this->getCreatedById(),
        ];
    }
}
