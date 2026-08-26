<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->ignoreUnknownClasses([
        'PHPUnit\Framework\Attributes\DataProvider',
        'PHPUnit\Framework\TestCase',
    ])
    ->ignoreErrorsOnPackage('symfony/security-bundle', [ErrorType::UNUSED_DEPENDENCY])
;
