<?php

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\FunctionNotation\FunctionDeclarationFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\ListNotation\ListSyntaxFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;

return ECSConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests'
    ])
    ->withSkip([
        __DIR__ . '/tests/_support/_generated',
    ])
    ->withParallel()
    ->withConfiguredRule(
        ArraySyntaxFixer::class,
        ['syntax' => 'short']
    )
    ->withConfiguredRule(
        FunctionDeclarationFixer::class,
        ['closure_fn_spacing' => FunctionDeclarationFixer::SPACING_NONE]
    )
    ->withRules([
        ListSyntaxFixer::class,
        // NoUnusedImportsFixer::class,
    ])
    ->withPreparedSets(psr12: true);
