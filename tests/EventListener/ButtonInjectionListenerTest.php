<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\Tests\EventListener;

use ContaoId\ContaoBundle\EventListener\ButtonInjectionListener;
use PHPUnit\Framework\TestCase;
use Twig\Environment;

class ButtonInjectionListenerTest extends TestCase
{
    public function testDoesNothingInContao55Plus(): void
    {
        if ($this->isContao55OrNewer()) {
            $twig = $this->createMock(Environment::class);
            $twig->expects($this->never())->method('render');

            $listener = new ButtonInjectionListener($twig);
            $listener('_buffer', '_template');
        } else {
            $this->testAppendsButtonsOnLoginTemplate();
        }
    }

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
        $expectedBuffer = \sprintf('<main><form><div>Hello!%s</div></form></main>', $this->isContao55OrNewer() ? '' : '_buttons');

        $twig = $this->createMock(Environment::class);
        $twig
            ->expects($this->isContao55OrNewer() ? $this->never() : $this->once())
            ->method('render')
            ->willReturn('_buttons')
        ;

        $listener = new ButtonInjectionListener($twig);
        $appended = $listener($buffer, 'be_login');

        $this->assertSame($expectedBuffer, $appended);
    }

    private function isContao55OrNewer(): bool
    {
        return class_exists(\Contao\CoreBundle\EventListener\Menu\BackendLoginListener::class);
    }
}
