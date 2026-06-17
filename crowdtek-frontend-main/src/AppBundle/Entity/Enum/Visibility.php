<?php

namespace AppBundle\Entity\Enum;

enum Visibility: string
{
    case Auto = 'auto'; // No restrictions
    case Admin = 'admin'; // Admin only
    case Vip = 'vip'; // Top Yielders or admin
}
