<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\Tests\EventListener\Menu;

use Contao\CoreBundle\Event\MenuEvent;
use ContaoId\ContaoBundle\EventListener\Menu\BackendLoginListener;
use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BackendLoginListenerTest extends TestCase
{
    public function testDoesNothingWhenNotLoginMenu(): void
    {
        $tree = $this->createMock(ItemInterface::class);
        $tree
            ->expects($this->once())
            ->method('getName')
            ->willReturn('notLoginMenu')
        ;

        $event = $this->createMock(MenuEvent::class);
        $event
            ->expects($this->once())
            ->method('getTree')
            ->willReturn($tree)
        ;

        $event
            ->expects($this->never())
            ->method('getFactory')
        ;

        $translator = $this->createMock(TranslatorInterface::class);
        $router = $this->createMock(RouterInterface::class);

        $listener = new BackendLoginListener($translator, $router);
        $listener($event);
    }

    public function testCreatesMenuItem(): void
    {
        $factory = new MenuFactory();
        $menu = $factory->createItem('loginMenu');

        $event = new MenuEvent($factory, $menu);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->once())
            ->method('trans')
            ->with('login.button', [], 'ContaoIdContao')
            ->willReturn('Login with contao.id')
        ;

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('hwi_oauth_service_redirect', ['service' => 'contao_id'])
            ->willReturn('https://localhost/contao/connect/contao_id')
        ;

        $listener = new BackendLoginListener($translator, $router);
        $listener($event);

        $children = $event->getTree()->getChildren();

        $this->assertCount(1, $children);
        $this->assertSame(['contaoid'], array_keys($children));
        $this->assertSame('contaoid', $children['contaoid']->getAttribute('class'));
        $this->assertSame('Login with contao.id', $children['contaoid']->getLabel());
        $this->assertSame('https://localhost/contao/connect/contao_id', $children['contaoid']->getUri());
        $this->assertSame('tl_submit has-icon', $children['contaoid']->getLinkAttribute('class'));
        $this->assertSame(
            [
                'icon' => '/bundles/contaoidcontao/contao-id.svg',
                'icon_dark' => '/bundles/contaoidcontao/contao-id--dark.svg',
                'safe_label' => true,
                'translation_domain' => false,
            ],
            $children['contaoid']->getExtras()
        );
    }
}
