<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\Tests\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerBundle\ContaoManagerBundle;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder;
use ContaoId\ContaoBundle\ContaoIdContaoBundle;
use ContaoId\ContaoBundle\ContaoManager\Plugin;
use HWI\Bundle\OAuthBundle\HWIOAuthBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class PluginTest extends TestCase
{
    public function testGetsLoadedAfterCoreBundle(): void
    {
        $plugin = new Plugin();

        $bundles = $plugin->getBundles($this->createMock(ParserInterface::class));

        self::assertCount(2, $bundles);

        self::assertSame(HWIOAuthBundle::class, $bundles[0]->getName());
        self::assertSame(ContaoIdContaoBundle::class, $bundles[1]->getName());
        self::assertSame([ContaoCoreBundle::class, ContaoManagerBundle::class, HWIOAuthBundle::class], $bundles[1]->getLoadAfter());
    }

    public function testRegistersTheContainerConfiguration(): void
    {
        $plugin = new Plugin();

        $loader = $this->createMock(LoaderInterface::class);
        $loader
            ->expects($this->once())
            ->method('load')
        ;

        $plugin->registerContainerConfiguration($loader, []);
    }

    public function testGetsTheExtensionConfig(): void
    {
        $plugin = new Plugin();

        $matcher = $this->exactly(2);

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects($matcher)
            ->method('hasParameter')
            ->willReturnCallback(function (string $property) use ($matcher) {
                match ($matcher->getInvocationCount()) {
                    1 => $this->assertSame('contao_id_identifier', $property),
                    2 => $this->assertSame('contao_id_secret', $property),
                };

                return false;
            })
        ;

        $matcher = $this->exactly(2);

        $container
            ->expects($matcher)
            ->method('setParameter')
            ->willReturnCallback(function (string $property, string $value) use ($matcher) {
                match ($matcher->getInvocationCount()) {
                    1 => $this->assertSame(['contao_id_identifier', '%env(CONTAO_ID_IDENTIFIER)%'], [$property, $value]),
                    2 => $this->assertSame(['contao_id_secret', '%env(CONTAO_ID_SECRET)%'], [$property, $value]),
                };

                return false;
            })
        ;

        $extensionConfigs = $plugin->getExtensionConfig('security', [['firewalls' => ['contao_backend' => []]]], $container);

        $this->assertSame([
            'oauth' => [
                'resource_owners' => [
                    'contao_id' => '/contao/login/contao_id',
                ],
                'login_path' => '/contao/login',
                'default_target_path' => '/contao',
                'use_forward' => false,
                'failure_path' => '/contao/login',
                'oauth_user_provider' => [
                    'service' => 'contao_id_contao.security.user_provider',
                ],
            ],
        ], $extensionConfigs[0]['firewalls']['contao_backend']);
    }

    public function testGetsTheRouteCollection(): void
    {
        $plugin = new Plugin();

        $loader = $this->createMock(LoaderInterface::class);
        $loader
            ->expects($this->once())
            ->method('load')
        ;

        $resolver = $this->createMock(LoaderResolverInterface::class);
        $resolver
            ->expects($this->once())
            ->method('resolve')
            ->willReturn($loader)
        ;

        $plugin->getRouteCollection($resolver, $this->createMock(KernelInterface::class));
    }
}
