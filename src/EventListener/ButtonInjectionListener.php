<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\EventListener;

use Twig\Environment;

class ButtonInjectionListener
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(string $buffer, string $template): string
    {
        if (class_exists(\Contao\CoreBundle\EventListener\Menu\BackendLoginListener::class)) {
            return $buffer;
        }

        if ('be_login' !== $template) {
            return $buffer;
        }

        $buttons = $this->twig->render('@ContaoIdContao/be_login_button.html.twig');
        $buttons .= '</div></form></main>';

        $buffer = preg_replace('/<\/div>(\s*)<\/form>(\s*)<\/main>/', $buttons, $buffer);

        return (string) $buffer;
    }
}
