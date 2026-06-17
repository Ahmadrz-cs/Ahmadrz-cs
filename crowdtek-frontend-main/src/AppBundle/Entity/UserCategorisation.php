<?php

namespace AppBundle\Entity;

use AppBundle\Entity\Enum\UserCategory;

class UserCategorisation
{
    public ?UserCategory $category = null;
    public array $details = [];
}
