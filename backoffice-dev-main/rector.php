<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php84\Rector\MethodCall\NewMethodCallWithoutParenthesesRector;
use Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/tests',
        __DIR__ . '/src',
    ])
    ->withRules([
        ExplicitNullableParamTypeRector::class,
        NewMethodCallWithoutParenthesesRector::class,
    ])
    // ->withPhpSets(php84: true)
;
