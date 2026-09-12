<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

return (new Config())
    ->setRiskyAllowed(false)
    ->setRules([
        '@PSR12' => true,
    ])
    ->setFinder(
        (new Finder())
            ->in([__DIR__ . '/src', __DIR__ . '/test'])
    )
;
