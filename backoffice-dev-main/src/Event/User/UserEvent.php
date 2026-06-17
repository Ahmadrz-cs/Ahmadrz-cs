<?php

namespace App\Event\User;

use App\Entity\User;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * The base user event class
 */
abstract class UserEvent extends Event
{
    //Concrete classes must define a name const. e.g const NAME = 'user.created'

    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function getUser()
    {
        return $this->user;
    }
}
