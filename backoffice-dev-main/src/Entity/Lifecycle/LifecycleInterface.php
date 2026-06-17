<?php

namespace App\Entity\Lifecycle;

interface LifecycleInterface
{
    /**
     * @return string
     */
    public static function getDefaultState();
}
