<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->ignoreUnknownClasses([
        'Contao\CoreBundle\DataContainer\RecordLabel', // TODO: Remove me when dropping Contao 5 support
        'PHPUnit\Framework\Attributes\DataProvider',
        'PHPUnit\Framework\TestCase',
    ])
    ->ignoreErrorsOnPackage('symfony/security-bundle', [ErrorType::UNUSED_DEPENDENCY])
;
