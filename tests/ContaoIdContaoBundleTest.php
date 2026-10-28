<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\Tests;

use ContaoId\ContaoBundle\ContaoIdContaoBundle;
use ContaoId\ContaoBundle\Security\UserProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ContaoIdContaoBundleTest extends TestCase
{
    public function testLoadsServicesYaml(): void
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir());

        $bundle = new ContaoIdContaoBundle();
        $bundle->getContainerExtension()->load([], $container);

        $definitions = array_keys($container->getDefinitions());
        $definitions = array_values(array_diff($definitions, ['service_container']));

        $this->assertSame(
            [
                'contao_id_contao.contao_manager.plugin',
                'contao_id_contao.event_listener.button_injection_listener',
                'contao_id_contao.event_listener.hide_user_fields_listener',
                'contao_id_contao.event_listener.login_target_path_listener',
                'contao_id_contao.event_listener.menu.backend_login_listener',
                'contao_id_contao.event_listener.user_icon_listener',
                'contao_id_contao.routing.login_url_generator',
                'contao_id_contao.security.voter.contao_id_user_voter',
                'contao_id_contao.security.user_provider',
            ],
            $definitions,
        );

        $this->assertSame([UserProvider::class], array_keys($container->getAliases()));
        $this->assertSame('contao_id_contao.security.user_provider', (string) $container->getAlias(UserProvider::class));
    }
}
