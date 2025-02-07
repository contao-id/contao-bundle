<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\EventListener;

use Contao\Backend;
use Contao\BackendUser;
use Contao\CoreBundle\Security\ContaoCorePermissions;
use Contao\DataContainer;
use Contao\Image;
use Contao\StringUtil;
use Symfony\Bundle\SecurityBundle\Security;

// TODO: replace this listener with a proper voter
class HideToggleUserActionListener
{
    public function __construct(
        private readonly Security $security
    ) {
    }

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

        // Check permissions AFTER checking the tid, so hacking attempts are logged
        if (!$this->security->isGranted(ContaoCorePermissions::USER_CAN_EDIT_FIELD_OF_TABLE, 'tl_user::disable')) {
            return '';
        }

        $href .= '&amp;id=' . $row['id'];

        if ($row['disable']) {
            $icon = 'invisible.svg';
        }

        $user = $this->security->getUser();

        if (!$user instanceof BackendUser) {
            return '';
        }

        // Protect admin accounts and own account
        if ((!$user->isAdmin && $row['admin']) || $user->id === $row['id']) {
            return Image::getHtml((string) $icon) . ' ';
        }

        $titleDisabled = (\is_array($GLOBALS['TL_DCA']['tl_user']['list']['operations']['toggle']['label']) && isset($GLOBALS['TL_DCA']['tl_user']['list']['operations']['toggle']['label'][2])) ? \sprintf($GLOBALS['TL_DCA']['tl_user']['list']['operations']['toggle']['label'][2], $row['id']) : $title;

        return '<a href="' . Backend::addToUrl($href) . '" title="' . StringUtil::specialchars(!$row['disable'] ? $title : $titleDisabled) . '" data-title="' . StringUtil::specialchars($title) . '" data-title-disabled="' . StringUtil::specialchars($titleDisabled) . '" data-action="contao--scroll-offset#store" onclick="return AjaxRequest.toggleField(this,true)">' . Image::getHtml((string) $icon, $label, 'data-icon="visible.svg" data-icon-disabled="invisible.svg" data-state="' . ($row['disable'] ? 0 : 1) . '"') . '</a> ';
    }
}
