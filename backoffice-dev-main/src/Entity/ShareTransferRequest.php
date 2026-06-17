<?php

namespace App\Entity;

use App\Entity\Enum\OrderRequestStatus;
use App\Entity\Investment;
use App\Entity\Traits\AssociationBlameableEntity;
use App\Entity\User;
use App\Repository\ShareTransferRequestRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

#[ORM\Entity(repositoryClass: ShareTransferRequestRepository::class)]
class ShareTransferRequest
{
    use AssociationBlameableEntity;
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'shareTransfers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ShareTransferOrder $shareTransferOrder = null;

    #[ORM\Column(type: Types::STRING, enumType: OrderRequestStatus::class, options: [
        'default' => OrderRequestStatus::Pending,
    ])]
    private OrderRequestStatus $status = OrderRequestStatus::Pending;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $seller = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $buyer = null;

    #[ORM\ManyToOne]
    private ?Investment $investment = null;

    #[ORM\Column]
    private ?int $shares = null;

    #[ORM\ManyToOne]
    private ?ShareTrade $shareTrade = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShareTransferOrder(): ?ShareTransferOrder
    {
        return $this->shareTransferOrder;
    }

    public function setShareTransferOrder(?ShareTransferOrder $shareTransferOrder): static
    {
        $this->shareTransferOrder = $shareTransferOrder;

        return $this;
    }

    public function getStatus(): OrderRequestStatus
    {
        return $this->status;
    }

    public function setStatus(OrderRequestStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSeller(): ?User
    {
        return $this->seller;
    }

    public function setSeller(?User $seller): static
    {
        $this->seller = $seller;

        return $this;
    }

    public function getBuyer(): ?User
    {
        return $this->buyer;
    }

    public function setBuyer(?User $buyer): static
    {
        $this->buyer = $buyer;

        return $this;
    }

    public function getShares(): ?int
    {
        return $this->shares;
    }

    public function setShares(int $shares): static
    {
        $this->shares = $shares;

        return $this;
    }

    public function getInvestment(): ?Investment
    {
        return $this->investment;
    }

    public function setInvestment(?Investment $investment): static
    {
        $this->investment = $investment;

        return $this;
    }

    public function getShareTrade(): ?ShareTrade
    {
        return $this->shareTrade;
    }

    public function setShareTrade(?ShareTrade $shareTrade): static
    {
        $this->shareTrade = $shareTrade;

        return $this;
    }
}
