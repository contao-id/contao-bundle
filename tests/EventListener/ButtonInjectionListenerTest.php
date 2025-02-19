<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\Tests\EventListener;

use ContaoId\ContaoBundle\EventListener\ButtonInjectionListener;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class ButtonInjectionListenerTest extends TestCase
{
    public function testDoesNothingOnWrongTemplate(): void
    {
        $twig = $this->createMock(Environment::class);
        $twig->expects($this->never())->method('render');

        $listener = new ButtonInjectionListener($twig);
        $listener('_buffer', '_template');
    }

    public function testAppendsButtonsOnLoginTemplate(): void
    {
        $buffer = '<main><form><div>Hello!</div></form></main>';
        $expectedBuffer = '<main><form><div>Hello!_buttons</div></form></main>';

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->once())
            ->method('render')
            ->willReturn('_buttons')
        ;

        $listener = new ButtonInjectionListener($twig);
        $appended = $listener($buffer, 'be_login');

        $this->assertSame($expectedBuffer, $appended);
    }
}
