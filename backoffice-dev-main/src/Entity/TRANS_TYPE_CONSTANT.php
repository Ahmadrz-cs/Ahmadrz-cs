<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 19/12/18
 * Time: 23:46
 */

namespace App\Entity;

/***
 * Class TRANS_TYPE_CONSTANT
 * @package App\Entity
 *
 * list of Transaction types
 *
 */
class TRANS_TYPE_CONSTANT
{
    public const TRANS_NP = 'NP'; //normal purchase
    public const TRANS_SP = 'SP'; //secondary purchase
    public const TRANS_DIV = 'DIV'; //dividend payment
}
