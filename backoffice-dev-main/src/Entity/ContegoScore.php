<?php

/**
 * Created by PhpStorm.
 * User: keesj
 * Date: 04/02/17
 * Time: 00:49
 */

namespace App\Entity;

use App\Entity\BaseEntity;
use App\Entity\User;
use App\Repository\ContegoScoreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;

/**
 * @author Keesh
 */
#[JMS\ExclusionPolicy('all')]
#[ORM\Table(name: 'contego_score')]
#[ORM\Entity(repositoryClass: ContegoScoreRepository::class)]
class ContegoScore extends BaseEntity
{
    /**
     * The RAG response
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[ORM\Column]
    private $rag;

    /**
     * The KYC score
     *
     * @var int
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[JMS\Groups('kycScore')]
    #[ORM\Column]
    private $kyc_score;

    /**
     * The rule messages
     *
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['admin'])]
    #[JMS\Groups('ruleMessages')]
    #[ORM\Column(type: 'text')]
    private $rule_messages;

    #[ORM\OneToOne(targetEntity: 'App\Entity\User', mappedBy: 'contego_score')]
    private $user;

    /**
     * @return string
     */
    public function getRAG()
    {
        return $this->rag;
    }

    /**
     * @param string $rag
     */
    public function setRAG($rag)
    {
        $this->rag = $rag;
    }

    /**
     * @return int
     */
    public function getKycScore()
    {
        return $this->kyc_score;
    }

    /**
     * @param int $kyc_score
     */
    public function setKycScore($kyc_score)
    {
        $this->kyc_score = $kyc_score;
    }

    /**
     * @return mixed
     */
    public function getRuleMessages()
    {
        return $this->rule_messages;
    }

    /**
     * @param mixed $rule_messages
     */
    public function setRuleMessages($rule_messages)
    {
        $this->rule_messages = $rule_messages;
    }

    /**
     * Set user
     *
     * @param \App\Entity\User $user
     *
     */
    public function setUser(?\App\Entity\User $user = null)
    {
        $this->user = $user;
    }

    /**
     * Get user
     *
     * @return \App\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }
}
