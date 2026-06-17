<?php

namespace App\Event\User;

/**
 * The user.created event is dispatched each time a user is created.
 */
class UserCreatedEvent extends UserEvent
{
    public const NAME = 'user.created';
}
