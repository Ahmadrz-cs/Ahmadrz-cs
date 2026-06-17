<?php

namespace AppBundle\Entity\Enum;

enum PayoutType: int
{
    case Dividend = 0;
    case Divestment = 1;
}
