<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\Tests\Security\Voter;

use Contao\CoreBundle\Security\DataContainer\CreateAction;
use Contao\CoreBundle\Security\DataContainer\DeleteAction;
use Contao\CoreBundle\Security\DataContainer\ReadAction;
use Contao\CoreBundle\Security\DataContainer\UpdateAction;
use ContaoId\ContaoBundle\Security\Voter\ContaoIdUserVoter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class ContaoIdUserVoterTest extends TestCase
{
    public function testSupportsTheUserTableOnly(): void
    {
        $voter = new ContaoIdUserVoter();

        $this->assertTrue($voter->supportsAttribute('contao_dc.tl_user'));
        $this->assertFalse($voter->supportsAttribute('contao_dc.tl_member'));
    }

    public function testSupportsCreateAndUpdateActionsOnly(): void
    {
        $voter = new ContaoIdUserVoter();

        $this->assertTrue($voter->supportsType(CreateAction::class));
        $this->assertTrue($voter->supportsType(UpdateAction::class));
        $this->assertFalse($voter->supportsType(ReadAction::class));
        $this->assertFalse($voter->supportsType(DeleteAction::class));
    }

    #[DataProvider('voteProvider')]
    public function testVote(CreateAction|DeleteAction|ReadAction|UpdateAction $action, int $expected): void
    {
        $voter = new ContaoIdUserVoter();

        $this->assertSame($expected, $voter->vote($this->createMock(TokenInterface::class), $action, ['contao_dc.tl_user']));
    }

    public static function voteProvider(): iterable
    {
        yield 'copying a contao.id user is denied' => [
            new CreateAction('tl_user', ['id' => 1, 'contaoIdRemoteId' => '01a03400-d132-7321-92e0-8c2d0ccaa420']),
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'copying a local user is allowed' => [
            new CreateAction('tl_user', ['id' => 1, 'contaoIdRemoteId' => '']),
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'creating a new user is allowed' => [
            new CreateAction('tl_user'),
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'toggling a contao.id user is denied' => [
            new UpdateAction('tl_user', ['id' => 1, 'contaoIdRemoteId' => '01a03400-d132-7321-92e0-8c2d0ccaa420'], ['disable' => '1']),
            VoterInterface::ACCESS_DENIED,
        ];

        yield 'toggling a local user is allowed' => [
            new UpdateAction('tl_user', ['id' => 1, 'contaoIdRemoteId' => ''], ['disable' => '1']),
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'editing a contao.id user is allowed' => [
            new UpdateAction('tl_user', ['id' => 1, 'contaoIdRemoteId' => '01a03400-d132-7321-92e0-8c2d0ccaa420']),
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'updating other fields of a contao.id user is allowed' => [
            new UpdateAction('tl_user', ['id' => 1, 'contaoIdRemoteId' => '01a03400-d132-7321-92e0-8c2d0ccaa420'], ['name' => 'Rick']),
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'reading a contao.id user is allowed' => [
            new ReadAction('tl_user', ['id' => 1, 'contaoIdRemoteId' => '01a03400-d132-7321-92e0-8c2d0ccaa420']),
            VoterInterface::ACCESS_ABSTAIN,
        ];

        yield 'deleting a contao.id user is allowed' => [
            new DeleteAction('tl_user', ['id' => 1, 'contaoIdRemoteId' => '01a03400-d132-7321-92e0-8c2d0ccaa420']),
            VoterInterface::ACCESS_ABSTAIN,
        ];
    }

    public function testAbstainsOnOtherTables(): void
    {
        $voter = new ContaoIdUserVoter();
        $action = new CreateAction('tl_member', ['id' => 1, 'contaoIdRemoteId' => '01a03400-d132-7321-92e0-8c2d0ccaa420']);

        $this->assertSame(
            VoterInterface::ACCESS_ABSTAIN,
            $voter->vote($this->createMock(TokenInterface::class), $action, ['contao_dc.tl_member']),
        );
    }
}
