<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 29/01/17
 * Time: 00:31
 */

namespace App\Entity;

use App\Entity\BaseEntity;
use App\Entity\User;
use App\Repository\ContegoLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @author Keesh
 */
#[ORM\Table(name: 'contego_logs')]
#[ORM\Entity(repositoryClass: ContegoLogRepository::class)]
class ContegoLog extends BaseEntity
{
    /**
     * Name of the contegor profile used to execute the check
     *
     * @var string
     */
    #[ORM\Column]
    private $profile_name;

    /**
     * The RAG response
     * @var string
     */
    #[ORM\Column]
    private $rag;

    /**
     * The KYC score
     *
     * @var string
     */
    #[ORM\Column]
    private $kyc_score;

    /**
     * The Type of check performed
     *
     * @var string
     */
    #[ORM\Column]
    private $kyc_type;

    /**
     * The contego reference id for the check
     *
     * @var string
     */
    #[ORM\Column]
    private $ext_reference_id;

    /**
     * The contego url to the pdf report
     *
     * @var string
     */
    #[ORM\Column]
    private $pdf_report_url;

    /**
     * The User entity that the check is being performed on
     *
     * Note that this property returns a string (username)
     * ManyToOne Relation currently doesn't work properly
     *
     * @var User
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User')]
    #[ORM\Column(nullable: false)]
    private $user;

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param User $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * @return string
     */
    public function getProfileName()
    {
        return $this->profile_name;
    }

    /**
     * @param string $profile_name
     */
    public function setProfileName($profile_name)
    {
        $this->profile_name = $profile_name;
    }

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
     * @return string
     */
    public function getKycScore()
    {
        return $this->kyc_score;
    }

    /**
     * @param string $kyc_score
     */
    public function setKycScore($kyc_score)
    {
        $this->kyc_score = $kyc_score;
    }

    /**
     * @return string
     */
    public function getKycType()
    {
        return $this->kyc_type;
    }

    /**
     * @param string $kyc_type
     */
    public function setKycType($kyc_type)
    {
        $this->kyc_type = $kyc_type;
    }

    /**
     * @return string
     */
    public function getExtReferenceId()
    {
        return $this->ext_reference_id;
    }

    /**
     * @param string $ext_reference_id
     */
    public function setExtReferenceId($ext_reference_id)
    {
        $this->ext_reference_id = $ext_reference_id;
    }

    /**
     * @return string
     */
    public function getPdfReportUrl()
    {
        return $this->pdf_report_url;
    }

    /**
     * @param string $pdf_report_url
     */
    public function setPdfReportUrl($pdf_report_url)
    {
        $this->pdf_report_url = $pdf_report_url;
    }
}
