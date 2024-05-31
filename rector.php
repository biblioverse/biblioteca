<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withRules([
        AddVoidReturnTypeWhereNoReturnRector::class,
    ])
    ->withConfiguredRule(\Rector\Renaming\Rector\Name\RenameClassRector::class, [
        \App\Entity\Kobo::class => str_replace("Kobo", "KoboDevice", \App\Entity\Kobo::class),
    ])
    ->withImportNames(true, true, false, true)
    ->withSkip([
        '**/config/bundles.php',
    ]);
