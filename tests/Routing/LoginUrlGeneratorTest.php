<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\Tests\Routing;

use ContaoId\ContaoBundle\Routing\LoginUrlGenerator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class LoginUrlGeneratorTest extends TestCase
{
    public function testForwardsRedirectAndHash(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/contao/login?redirect=https%3A%2F%2Flocalhost%2Fcontao%3Fdo%3Darticle&_hash=abc'));

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with(
                'hwi_oauth_service_redirect',
                [
                    'service' => 'contao_id',
                    'redirect' => 'https://localhost/contao?do=article',
                    '_hash' => 'abc',
                ],
            )
            ->willReturn('/contao/connect/contao_id?redirect=https%3A%2F%2Flocalhost%2Fcontao%3Fdo%3Darticle&_hash=abc')
        ;

        $generator = new LoginUrlGenerator($router, $requestStack);

        $this->assertSame('/contao/connect/contao_id?redirect=https%3A%2F%2Flocalhost%2Fcontao%3Fdo%3Darticle&_hash=abc', $generator->generate());
    }

    public function testOmitsMissingParameters(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/contao/login?redirect=https%3A%2F%2Flocalhost%2Fcontao'));

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with(
                'hwi_oauth_service_redirect',
                [
                    'service' => 'contao_id',
                    'redirect' => 'https://localhost/contao',
                ],
            )
            ->willReturn('/contao/connect/contao_id?redirect=https%3A%2F%2Flocalhost%2Fcontao')
        ;

        $generator = new LoginUrlGenerator($router, $requestStack);

        $this->assertSame('/contao/connect/contao_id?redirect=https%3A%2F%2Flocalhost%2Fcontao', $generator->generate());
    }

    public function testGeneratesPlainUrlWithoutRequest(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects($this->once())
            ->method('generate')
            ->with('hwi_oauth_service_redirect', ['service' => 'contao_id'])
            ->willReturn('/contao/connect/contao_id')
        ;

        $generator = new LoginUrlGenerator($router, new RequestStack());

        $this->assertSame('/contao/connect/contao_id', $generator->generate());
    }
}
