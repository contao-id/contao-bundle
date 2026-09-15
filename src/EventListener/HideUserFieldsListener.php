<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\EventListener;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\UserModel;
use ContaoId\ContaoBundle\ContaoIdUser;
use Symfony\Component\HttpFoundation\RequestStack;

class HideUserFieldsListener
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ContaoFramework $framework,
    ) {
    }

    public function __invoke(?DataContainer $dataContainer): void
    {
        if (null === $dataContainer || !$this->containsContaoIdUser($dataContainer)) {
            return;
        }

        $readonly = ['username', 'name', 'email'];
        $disabled = ['groups'];

        $hiddenFields = array_diff(ContaoIdUser::MANAGED_FIELDS, $readonly, $disabled);

        // @phpstan-ignore foreach.nonIterable, offsetAccess.nonOffsetAccessible, offsetAccess.nonOffsetAccessible
        foreach ($GLOBALS['TL_DCA']['tl_user']['palettes'] as $palette => $fields) {
            if (!\is_string($fields) || !\is_string($palette)) {
                continue;
            }

            $this->removeFieldsFromPalette($palette, $hiddenFields);
        }

        foreach ($readonly as $field) {
            // @phpstan-ignore-next-line
            $GLOBALS['TL_DCA']['tl_user']['fields'][$field]['eval']['readonly'] = true;
        }

        foreach ($disabled as $field) {
            // @phpstan-ignore-next-line
            $GLOBALS['TL_DCA']['tl_user']['fields'][$field]['eval']['disabled'] = true;
        }
    }

    private function containsContaoIdUser(DataContainer $dataContainer): bool
    {
        if ([] === $ids = $this->getIds($dataContainer)) {
            return false;
        }

        /** @var UserModel $userModelAdapter */
        // @phpstan-ignore varTag.nativeType
        $userModelAdapter = $this->framework->getAdapter(UserModel::class);
        $users = $userModelAdapter->findMultipleByIds($ids);

        if (null === $users) {
            return false;
        }

        foreach ($users as $user) {
            if ($user->{ContaoIdUser::REMOTE_ID_FIELD}) {
                return true;
            }
        }

        return false;
    }

    private function getIds(DataContainer $dataContainer): array
    {
        if (null === $request = $this->requestStack->getCurrentRequest()) {
            return [];
        }

        $act = $request->query->get('act');

        if ('edit' === $act) {
            return $dataContainer->id ? [$dataContainer->id] : [];
        }

        if (!\in_array($act, ['editAll', 'overrideAll'], true) || !$request->hasSession()) {
            return [];
        }

        $session = $request->getSession()->all();
        $ids = $session['CURRENT']['IDS'] ?? [];

        return \is_array($ids) ? array_values($ids) : [];
    }

    private function removeFieldsFromPalette(string $palette, array $fields): void
    {
        foreach ($fields as $field) {
            PaletteManipulator::create()
                ->removeField($field)
                ->applyToPalette($palette, 'tl_user')
            ;
        }
    }
}
