<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Mail
 * @package App\Entity
 */
#[ORM\Table(name: 'mails')]
#[ORM\Entity]
class Mail extends BaseEntity
{
    /**
     * @var string $slug Machine name for the mail
     */
    #[ORM\Column]
    #[Assert\NotBlank]
    private $slug;

    /**
     * @var string $name Human name for the mail
     */
    #[ORM\Column]
    #[Assert\NotBlank]
    private $name;

    /**
     * @var string $subject Mail subject
     */
    #[ORM\Column]
    #[Assert\NotBlank]
    private $subject;

    /**
     * @var string $body Mail body
     */
    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private $body;

    /**
     * @deprecated No longer using params
     * @var array $params Array of required params for the mail
     */
    private $params;

    /**
     * @var boolean $sendAdmin
     *
     */
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $sendAdmin;
    /**
     * @var boolean $sendUser
     *
     */
    #[ORM\Column(type: 'boolean', nullable: true)]
    private $sendUser;

    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return Mail
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Mail
     */
    public function setName($name)
    {
        $this->name = $name;

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
     * Set subject
     *
     * @param string $subject
     *
     * @return Mail
     */
    public function setSubject($subject)
    {
        $this->subject = trim($subject);

        return $this;
    }

    /**
     * Get subject
     *
     * @return string
     */
    public function getSubject()
    {
        return trim($this->subject);
    }

    /**
     * Set body
     *
     * @param string $body
     *
     * @return Mail
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param array $params
     *
     * @return Mail
     */
    #[\Deprecated(message: 'No longer using params', since: '2024 November')]
    public function setParams($params)
    {
        $this->params = $params;

        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return Mail
     */
    #[\Deprecated(message: 'No longer using params', since: '2024 November')]
    public function addParam($key, $value)
    {
        $this->params[$key] = $value;

        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    #[\Deprecated(message: 'No longer using params', since: '2024 November')]
    public function removeParam($key)
    {
        unset($this->params[$key]);

        return $this;
    }

    /**
     *
     * @return array
     */
    #[\Deprecated(message: 'No longer using params', since: '2024 November')]
    public function getParams()
    {
        return [];

        // if (is_string($this->params)) {
        //     return unserialize($this->params);
        // }
        // return $this->params;
    }

    /**
     * Set sendAdmin
     *
     * @param boolean $sendAdmin
     *
     * @return Mail
     */
    public function setSendAdmin($sendAdmin)
    {
        $this->sendAdmin = $sendAdmin;

        return $this;
    }

    /**
     * Get sendAdmin
     *
     * @return boolean
     */
    public function getSendAdmin()
    {
        return $this->sendAdmin;
    }

    /**
     * Set sendUser
     *
     * @param boolean $sendUser
     *
     * @return Mail
     */
    public function setSendUser($sendUser)
    {
        $this->sendUser = $sendUser;

        return $this;
    }

    /**
     * Get sendUser
     *
     * @return boolean
     */
    public function getSendUser()
    {
        return $this->sendUser;
    }
}
