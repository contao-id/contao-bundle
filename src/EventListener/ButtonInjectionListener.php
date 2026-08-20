<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\EventListener;

use ContaoId\ContaoBundle\Routing\LoginUrlGenerator;
use Twig\Environment;

class ButtonInjectionListener
{
    public function __construct(
        private readonly Environment $twig,
        private readonly LoginUrlGenerator $loginUrlGenerator,
    ) {
    }

    public function __invoke(string $buffer, string $template): string
    {
        if ('be_login' !== $template) {
            return $buffer;
        }

        $buttons = $this->twig->render('@ContaoIdContao/be_login_button.html.twig', ['loginUrl' => $this->loginUrlGenerator->generate()]);
        $buttons .= '</div></form></main>';

        $buffer = preg_replace('/<\/div>(\s*)<\/form>(\s*)<\/main>/', $buttons, $buffer);

        return (string) $buffer;
    }
}
