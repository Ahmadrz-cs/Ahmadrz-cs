<?php

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\ListNotation\ListSyntaxFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/tests/Support/_generated',
    ])
    ->withParallel()
    ->withConfiguredRule(ArraySyntaxFixer::class, ['syntax' => 'short'])
    ->withRules([
        ListSyntaxFixer::class,
        // NoUnusedImportsFixer::class,
    ])
    ->withPhpCsFixerSets(perCS: true);
