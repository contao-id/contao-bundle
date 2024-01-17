<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;

#[AsCallback(table: 'tl_user', target: 'list.operations.toggle.button')]
class HideToggleUserActionListener
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

        return (new \tl_user())->toggleIcon($row, (string) $href, $label, $title, (string) $icon, $attributes);
    }
}
