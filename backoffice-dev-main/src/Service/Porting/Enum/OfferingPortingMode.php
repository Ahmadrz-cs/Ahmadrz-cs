<?php

namespace App\Service\Porting\Enum;

enum OfferingPortingMode: string
{
    case FirstParty = 'first-party';
    case Relisting = 'relisting';
}
