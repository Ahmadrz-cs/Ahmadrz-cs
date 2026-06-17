<?php

namespace App\Entity;

use App\Entity\Log;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class UserLog
 * @package App\Entity
 */
#[ORM\Table(name: 'userLogs')]
#[ORM\Entity]
class UserLog extends Log
{
    public const TYPE_USER = 2;

    /**
     * @var User
     */
    #[ORM\ManyToOne(targetEntity: 'App\Entity\User', inversedBy: 'logs')]
    protected $user;

    /**
     * @param $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    public function __toString()
    {
        $return = parent::__toString();

        $return = str_replace('%userid%', $this->user->getId(), $return);
        $return = str_replace('%username%', $this->user->getUsername(), $return);
        $return = str_replace('%useremail%', $this->user->getEmail(), $return);
        $return = str_replace(
            '%birthcountry%',
            $this->user->getBirthCountry(),
            $return,
        );
        return $return;
    }
}
