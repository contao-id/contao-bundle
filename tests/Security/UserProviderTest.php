<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\Tests\Security;

use Contao\BackendUser;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Monolog\ContaoContext;
use ContaoId\ContaoBundle\Security\UserProvider;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;

class UserProviderTest extends TestCase
{
    public function testDeletesAndLogsRevokedUsers(): void
    {
        $connection = $this->mockConnection([
            ['id' => 2, 'username' => 'rick@example.com'],
            ['id' => 3, 'username' => 'astley@example.com'],
        ]);

        $deleted = [];

        $connection
            ->expects($this->exactly(2))
            ->method('delete')
            ->willReturnCallback(static function (string $table, array $criteria) use (&$deleted): int {
                $deleted[] = [$table, $criteria];

                return 1;
            })
        ;

        $messages = [];

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->never())->method('warning');
        $logger
            ->expects($this->exactly(2))
            ->method('info')
            ->willReturnCallback(static function (string $message, array $context) use (&$messages): void {
                $messages[] = [$message, $context['contao']->getAction(), $context['contao']->getUsername()];
            })
        ;

        $this->loadUser($connection, $logger, ['01a03400-d132-7321-92e0-8c2d0ccaa420', 'other-remote-id']);

        $this->assertSame([['tl_user', ['id' => 2]], ['tl_user', ['id' => 3]]], $deleted);
        $this->assertSame(
            [
                ['User "rick@example.com" was deleted because they no longer have access via contao.id', ContaoContext::ACCESS, 'rick@example.com'],
                ['User "astley@example.com" was deleted because they no longer have access via contao.id', ContaoContext::ACCESS, 'astley@example.com'],
            ],
            $messages,
        );
    }

    public function testSkipsRemovalIfTheAuthenticatingUserIsNotListed(): void
    {
        $connection = $this->mockConnection([['id' => 1, 'username' => 'rick@example.com']]);
        $connection
            ->expects($this->never())
            ->method('fetchAllAssociative')
        ;

        $connection
            ->expects($this->never())
            ->method('delete')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->never())
            ->method('info')
        ;

        $logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'contao.id response does not list the authenticating user, skipping removal of revoked users',
                $this->callback(static fn (array $context): bool => ContaoContext::ERROR === $context['contao']->getAction()),
            )
        ;

        $this->loadUser($connection, $logger, ['other-remote-id']);
    }

    public function testSkipsRemovalIfNoClientUsersAreListed(): void
    {
        $connection = $this->mockConnection([]);
        $connection
            ->expects($this->never())
            ->method('fetchAllAssociative')
        ;

        $connection
            ->expects($this->never())
            ->method('delete')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->never())
            ->method($this->anything())
        ;

        $this->loadUser($connection, $logger, []);
    }

    public function testDoesNotDeleteAnythingIfNoUserWasRevoked(): void
    {
        $connection = $this->mockConnection([]);
        $connection
            ->expects($this->once())
            ->method('fetchAllAssociative')
        ;

        $connection
            ->expects($this->never())
            ->method('delete')
        ;

        $logger = $this->createMock(LoggerInterface::class);
        $logger
            ->expects($this->never())
            ->method($this->anything())
        ;

        $this->loadUser($connection, $logger, ['01a03400-d132-7321-92e0-8c2d0ccaa420']);
    }

    public function testWorksWithoutLogger(): void
    {
        $connection = $this->mockConnection([['id' => 2, 'username' => 'rick@example.com']]);
        $connection
            ->expects($this->once())
            ->method('delete')
            ->with('tl_user', ['id' => 2])
        ;

        $this->assertInstanceOf(BackendUser::class, $this->loadUser($connection, null, ['01a03400-d132-7321-92e0-8c2d0ccaa420']));
    }

    public function testThrowsIfTheUserRecordIsMissing(): void
    {
        $connection = $this->mockConnection([], false);

        $this->expectException(UserNotFoundException::class);

        $this->loadUser($connection, null, ['01a03400-d132-7321-92e0-8c2d0ccaa420']);
    }

    private function loadUser(Connection $connection, ?LoggerInterface $logger, array $clientUsers): UserInterface
    {
        $userProvider = new UserProvider($this->mockFramework(), $connection, $logger);

        $response = $this->createMock(UserResponseInterface::class);
        $response
            ->method('getData')
            ->willReturn([
                'id' => '01a03400-d132-7321-92e0-8c2d0ccaa420',
                'email' => 'rick@example.com',
                'firstname' => 'Rick',
                'lastname' => 'Astley',
                'language' => 'en',
                'roles' => ['admin'],
                'client_users' => $clientUsers,
            ])
        ;

        return $userProvider->loadUserByOAuthUserResponse($response);
    }

    private function mockConnection(array $revokedUsers, false|string $username = 'rick@example.com'): Connection&MockObject
    {
        $connection = $this->createMock(Connection::class);
        $connection
            ->method('executeQuery')
            ->willReturnCallback(function (string $query) use ($username): Result {
                $result = $this->createMock(Result::class);

                if (str_starts_with($query, 'SELECT id FROM tl_user WHERE email')) {
                    $result->method('fetchOne')->willReturn(1);
                }

                if (str_starts_with($query, 'SELECT username FROM tl_user')) {
                    $result->method('fetchOne')->willReturn($username);
                }

                return $result;
            })
        ;

        $connection->method('fetchAllAssociative')->willReturn($revokedUsers);

        return $connection;
    }

    private function mockFramework(): ContaoFramework&MockObject
    {
        $backendUser = $this->createMock(BackendUser::class);

        $adapter = $this->createMock(Adapter::class);
        $adapter
            ->method('__call')
            ->willReturnCallback(
                static fn (string $name): ?BackendUser => 'loadUserByIdentifier' === $name ? $backendUser : null,
            )
        ;

        $framework = $this->createMock(ContaoFramework::class);
        $framework->method('getAdapter')->with(BackendUser::class)->willReturn($adapter);

        return $framework;
    }
}
