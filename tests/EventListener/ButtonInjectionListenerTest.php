<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\Tests\EventListener;

use ContaoId\ContaoBundle\EventListener\ButtonInjectionListener;
use ContaoId\ContaoBundle\Routing\LoginUrlGenerator;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class ButtonInjectionListenerTest extends TestCase
{
    public function testDoesNothingOnWrongTemplate(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->expects($this->never())->method('render');

        $loginUrlGenerator = $this->createMock(LoginUrlGenerator::class);
        $loginUrlGenerator->expects($this->never())->method('generate');

        $listener = new ButtonInjectionListener($twig, $loginUrlGenerator);
        $listener('_buffer', '_template');
    }

    public function testAppendsButtonsOnLoginTemplate(): void
    {
        $buffer = '<main><form><div>Hello!</div></form></main>';
        $expectedBuffer = '<main><form><div>Hello!_buttons</div></form></main>';

        $loginUrlGenerator = $this->createMock(LoginUrlGenerator::class);
        $loginUrlGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('/contao/connect/contao_id')
        ;

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->with('@ContaoIdContao/be_login_button.html.twig', ['loginUrl' => '/contao/connect/contao_id'])
            ->willReturn('_buttons')
        ;

        $listener = new ButtonInjectionListener($twig, $loginUrlGenerator);
        $appended = $listener($buffer, 'be_login');

        $this->assertSame($expectedBuffer, $appended);
    }
}
