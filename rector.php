<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\FuncCall\ArrayMergeOfNonArraysToSimpleArrayRector;
use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/tests',
    ])
    ->withPhpSets(php84: true)
    ->withSets([
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::TYPE_DECLARATION,
    ])
    ->withSkip([
        // Flattens grouped array_merge into one unreadable line — readability intentional
        ArrayMergeOfNonArraysToSimpleArrayRector::class,
    ]);
