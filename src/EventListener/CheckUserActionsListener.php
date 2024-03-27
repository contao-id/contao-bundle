<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\EventListener;

use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

// TODO: replace this listener with a proper voter
class CheckUserActionsListener
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Connection $connection,
    ) {
    }

    public function __invoke(?DataContainer $dataContainer): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request) {
            return;
        }

        $action = $request->get('act');

        if (null === $action) {
            return;
        }

        $id = $request->get('id');
        $user = $this->connection->executeQuery('SELECT * FROM tl_user WHERE id = :id', ['id' => $id])->fetchAssociative();

        if (false === $user) {
            return;
        }

        if (!($user['contaoIdRemoteId'] ?? null)) {
            return;
        }

        if ('copy' === $action || 'toggle' === $action) {
            throw new AccessDeniedException('Not enough permissions to perform action "' . $action . '" on user ID ' . $id);
        }
    }
}
