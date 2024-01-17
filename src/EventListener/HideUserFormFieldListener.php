<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\EventListener;

use Contao\CoreBundle\DataContainer\PaletteManipulator;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\UserModel;
use Symfony\Component\HttpFoundation\RequestStack;

#[AsCallback(table: 'tl_user', target: 'config.onload')]
class HideUserFormFieldListener
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ContaoFramework $framework,
    ) {
    }

    public function __invoke(DataContainer|null $dataContainer): void
    {
        if (null === $dataContainer || !$dataContainer->id || 'edit' !== $this->requestStack->getCurrentRequest()?->query->get('act')) {
            return;
        }

        /** @var UserModel $userModel */
        $userModel = $this->framework->getAdapter(UserModel::class);
        $user = $userModel->findById($dataContainer->id);

        if (null === $user || !$user->contaoIdRemoteId) {
            return;
        }

        foreach ($GLOBALS['TL_DCA']['tl_user']['palettes'] as $key => $palette) {
            if (!\is_string($palette)) {
                continue;
            }

            $this->removeFieldsFromPalette($key, ['password', 'pwChange', 'admin', 'disable', 'start', 'stop']);
        }

        $GLOBALS['TL_DCA']['tl_user']['fields']['username']['eval']['readonly'] = true;
        $GLOBALS['TL_DCA']['tl_user']['fields']['name']['eval']['readonly'] = true;
        $GLOBALS['TL_DCA']['tl_user']['fields']['email']['eval']['readonly'] = true;
    }

    private function removeFieldsFromPalette(string $paletteKey, array $fields): void
    {
        foreach ($fields as $field) {
            PaletteManipulator::create()
                ->removeField($field)
                ->applyToPalette($paletteKey, 'tl_user')
            ;
        }
    }
}
