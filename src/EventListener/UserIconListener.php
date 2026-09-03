<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\EventListener;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\DataContainer\RecordLabel;
use Contao\DataContainer;
use Contao\System;
use Twig\Environment;

class UserIconListener
{
    public function __construct(
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(array $row, string $label, DataContainer $dataContainer, array $labels): array|RecordLabel
    {
        /** @var \tl_user $tlUser */
        $tlUser = System::importStatic('tl_user');
        $labels = $tlUser->addIcon($row, $label, $dataContainer, $labels);

        if (!($row['contaoIdRemoteId'] ?? null)) {
            return $labels;
        }

        $html = $this->twig->render('@ContaoIdContao/user_icon.html.twig', [
            'isAdmin' => (bool) $row['admin'],
            'isLegacy' => version_compare(ContaoCoreBundle::getVersion(), '5.5.0', '<='),
        ]);

        if (class_exists(RecordLabel::class)) {
            return RecordLabel::fromHtml($html);
        }

        $labels[0] = $html;

        return $labels;
    }
}
