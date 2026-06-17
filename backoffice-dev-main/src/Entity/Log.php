<?php

namespace App\Entity;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Blameable\Traits\BlameableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class StatusHistory
 * @package App\Entity
 */
#[ORM\MappedSuperclass]
class Log
{
    use TimestampableEntity;

    use BlameableEntity;

    public const TYPE_SYSTEM = 1;

    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * @var string $type The type of event occured
     */
    #[ORM\Column(name: '`type`')]
    protected $type;

    /**
     * @var string
     */
    #[ORM\Column]
    protected $event;

    /**
     * @var string $message commentary on what to log
     */
    #[ORM\Column]
    protected $message;

    /**
     * They key/field that was changed
     *
     * @var string
     */
    #[ORM\Column(name: '`key`', nullable: true)]
    #[Assert\NotBlank]
    protected $key;

    /**
     * Old value of the key
     *
     * @var mixed
     */
    #[ORM\Column(nullable: true)]
    protected $oldValue;

    /**
     * New value of the key
     *
     * @var mixed
     */
    #[ORM\Column(nullable: true)]
    protected $newValue;

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
     * Set key
     *
     * @param string $key
     *
     * @return Log
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set oldValue
     *
     * @param string $oldValue
     *
     * @return Log
     */
    public function setOldValue($oldValue)
    {
        $this->oldValue = $oldValue;

        return $this;
    }

    /**
     * Get oldValue
     *
     * @return string
     */
    public function getOldValue()
    {
        return $this->oldValue;
    }

    /**
     * Set newValue
     *
     * @param string $newValue
     *
     * @return Log
     */
    public function setNewValue($newValue)
    {
        $this->newValue = $newValue;

        return $this;
    }

    /**
     * Get newValue
     *
     * @return string
     */
    public function getNewValue()
    {
        return $this->newValue;
    }

    /**
     * Set createdOn
     *
     * @param \DateTime $createdOn
     *
     * @return Log
     */
    public function setCreatedOn($createdOn)
    {
        $this->createdOn = $createdOn;

        return $this;
    }

    /**
     * @param string $message
     * @return $this
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param $event
     * @return $this
     */
    public function setEvent($event)
    {
        $this->event = $event;

        return $this;
    }

    /**
     * @return string
     */
    public function getEvent()
    {
        return $this->event;
    }

    public function __toString()
    {
        $return = (string) str_replace(
            '%timestamp%',
            $this->createdAt->format('d M Y @ H:i'),
            $this->message,
        );

        $return = (string) str_replace('%oldvalue%', $this->getOldValue(), $return);

        $return = (string) str_replace('%newvalue%', $this->getNewValue(), $return);

        return $return;
    }
}
