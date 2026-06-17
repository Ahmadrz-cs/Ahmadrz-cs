<?php

namespace App\Event\User;

/**
 * The user_email.verified event is dispatched each time a user is updated.
 */
class UserEmailVerifiedEvent extends UserEvent
{
    public const NAME = 'user_email.verified';
}
