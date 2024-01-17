<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\EventListener;

use Contao\Backend;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\Image;
use Contao\StringUtil;

#[AsCallback(table: 'tl_user', target: 'list.operations.copy.button')]
#[AsCallback(table: 'tl_user', target: 'list.operations.toggle.button')]
class HideUserActionsListener
{
    public function __invoke(array $row, ?string $href, string $label, string $title, ?string $icon, string $attributes): string
    {
        if ($row['contaoIdRemoteId']) {
            return '';
        }

        return sprintf('<a href="%s" title="%s"%s>%s</a> ', Backend::addToUrl($href . '&amp;id=' . $row['id']), StringUtil::specialchars($title), $attributes, Image::getHtml((string) $icon, $label));
    }
}
