<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setUsingCache(true)
    ->setCacheFile(__DIR__ . '/.php-cs-fixer.cache')
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'declare_strict_types' => true,
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
        ],
        'no_unused_imports' => true,
        'single_quote' => true,
        'array_syntax' => [
            'syntax' => 'short',
        ],
        'binary_operator_spaces' => [
            'default' => 'single_space',
        ],
    ])
    ->setFinder(
        (new Finder())
            ->in([
                __DIR__ . '/src',
                __DIR__ . '/public',
                __DIR__ . '/tests',
            ])
            ->exclude([
                'vendor',
                'build',
                'pruebas',
                '.phpdoc',
            ])
    )
;
