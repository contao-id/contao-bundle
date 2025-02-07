<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\Security;

use Contao\BackendUser;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\User;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * @implements UserProviderInterface<User>
 */
class UserProvider implements UserProviderInterface, OAuthAwareUserProviderInterface
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly Connection $connection,
    ) {
    }

    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        /** @var BackendUser $adapter */
        $adapter = $this->framework->getAdapter(BackendUser::class);

        $user = $adapter->loadUserByIdentifier($identifier);

        if (!$user instanceof UserInterface) {
            throw new UserNotFoundException('User not found');
        }

        return $user;
    }

    public function loadUserByOAuthUserResponse(UserResponseInterface $response): UserInterface
    {
        $this->framework->initialize();
        $data = $response->getData();

        $mail = $data['email'];
        $name = \sprintf('%s %s', $data['firstname'], $data['lastname']);
        $language = $data['language'];
        $clientUsers = (array) ($data['client_users'] ?? []);

        // Check roles
        $groups = [];
        $roles = (array) $data['roles'];

        /** @var string $role */
        foreach ($roles as $role) {
            if ('admin' === $role) {
                continue;
            }

            // Find id of corresponding group
            $statement = $this->connection->executeQuery('SELECT id FROM tl_user_group WHERE `name` = :name', [
                'name' => $role,
            ]);

            $groupId = $statement->fetchOne();

            if (false === $groupId) {
                continue;
            }

            $groups[] = $groupId;
        }

        // Check if user exists
        $statement = $this->connection->executeQuery('SELECT id FROM tl_user WHERE email = :email', [
            'email' => $mail,
        ]);

        $id = $statement->fetchOne();

        // User not found, create one
        if (false === $id) {
            $this->connection->insert('tl_user', [
                'username' => $mail,
                'name' => $name,
                'email' => $mail,
                'language' => $language,
                'backendTheme' => 'flexible',
                'uploader' => 'DropZone',
                'showHelp' => 1,
                'thumbnails' => 1,
                'useRTE' => 1,
                'useCE' => 1,
                'admin' => \in_array('admin', $roles, true) ? 1 : 0,
                'dateAdded' => time(),
                'tstamp' => time(),
                'lastLogin' => time(),
                'currentLogin' => time(),
                'contaoIdRemoteId' => $data['id'],
            ]);

            $id = $this->connection->lastInsertId();
        } else {
            $this->connection->update('tl_user', [
                'name' => $name,
                'username' => $mail,
                'email' => $mail,
                'admin' => \in_array('admin', $roles, true) ? 1 : 0,
                'tstamp' => time(),
                'lastLogin' => time(),
                'currentLogin' => time(),
                'contaoIdRemoteId' => $data['id'],
            ], [
                'id' => $id,
            ]);
        }

        // Update groups
        $this->connection->executeQuery('UPDATE tl_user SET `groups` = :groups WHERE id = :id', [
            'id' => $id,
            'groups' => serialize($groups),
        ]);

        // Delete contao.id users that do not have access anymore
        // $clientUsers should have at least one entry, since we were authenticated successfully, double check it anyway
        if (\count($clientUsers) > 0) {
            $this->connection->executeQuery('DELETE FROM tl_user WHERE contaoIdRemoteId <> "" AND contaoIdRemoteId NOT IN (:clientUsers) ', [
                'clientUsers' => $clientUsers,
            ], [
                'clientUsers' => ArrayParameterType::STRING,
            ]);
        }

        $statement = $this->connection->executeQuery('SELECT username FROM tl_user WHERE id = :id', [
            'id' => $id,
        ]);

        /** @var string $username */
        $username = $statement->fetchOne();

        return $this->loadUserByIdentifier($username);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        /** @var BackendUser $adapter */
        $adapter = $this->framework->getAdapter(BackendUser::class);

        $refreshedUser = $adapter->loadUserByIdentifier($user->getUserIdentifier());

        if (!$refreshedUser instanceof UserInterface) {
            throw new UserNotFoundException('User not found');
        }

        return $refreshedUser;
    }

    public function supportsClass(string $class): bool
    {
        return BackendUser::class === $class;
    }
}
