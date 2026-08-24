<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginTargetPathListener
{
    use TargetPathTrait;

    public function __construct(
        private readonly RouterInterface $router,
        private readonly string $firewallName = 'contao_backend',
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        if ('hwi_oauth_service_redirect' !== $request->attributes->get('_route')) {
            return;
        }

        if ('contao_id' !== $request->attributes->get('service')) {
            return;
        }

        if (!$request->hasSession()) {
            return;
        }

        $redirect = $request->query->get('redirect');
        $hash = $request->query->get('_hash');

        if (!\is_string($redirect) || !\is_string($hash)) {
            return;
        }

        $this->saveTargetPath(
            $request->getSession(),
            $this->firewallName,
            $this->router->generate('contao_backend_login', ['redirect' => $redirect, '_hash' => $hash], UrlGeneratorInterface::ABSOLUTE_URL),
        );
    }
}
