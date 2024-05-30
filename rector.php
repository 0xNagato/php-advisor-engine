<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php83\Rector\ClassMethod\AddOverrideAttributeToOverriddenMethodsRector;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        // __DIR__.'/bootstrap',
        __DIR__.'/config',
        // __DIR__.'/lang',
        __DIR__.'/database',
        // __DIR__.'/resources',
        // __DIR__.'/routes',
        // __DIR__.'/tests',
    ])
    ->withPhpSets()
    ->withSkip([
        AddOverrideAttributeToOverriddenMethodsRector::class,
    ])
    ->withSets([
        LaravelSetList::LARAVEL_110,
    ]);
