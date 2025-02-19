<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\EventListener\Menu;

use Contao\CoreBundle\Event\MenuEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class BackendLoginListener
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly RouterInterface $router,
    ) {
    }

    public function __invoke(MenuEvent $event): void
    {
        $tree = $event->getTree();

        if ('loginMenu' !== $tree->getName()) {
            return;
        }

        $contaoId = $event->getFactory()
            ->createItem('contaoid')
            ->setAttribute('class', 'contaoid')
            ->setLabel($this->translator->trans('login.button', [], 'ContaoIdContao'))
            ->setUri($this->router->generate('hwi_oauth_service_redirect', ['service' => 'contao_id']))
            ->setLinkAttribute('class', 'tl_submit has-icon')
            ->setExtra('icon', '/bundles/contaoidcontao/contao-id.svg')
            ->setExtra('icon_dark', '/bundles/contaoidcontao/contao-id--dark.svg')
            ->setExtra('safe_label', true)
            ->setExtra('translation_domain', false)
        ;

        $tree->addChild($contaoId);
    }
}
