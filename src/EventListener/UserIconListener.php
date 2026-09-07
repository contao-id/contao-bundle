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

    // TODO: Remove phpstan-ignore's when dropping Contao 5 support
    // @phpstan-ignore class.notFound
    public function __invoke(array $row, string $label, DataContainer $dataContainer, array $labels): array|RecordLabel
    {
        /** @var \tl_user $tlUser */
        $tlUser = System::importStatic('tl_user');

        /** @var array|RecordLabel $newLabels */ // @phpstan-ignore class.notFound
        $newLabels = $tlUser->addIcon($row, $label, $dataContainer, $labels);

        if (!($row['contaoIdRemoteId'] ?? null)) {
            return $newLabels;
        }

        $html = $this->twig->render('@ContaoIdContao/user_icon.html.twig', [
            'isAdmin' => (bool) $row['admin'],
            'isLegacy' => version_compare(ContaoCoreBundle::getVersion(), '5.5.0', '<='),
        ]);

        // @phpstan-ignore class.notFound
        if ($newLabels instanceof RecordLabel) {
            $newLabels->htmlColumns[0] = $html; // @phpstan-ignore class.notFound, class.notFound

            return $newLabels;
        }

        $newLabels[0] = $html;

        return $newLabels;
    }
}
