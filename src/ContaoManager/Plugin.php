<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use ContaoId\ContaoBundle\ContaoIdContaoBundle;
use HWI\Bundle\OAuthBundle\HWIOAuthBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

final class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(ContaoIdContaoBundle::class)
                ->setLoadAfter([
                    ContaoCoreBundle::class,
                    HWIOAuthBundle::class,
                ]),
        ];
    }

    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): ?RouteCollection
    {
        return $resolver
            ->resolve(__DIR__ . '/../config/routes.yaml')
            ->load(__DIR__ . '/../config/routes.yaml')
        ;
    }
}
