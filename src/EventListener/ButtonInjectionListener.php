<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Twig\Environment;

#[AsHook(hook: 'parseBackendTemplate', priority: 0)]
class ButtonInjectionListener
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(string $buffer, string $template): string
    {
        if ('be_login' !== $template) {
            return $buffer;
        }

        $buttons = $this->twig->render('@ContaoIdContao/be_login_button.html.twig');
        $buttons .= '</div></form></main>';

        $buffer = preg_replace('/\<\/div\>(\s*)\<\/form\>(\s*)\<\/main\>/', $buttons, $buffer);

        return (string) $buffer;
    }
}
