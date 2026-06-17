<?php

/**
 * Created by PhpStorm.
 */

namespace App\Entity;

use App\Entity\BaseEntity;
use App\Entity\Document;
use App\Entity\Traits\TimestampableEntity;
use App\Repository\InvestmentDocumentRepository;
use App\Service\Util;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints\Date;

/**
 */
#[JMS\ExclusionPolicy('all')]
#[ORM\Table(name: 'investment_docs')]
#[ORM\Entity(repositoryClass: InvestmentDocumentRepository::class)]
// ForDBAL4 #[Gedmo\Loggable]
class InvestmentDocuments implements \JsonSerializable
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

    /**
     */
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
     * @var $investment
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\Investment', inversedBy: 'documents')]
    #[ORM\JoinColumn(name: 'investment_id', referencedColumnName: 'id')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $investment;

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
     * Set investment
     *
     * @param \App\Entity\Investment $investment
     *
     * @return InvestmentDocuments
     */
    public function setInvestment(?\App\Entity\Investment $investment = null)
    {
        $this->investment = $investment;

        return $this;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getCreatedById(): ?int
    {
        return $this->createdById;
    }

    public function setCreatedById(int $createdById): void
    {
        $this->createdById = $createdById;
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
     * Set document
     *
     * @param  $document
     *
     * @return InvestmentDocuments
     */
    public function setDocument(?Document $document = null)
    {
        $this->document = $document;

        return $this;
    }

    #[JMS\Expose]
    #[JMS\VirtualProperty]
    #[JMS\Groups(['standard', 'admin'])]
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
            'investment_document_id' => $this->getId(),
            // specifically used to identify the
            // document id to be downloaded by the front end
            'url' => '/document-download/' . $this->document->getId(),
            // specifically used to identify the
            // s3 path to the document
            'document_url' => $this->document->getDocumentUrl(),

            /* audit */
            'created_at' => Util\Helper::formatDate($this->getCreatedAt()),
            'updated_at' => Util\Helper::formatDate($this->getUpdatedAt()),
            'user_id' => $this->getCreatedById(),
        ];
    }
}
