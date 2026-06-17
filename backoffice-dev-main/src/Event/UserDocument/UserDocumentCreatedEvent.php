<?php

namespace App\Event\UserDocument;

/**
 * The user_doc.created event is dispatched each time a user document is created.
 */
class UserDocumentCreatedEvent extends UserDocumentEvent
{
    public const NAME = 'user_doc.created';
}
