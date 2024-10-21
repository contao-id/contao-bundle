<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerBundle\ContaoManagerBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use ContaoId\ContaoBundle\ContaoIdContaoBundle;
use HWI\Bundle\OAuthBundle\HWIOAuthBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

final class Plugin implements BundlePluginInterface, ConfigPluginInterface, ExtensionPluginInterface, RoutingPluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(HWIOAuthBundle::class),
            BundleConfig::create(ContaoIdContaoBundle::class)
                ->setLoadAfter([
                    ContaoCoreBundle::class,
                    ContaoManagerBundle::class,
                    HWIOAuthBundle::class,
                ]),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig): void
    {
        $loader
            ->load(__DIR__ . '/../../config/hwi_oauth.yaml');
    }

    public function getExtensionConfig($extensionName, array $extensionConfigs, ContainerBuilder $container): array
    {
        $this->enhanceSecurityConfig($extensionName, $extensionConfigs, $container);

        if (!$container->hasParameter('contao_id_identifier')) {
            $container->setParameter('env(CONTAO_ID_IDENTIFIER)', '');
            $container->setParameter('contao_id_identifier', '%env(CONTAO_ID_IDENTIFIER)%');
        }

        if (!$container->hasParameter('contao_id_secret')) {
            $container->setParameter('env(CONTAO_ID_SECRET)', '');
            $container->setParameter('contao_id_secret', '%env(CONTAO_ID_SECRET)%');
        }

        return $extensionConfigs;
    }

    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): ?RouteCollection
    {
        return $resolver
            ->resolve(__DIR__ . '/../../config/routes.yaml')
            ->load(__DIR__ . '/../../config/routes.yaml')
        ;
    }

    private function enhanceSecurityConfig(string $extensionName, array &$extensionConfigs, ContainerBuilder $container): void
    {
        if ('security' !== $extensionName) {
            return;
        }

        foreach ($extensionConfigs as &$extensionConfig) {
            if (isset($extensionConfig['firewalls']['contao_backend'])) {
                $extensionConfig['firewalls']['contao_backend']['oauth'] = [
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
                ];

                break;
            }
        }
    }
}
