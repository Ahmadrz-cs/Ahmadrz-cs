<?php

/**
 * Created by PhpStorm.
 */

namespace App\Entity;

use App\Entity\BaseEntity;
use App\Entity\Document;
use App\Repository\OfferingDocumentRepository;
use App\Service\Util;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints\Date;

#[JMS\ExclusionPolicy('all')]
#[ORM\Table(name: 'offering_docs')]
#[ORM\Entity(repositoryClass: OfferingDocumentRepository::class)]
// ForDBAL4 #[Gedmo\Loggable]
class OfferingDocuments implements \JsonSerializable
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
     * @var $offering
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Offering', inversedBy: 'documents')]
    #[ORM\JoinColumn(name: 'off_id', referencedColumnName: 'id')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $offering;

    /**
     * @var Document
     */
    #[ORM\OneToOne(targetEntity: 'App\Entity\Document', cascade: ['persist', 'remove'])]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $document;

    public function __construct()
    {
        $this->document = new ArrayCollection();
    }

    /**
     * Gets the id
     *
     * @return int
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
     * Get offering
     *
     * @return \App\Entity\Offering
     */
    public function getOffering()
    {
        return $this->offering;
    }

    /**
     * @param integer $createdById
     */
    public function setCreatedById($createdById)
    {
        $this->createdById = $createdById;
    }

    /**
     * Set offering
     *
     * @param \App\Entity\Offering $offering
     *
     * @return OfferingDocuments
     */
    public function setOffering(?\App\Entity\Offering $offering = null)
    {
        $this->offering = $offering;

        return $this;
    }

    /**
     * Set document
     *
     * @param  $document
     *
     * @return OfferingDocuments
     */
    public function setDocument(?Document $document = null)
    {
        $this->document = $document;

        return $this;
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
    public function getDocument()
    {
        return $this->document;
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
            'offering_document_id' => $this->getId(),

            /* audit */
            'created_at' => Util\Helper::formatDate($this->getCreatedAt()),
            'updated_at' => Util\Helper::formatDate($this->getUpdatedAt()),
            'user_id' => $this->getCreatedById(),
        ];
    }
}
