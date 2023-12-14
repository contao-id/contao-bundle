<?php

declare(strict_types=1);

namespace Oneup\ContaoBackendSortableListViewsBundle\Tests\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use ContaoId\ContaoBundle\ContaoIdContaoBundle;
use ContaoId\ContaoBundle\ContaoManager\Plugin;
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase
{
    public function testGetsLoadedAfterCoreBundle(): void
    {
        $plugin = new Plugin();

        $bundles = $plugin->getBundles($this->createMock(ParserInterface::class));

        self::assertCount(1, $bundles);

        /** @var BundleConfig $config */
        $config = $bundles[0];

        self::assertSame(ContaoIdContaoBundle::class, $config->getName());
        self::assertSame([ContaoCoreBundle::class], $config->getLoadAfter());
    }
}
