<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\Routing;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class LoginUrlGenerator
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function generate(): string
    {
        $parameters = ['service' => 'contao_id'];

        if ($request = $this->requestStack->getCurrentRequest()) {
            foreach (['redirect', '_hash'] as $key) {
                if (\is_string($value = $request->query->get($key))) {
                    $parameters[$key] = $value;
                }
            }
        }

        return $this->router->generate('hwi_oauth_service_redirect', $parameters);
    }
}
