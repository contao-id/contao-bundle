<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\EventListener;

use Contao\DataContainer;
use Contao\System;

class HideCopyUserActionListener
{
    public function __invoke(
        array $row,
        ?string $href,
        string $label,
        string $title,
        ?string $icon,
        string $attributes,
        string $table,
        array $rootRecordIds,
        ?array $childRecordIds,
        bool $circularReference,
        ?string $previous,
        ?string $next,
        DataContainer $dc
    ): string {
        if ($row['contaoIdRemoteId'] ?? null) {
            return '';
        }

        /** @var \tl_user $tlUser */
        $tlUser = System::importStatic('tl_user');

        return $tlUser->copyUser($row, (string) $href, $label, $title, (string) $icon, $attributes, $table);
    }
}
