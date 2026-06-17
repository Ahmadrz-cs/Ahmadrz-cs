<?php

namespace App\Entity;

use App\Entity\BaseEntity;
use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use League\Bundle\OAuth2ServerBundle\Model\Client;

#[ORM\Table(name: 'user_client')]
#[ORM\Entity]
// ForDBAL4 #[Gedmo\Loggable]
class UserClient extends BaseEntity
{
    /**
     * @var User
     */
    #[ORM\ManyToOne(targetEntity: 'User', inversedBy: 'clients')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $user;

    /**
     * @var Client
     */
    #[ORM\OneToOne(targetEntity: 'League\Bundle\OAuth2ServerBundle\Model\Client', cascade: [
        'persist',
        'remove',
    ])]
    #[ORM\JoinColumn(
        name: 'client_id',
        referencedColumnName: 'identifier',
        onDelete: 'CASCADE',
        nullable: false,
    )]
    // ForDBAL4 #[Gedmo\Versioned]
    private $client;

    /**
     * @var string
     */
    #[ORM\Column(name: 'alias', type: 'string', nullable: true)]
    private $alias;

    /**
     * @var string
     */
    #[ORM\Column(name: 'description', type: 'string', nullable: true)]
    private $description;

    public function __construct(User $user, Client $client)
    {
        $this->user = $user;
        $this->client = $client;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setClient(Client $client): self
    {
        $this->client = $client;
        return $this;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setAlias(?string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}
