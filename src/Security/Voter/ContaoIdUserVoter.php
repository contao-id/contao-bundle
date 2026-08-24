<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\Security\Voter;

use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use Contao\CoreBundle\Security\Voter\DataContainer\AbstractDataContainerVoter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ContaoIdUserVoter extends AbstractDataContainerVoter
{
    public function supportsType(string $subjectType): bool
    {
        return \in_array($subjectType, [CreateAction::class, UpdateAction::class], true);
    }

    protected function getTable(): string
    {
        return 'tl_user';
    }

    protected function hasAccess(TokenInterface $token, CreateAction|DeleteAction|ReadAction|UpdateAction $action): bool
    {
        if ($action instanceof CreateAction) {
            return !$this->isContaoIdUser($action->getNew());
        }

        if ($action instanceof UpdateAction) {
            return !\array_key_exists('disable', $action->getNew() ?? [])
                || !$this->isContaoIdUser($action->getCurrent());
        }

        return true;
    }

    private function isContaoIdUser(?array $record): bool
    {
        $remoteId = $record['contaoIdRemoteId'] ?? null;

        return \is_string($remoteId) && '' !== $remoteId;
    }
}
