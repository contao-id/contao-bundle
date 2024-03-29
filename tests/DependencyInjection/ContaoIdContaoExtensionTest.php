<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\Tests\DependencyInjection;

use ContaoId\ContaoBundle\DependencyInjection\ContaoIdContaoExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContaoIdContaoExtensionTest extends TestCase
{
    public function testLoadsServicesYaml(): void
    {
        $extension = new ContaoIdContaoExtension();
        $containerBuilder = new ContainerBuilder();

        $extension->load([], $containerBuilder);
        $definitions = array_keys($containerBuilder->getDefinitions());

        self::assertGreaterThan(1, $definitions);
    }
}
