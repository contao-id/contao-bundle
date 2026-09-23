<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\Security;

use Contao\BackendUser;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use Contao\User;
use ContaoId\ContaoBundle\Model\ContaoIdUserField;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthAwareUserProviderInterface;
use Psr\Log\LoggerInterface;
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
        private readonly LoggerInterface $logger,
    ) {
    }

    public function loadUserByUsername(string $username): UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        /** @var BackendUser $adapter */
        // @phpstan-ignore varTag.nativeType
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
                ContaoIdUserField::RemoteId->value => $data['id'],
            ]);

            $id = $this->connection->lastInsertId();

            if (version_compare(ContaoCoreBundle::getVersion(), '6.0.0', '<')) {
                $this->connection->update('tl_user', ['backendTheme' => 'flexible'], ['id' => $id]);
            }
        } else {
            $this->connection->update('tl_user', [
                'name' => $name,
                'username' => $mail,
                'email' => $mail,
                'admin' => \in_array('admin', $roles, true) ? 1 : 0,
                'tstamp' => time(),
                'lastLogin' => time(),
                'currentLogin' => time(),
                ContaoIdUserField::RemoteId->value => $data['id'],
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
        $this->removeRevokedUsers(\is_scalar($data['id']) ? (string) $data['id'] : '', $clientUsers);

        $statement = $this->connection->executeQuery('SELECT username FROM tl_user WHERE id = :id', [
            'id' => $id,
        ]);

        $username = $statement->fetchOne();

        if (!\is_string($username)) {
            throw new UserNotFoundException('User not found');
        }

        return $this->loadUserByIdentifier($username);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        /** @var BackendUser $adapter */
        // @phpstan-ignore varTag.nativeType
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

    private function removeRevokedUsers(string $remoteId, array $clientUsers): void
    {
        // Remove empty string IDs
        $clientUsers = array_values(
            array_filter(
                $clientUsers,
                static fn (mixed $clientUser): bool => \is_string($clientUser) && '' !== $clientUser,
            )
        );

        // $clientUsers should have at least one entry, since we were authenticated successfully, double check it anyway
        if (0 === \count($clientUsers)) {
            return;
        }

        if (!\in_array($remoteId, $clientUsers, true)) {
            $this->logger->warning(
                'contao.id response does not list the authenticating user, skipping removal of revoked users',
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::ERROR)],
            );

            return;
        }

        $revokedUsers = $this->connection->fetchAllAssociative(
            \sprintf('SELECT id, username FROM tl_user WHERE %1$s <> "" AND %1$s NOT IN (:clientUsers)', ContaoIdUserField::RemoteId->value),
            ['clientUsers' => $clientUsers],
            ['clientUsers' => ArrayParameterType::STRING],
        );

        foreach ($revokedUsers as $revokedUser) {
            $this->connection->delete('tl_user', ['id' => $revokedUser['id']]);

            $username = \is_string($revokedUser['username']) ? $revokedUser['username'] : '';

            $this->logger->info(
                \sprintf('User "%s" was deleted because they no longer have access via contao.id', $username),
                ['contao' => new ContaoContext(__METHOD__, ContaoContext::ACCESS, $username)],
            );
        }
    }
}
