<?php

/**
 * Created by PhpStorm.
 */

namespace App\Entity;

use App\Entity\BaseEntity;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use JMS\Serializer\Annotation as JMS;
use Symfony\Component\Validator\Constraints as Assert;

//
/**
 * Class UserInvestor
 * @package App\Entity
 */
#[JMS\ExclusionPolicy('all')]
#[ORM\Table(name: 'user_investors')]
#[ORM\Entity]
// ForDBAL4 #[Gedmo\Loggable]
class Investor extends BaseEntity
{
    /**
     * @var boolean $cxbWorthInvestor
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(type: 'boolean', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $cxbWorthInvestor;

    /**
     * @var boolean $cxbSophisticatedInvestor
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(type: 'boolean', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $cxbSophisticatedInvestor;

    /**
     * @var boolean $cxbRestrictedUser
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(type: 'boolean', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $cxbRestrictedUser;

    /**
     * @var boolean $cxbLtdCompInvestor
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(type: 'boolean', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $cxbLtdCompInvestor;

    /**
     * @var string $cxbLtdCompInvestor
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(type: 'string', nullable: true, options: ['default' => 'No'])]
    // ForDBAL4 #[Gedmo\Versioned]
    #[Assert\Choice(choices: ['Yes', 'No'])]
    private $alwaysGoUp;

    /**
     * @var string incomeEveryMonth
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(type: 'string', nullable: true, options: ['default' => 'No'])]
    // ForDBAL4 #[Gedmo\Versioned]
    #[Assert\Choice(choices: ['Yes', 'No'])]
    private $incomeEveryMonth;

    /**
     * @var string $neverExit
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(type: 'string', nullable: true, options: ['default' => 'No'])]
    // ForDBAL4 #[Gedmo\Versioned]
    #[Assert\Choice(choices: ['Yes', 'No'])]
    private $neverExit;

    /**
     * @var int
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true)]
    private $poiFileId;

    /**
     * @var string
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(nullable: true, length: 1000)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $wordsOfOwn;

    /**
     * @var boolean $corporateInvestor
     */
    #[JMS\Expose]
    #[JMS\Groups(['standard', 'admin'])]
    #[ORM\Column(type: 'boolean', nullable: true)]
    // ForDBAL4 #[Gedmo\Versioned]
    private $corporateInvestor;

    #[ORM\OneToOne(targetEntity: 'App\Entity\User', mappedBy: 'investor')]
    // ForDBAL4 #[Gedmo\Versioned]
    private $user;

    /**
     * Set cxbWorthInvestor
     *
     * @param boolean $cxbWorthInvestor
     */
    public function setCxbWorthInvestor($cxbWorthInvestor): self
    {
        $this->cxbWorthInvestor = $cxbWorthInvestor;

        return $this;
    }

    /**
     * Get cxbWorthInvestor
     *
     * @return boolean
     */
    public function getCxbWorthInvestor()
    {
        return $this->cxbWorthInvestor;
    }

    /**
     * Set cxbSophisticatedInvestor
     *
     * @param boolean $cxbSophisticatedInvestor
     */
    public function setCxbSophisticatedInvestor($cxbSophisticatedInvestor): self
    {
        $this->cxbSophisticatedInvestor = $cxbSophisticatedInvestor;

        return $this;
    }

    /**
     * Get cxbSophisticatedInvestor
     *
     * @return boolean
     */
    public function getCxbSophisticatedInvestor()
    {
        return $this->cxbSophisticatedInvestor;
    }

    /**
     * Set cxbRestrictedUser
     *
     * @param boolean $cxbRestrictedUser
     */
    public function setCxbRestrictedUser($cxbRestrictedUser): self
    {
        $this->cxbRestrictedUser = $cxbRestrictedUser;

        return $this;
    }

    /**
     * Get cxbRestrictedUser
     *
     * @return boolean
     */
    public function getCxbRestrictedUser()
    {
        return $this->cxbRestrictedUser;
    }

    /**
     * Set cxbLtdCompInvestor
     *
     * @param boolean $cxbLtdCompInvestor
     */
    public function setCxbLtdCompInvestor($cxbLtdCompInvestor): self
    {
        $this->cxbLtdCompInvestor = $cxbLtdCompInvestor;

        return $this;
    }

    /**
     * Get cxbLtdCompInvestor
     *
     * @return boolean
     */
    public function getCxbLtdCompInvestor()
    {
        return $this->cxbLtdCompInvestor;
    }

    /**
     * Set alwaysGoUp
     *
     * @param boolean $alwaysGoUp
     */
    public function setAlwaysGoUp($alwaysGoUp): self
    {
        $this->alwaysGoUp = $alwaysGoUp;

        return $this;
    }

    /**
     * Get alwaysGoUp
     *
     * @return boolean
     */
    public function getAlwaysGoUp()
    {
        return $this->alwaysGoUp;
    }

    /**
     * Set incomeEveryMonth
     *
     * @param integer $incomeEveryMonth
     */
    public function setIncomeEveryMonth($incomeEveryMonth): self
    {
        $this->incomeEveryMonth = $incomeEveryMonth;

        return $this;
    }

    /**
     * Get incomeEveryMonth
     *
     * @return integer
     */
    public function getIncomeEveryMonth()
    {
        return $this->incomeEveryMonth;
    }

    /**
     * Set neverExit
     *
     * @param boolean $neverExit
     */
    public function setNeverExit($neverExit): self
    {
        $this->neverExit = $neverExit;

        return $this;
    }

    /**
     * Get neverExit
     *
     * @return boolean
     */
    public function getNeverExit()
    {
        return $this->neverExit;
    }

    /**
     * Set poiFileId
     *
     * @param int $poiFileId
     */
    public function setPoiFileId($poiFileId): self
    {
        $this->poiFileId = $poiFileId;

        return $this;
    }

    /**
     * Get poiFileId
     *
     * @return int
     */
    public function getPoiFileId()
    {
        return $this->poiFileId;
    }

    /**
     * Set wordsOfOwn
     *
     * @param string $wordsOfOwn
     */
    public function setWordsOfOwn($wordsOfOwn): self
    {
        $this->wordsOfOwn = $wordsOfOwn;

        return $this;
    }

    /**
     * Get wordsOfOwn
     *
     * @return string
     */
    public function getWordsOfOwn()
    {
        return $this->wordsOfOwn;
    }

    /**
     * Set corporateInvestor
     *
     * @param boolean $corporateInvestor
     */
    public function setCorporateInvestor($corporateInvestor): self
    {
        $this->corporateInvestor = $corporateInvestor;

        return $this;
    }

    /**
     * Get corporateInvestor
     *
     * @return boolean
     */
    public function getCorporateInvestor()
    {
        return $this->corporateInvestor;
    }

    /**
     * Set user
     *
     * @param \App\Entity\User $user
     */
    public function setUser(?\App\Entity\User $user = null): self
    {
        $this->user = $user;

        return $this;
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
