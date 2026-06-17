<?php

namespace App\Entity\Enum;

enum OrderingDirection: string
{
    case Ascending = 'ASC';
    case Descending = 'DESC';
}
