<?php

namespace App\Event\UserDocument;

use App\Entity\UserDocument;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * The base user document event class
 */
abstract class UserDocumentEvent extends Event
{
    //Concrete classes must define a name const. e.g const NAME = 'user_doc.created'

    protected $userDocument;

    public function __construct(UserDocument $userDocument)
    {
        $this->userDocument = $userDocument;
    }

    public function getUserDocument()
    {
        return $this->userDocument;
    }
}
