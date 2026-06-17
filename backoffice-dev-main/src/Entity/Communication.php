<?php

namespace App\Entity;

use App\Entity\BaseEntity;
use App\Repository\CommunicationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

#[JMS\ExclusionPolicy('all')]
#[ORM\Table(name: 'users_communications')]
#[ORM\Entity(repositoryClass: CommunicationRepository::class)]
class Communication extends BaseEntity
{
    /**
     * @var User
     */
    #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'communications')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    #[Assert\NotBlank]
    private $user;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column]
    #[Assert\NotBlank]
    private $subject;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private $content;

    /**
     * @var int
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column(type: 'integer')]
    #[Assert\NotBlank]
    private $status;

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Set subject
     *
     * @param string $subject
     *
     * @return Communication
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return Communication
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set recipient
     *
     * @param \App\Entity\User user
     *
     * @return Communication
     */
    public function setRecipient(?\App\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get recipient
     *
     * @return \App\Entity\User
     */
    public function getRecipient()
    {
        return $this->user;
    }

    /**
     * Represents a string representation.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->user . $this->subject ?: '';
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
