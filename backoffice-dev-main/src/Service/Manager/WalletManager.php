<?php

/**
 * Created by PhpStorm.
 * User: keesh
 * Date: 02/02/17
 * Time: 11:21
 */

namespace App\Service\Manager;

use App\Entity\Wallet;
use App\Service\Manager\BaseManager;

class WalletManager extends BaseManager
{
    protected $entityClass = Wallet::class;
}
