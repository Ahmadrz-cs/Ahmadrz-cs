<?php

namespace App\Entity;

use App\Entity\BaseEntity;
use App\Entity\Document;
use App\Entity\Traits\TimestampableEntity;
use App\Repository\UserDocumentRepository;
use App\Service\Util;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class UserLog
 * @package App\Entity
 */
#[JMS\ExclusionPolicy('all')]
#[ORM\Table(name: 'user_docs')]
#[ORM\Entity(repositoryClass: UserDocumentRepository::class)]
// ForDBAL4 #[Gedmo\Loggable]
class UserDocument implements \JsonSerializable
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
     * @var User
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User', inversedBy: 'documents')]
    // ForDBAL4 #[Gedmo\Versioned]
    protected $user;

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
     * @param integer $createdById
     */
    public function setCreatedById($createdById)
    {
        $this->createdById = $createdById;
    }

    /**
     * @param $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set document
     *
     * @return UserDocument
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
            'asset_document_id' => $this->getId(),
            'document_content' => $this->document->getBase64Encoded_DocumentContent(),
            /* audit */
            'created_at' => Util\Helper::formatDate($this->getCreatedAt()),
            'updated_at' => Util\Helper::formatDate($this->getUpdatedAt()),
            'user_id' => $this->getCreatedById(),
        ];
    }
}
