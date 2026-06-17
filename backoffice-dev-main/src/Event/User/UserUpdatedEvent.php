<?php

namespace App\Event\User;

/**
 * The user.updated event is dispatched each time a user is updated.
 */
class UserUpdatedEvent extends UserEvent
{
    public const NAME = 'user.updated';
}
