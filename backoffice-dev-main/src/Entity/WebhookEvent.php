<?php

namespace App\Entity;

use App\Repository\WebhookEventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WebhookEventRepository::class)]
class WebhookEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public readonly int $id;

    public function __construct(
        #[ORM\Column(length: 255)]
        public readonly string $eventType,
        #[ORM\Column(length: 255)]
        public readonly string $resourceId,
        #[ORM\Column(length: 255)]
        public readonly string $fingerprint,
        #[ORM\Column]
        private int $lastReceived,
    ) {}

    public function getLastReceived(): ?int
    {
        return $this->lastReceived;
    }

    public function setLastReceived(int $lastReceived): self
    {
        $this->lastReceived = $lastReceived;

        return $this;
    }
}
