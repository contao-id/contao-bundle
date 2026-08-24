<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\Tests\EventListener;

use ContaoId\ContaoBundle\EventListener\LoginTargetPathListener;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class LoginTargetPathListenerTest extends TestCase
{
    private const TARGET_PATH_KEY = '_security.contao_backend.target_path';

    public function testStoresSignedLoginUrlAsTargetPath(): void
    {
        $session = $this->createSession();
        $event = $this->createEvent(
            ['_route' => 'hwi_oauth_service_redirect', 'service' => 'contao_id'],
            ['redirect' => 'https://localhost/contao?do=article', '_hash' => 'abc'],
            $session,
        );

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with(
                'contao_backend_login',
                ['redirect' => 'https://localhost/contao?do=article', '_hash' => 'abc'],
                UrlGeneratorInterface::ABSOLUTE_URL,
            )
            ->willReturn('https://localhost/contao/login?redirect=https%3A%2F%2Flocalhost%2Fcontao%3Fdo%3Darticle&_hash=abc')
        ;

        $listener = new LoginTargetPathListener($router);
        $listener($event);

        $this->assertSame(
            'https://localhost/contao/login?redirect=https%3A%2F%2Flocalhost%2Fcontao%3Fdo%3Darticle&_hash=abc',
            $session->get(self::TARGET_PATH_KEY),
        );
    }

    #[DataProvider('unrelatedRequestProvider')]
    public function testDoesNothingOnUnrelatedRequest(array $attributes, array $query): void
    {
        $session = $this->createSession();

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->never())->method('generate');

        $listener = new LoginTargetPathListener($router);
        $listener($this->createEvent($attributes, $query, $session));

        $this->assertFalse($session->has(self::TARGET_PATH_KEY));
    }

    public static function unrelatedRequestProvider(): iterable
    {
        yield 'wrong route' => [
            ['_route' => 'contao_backend_login', 'service' => 'contao_id'],
            ['redirect' => 'https://localhost/contao', '_hash' => 'abc'],
        ];

        yield 'wrong service' => [
            ['_route' => 'hwi_oauth_service_redirect', 'service' => 'google'],
            ['redirect' => 'https://localhost/contao', '_hash' => 'abc'],
        ];

        yield 'missing redirect' => [
            ['_route' => 'hwi_oauth_service_redirect', 'service' => 'contao_id'],
            ['_hash' => 'abc'],
        ];

        yield 'missing hash' => [
            ['_route' => 'hwi_oauth_service_redirect', 'service' => 'contao_id'],
            ['redirect' => 'https://localhost/contao'],
        ];
    }

    public function testDoesNothingWithoutSession(): void
    {
        $event = $this->createEvent(
            ['_route' => 'hwi_oauth_service_redirect', 'service' => 'contao_id'],
            ['redirect' => 'https://localhost/contao', '_hash' => 'abc'],
        );

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->never())->method('generate');

        $listener = new LoginTargetPathListener($router);
        $listener($event);

        $this->assertFalse($event->getRequest()->hasSession());
    }

    public function testDoesNothingOnSubRequest(): void
    {
        $session = $this->createSession();
        $event = $this->createEvent(
            ['_route' => 'hwi_oauth_service_redirect', 'service' => 'contao_id'],
            ['redirect' => 'https://localhost/contao', '_hash' => 'abc'],
            $session,
            HttpKernelInterface::SUB_REQUEST,
        );

        $router = $this->createMock(RouterInterface::class);
        $router->expects($this->never())->method('generate');

        $listener = new LoginTargetPathListener($router);
        $listener($event);

        $this->assertFalse($session->has(self::TARGET_PATH_KEY));
    }

    private function createSession(): SessionInterface
    {
        return new Session(new MockArraySessionStorage());
    }

    private function createEvent(array $attributes, array $query, ?SessionInterface $session = null, int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        $request = Request::create('/contao/connect/contao_id', 'GET', $query);
        $request->attributes->add($attributes);

        if ($session) {
            $request->setSession($session);
        }

        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, $requestType);
    }
}
