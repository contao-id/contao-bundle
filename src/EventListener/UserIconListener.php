<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\System;
use Twig\Environment;

#[AsCallback(table: 'tl_user', target: 'list.label.label')]
class UserIconListener
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(array $row, string $label, DataContainer $dataContainer, array $labels): array
    {
        /** @var \tl_user $tlUser */
        $tlUser = System::importStatic('tl_user');
        $labels = $tlUser->addIcon($row, $label, $dataContainer, $labels);

        if (!($row['contaoIdRemoteId'] ?? null)) {
            return $labels;
        }

        $labels[0] = $this->twig->render('@ContaoIdContao/user_icon.html.twig', [
            'isAdmin' => (bool) $row['admin'],
        ]);

        return $labels;
    }
}
