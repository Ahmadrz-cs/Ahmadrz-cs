<?php

namespace App\Entity;

use App\Entity\User;
use App\Service\Util\Helper;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class UserDocuments
 * @package App\Entity
 */
#[JMS\ExclusionPolicy('all')]
#[ORM\Table(name: 'documents')]
#[ORM\Entity(repositoryClass: DocumentRepository::class)]
class Document extends BaseEntity implements \JsonSerializable
{
    /**
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     *
     * @var File
     */
    private $file;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\SerializedName('fileName')]
    #[ORM\Column]
    private $filename;

    /**
     * @var string
     */
    #[ORM\Column(nullable: true)]
    private $name;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    private $description;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    private $type;

    /**
     * @var string
     */
    #[ORM\Column(nullable: true)]
    private $alias;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\SerializedName('tag')]
    #[ORM\Column(nullable: true)]
    private $tag;

    /**
     * @var string
     */
    #[ORM\Column(nullable: true)]
    private $category;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[JMS\SerializedName('url')]
    #[ORM\Column(nullable: true)]
    private $documentUrl;

    /**
     * @var string $documentContent
     */
    private $documentContent;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Document
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get documentUrl
     *
     * @return string
     */
    public function getDocumentUrl()
    {
        return $this->documentUrl;
    }

    /**
     * Set documentUrl
     *
     * @param string $documentUrl
     *
     * @return Document
     */
    public function setDocumentUrl($documentUrl)
    {
        $this->documentUrl = $documentUrl;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return Document
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set type
     *
     * @param string $type
     *
     * @return Document
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set alias
     *
     * @param string $alias
     *
     * @return Document
     */
    public function setAlias($alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * Get alias
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * Set tag
     *
     * @param string $tag
     *
     * @return Document
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Get tag
     *
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Set category
     *
     * @param string $category
     *
     * @return Document
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile $file
     *
     * @return $this
     */
    public function setFile(?File $file = null)
    {
        $this->file = $file;

        if ($file) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTime('now');
        }

        return $this;
    }

    public function getFile()
    {
        return $this->file;
    }

    public function getFilename()
    {
        return $this->filename;
    }

    public function setFilename($filename)
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * Set document contect
     *
     * @param string $documentContent
     *
     * @return Document
     */
    public function setDocumentContent($documentContent)
    {
        $this->documentContent = $documentContent;

        return $this;
    }

    /**
     * Get  document content
     *
     * @return string
     */
    public function getDocumentContent()
    {
        return $this->documentContent;
    }

    public function getBase64Encoded_DocumentContent()
    {
        if (!empty($this->documentContent)) {
            return base64_encode(stream_get_contents($this->documentContent));
        } else {
            return $this->documentContent;
        }
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->getId(),
            'file_alias' => $this->getAlias(),
            'file_description' => $this->getDescription(),
            'file_name' => $this->getFilename(),
            'file_type' => $this->getType(),
            'tag' => $this->getTag(),

            'document_url' => $this->getDocumentUrl(),
            'document_id' => $this->getId(),
            // 'document_content' => $this->getBase64Encoded_DocumentContent(),
            'document_content' => $this->getDocumentContent(),
            /* audit */
            'created_at' => Helper::formatDate($this->getCreatedAt()),
            'updated_at' => Helper::formatDate($this->getUpdatedAt()),
            'user_id' => $this->getCreatedById(),
        ];
    }
}
