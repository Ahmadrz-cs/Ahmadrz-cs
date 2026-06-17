<?php

namespace App\Event\UserDocument;

/**
 * The user_doc.updated event is dispatched each time a user document is updated.
 */
class UserDocumentUpdatedEvent extends UserDocumentEvent
{
    public const NAME = 'user_doc.updated';
}
