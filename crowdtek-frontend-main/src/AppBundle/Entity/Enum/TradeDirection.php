<?php

namespace AppBundle\Entity\Enum;

enum TradeDirection: int
{
    case Buy = 1;
    case Sell = -1;
}
