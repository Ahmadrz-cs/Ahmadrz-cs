<?php

namespace App\Entity\Enum;

enum UserCategory: string
{
    case Restricted = 'restricted';
    case Sophisticated = 'sophisticated';
    case HighNetWorth = 'hnw';
    case None = 'none';
}
