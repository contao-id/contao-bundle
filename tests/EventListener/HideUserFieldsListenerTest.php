<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\Tests\EventListener;

use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Model\Collection;
use Contao\UserModel;
use ContaoId\ContaoBundle\EventListener\HideUserFieldsListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class HideUserFieldsListenerTest extends TestCase
{
    private const PALETTE = '
        {name_legend},username,name,email;
        {backend_legend:hide},language,uploader,showHelp,thumbnails,useRTE,useCE,doNotCollapse;
        {theme_legend:hide},backendTheme;
        {password_legend:hide},password,pwChange;
        {admin_legend},admin;
        {groups_legend},groups,inherit;
        {account_legend},disable,start,stop
    ';

    protected function setUp(): void
    {
        $GLOBALS['TL_DCA']['tl_user'] = [
            'palettes' => ['default' => self::PALETTE],
            'fields' => [
                'username' => ['eval' => []],
                'name' => ['eval' => []],
                'email' => ['eval' => []],
            ],
        ];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);
    }

    public function testHidesTheFieldsInTheSingleRecordEditView(): void
    {
        $listener = new HideUserFieldsListener(
            $this->mockRequestStack(['act' => 'edit']),
            $this->mockContaoFramework(['01a03400-d132-7321-92e0-8c2d0ccaa420']),
        );

        $listener($this->mockDataContainer(1));

        $this->assertFieldsAreRemoved();
    }

    public function testHidesTheFieldsInTheMassEditViews(): void
    {
        foreach (['editAll', 'overrideAll'] as $act) {
            $this->setUp();

            $listener = new HideUserFieldsListener(
                $this->mockRequestStack(['act' => $act], [2, 3]),
                $this->mockContaoFramework(['', '01a03400-d132-7321-92e0-8c2d0ccaa420']),
            );

            $listener($this->mockDataContainer());

            $this->assertFieldsAreRemoved($act);
        }
    }

    public function testKeepsTheFieldsForLocalUsersOnly(): void
    {
        $listener = new HideUserFieldsListener(
            $this->mockRequestStack(['act' => 'editAll'], [2, 3]),
            $this->mockContaoFramework(['', '']),
        );

        $listener($this->mockDataContainer());

        $this->assertSame(self::PALETTE, $GLOBALS['TL_DCA']['tl_user']['palettes']['default']);
        $this->assertArrayNotHasKey('readonly', $GLOBALS['TL_DCA']['tl_user']['fields']['email']['eval']);
    }

    public function testDoesNothingWithoutASessionInTheMassEditViews(): void
    {
        $framework = $this->createMock(ContaoFramework::class);
        $framework->expects($this->never())->method('getAdapter');

        $listener = new HideUserFieldsListener($this->mockRequestStack(['act' => 'editAll']), $framework);
        $listener($this->mockDataContainer());

        $this->assertSame(self::PALETTE, $GLOBALS['TL_DCA']['tl_user']['palettes']['default']);
    }

    public function testDoesNothingOnUnrelatedActions(): void
    {
        $framework = $this->createMock(ContaoFramework::class);
        $framework->expects($this->never())->method('getAdapter');

        $listener = new HideUserFieldsListener($this->mockRequestStack(['act' => 'select'], [2]), $framework);
        $listener($this->mockDataContainer(1));

        $this->assertSame(self::PALETTE, $GLOBALS['TL_DCA']['tl_user']['palettes']['default']);
    }

    public function testDoesNothingWithoutADataContainer(): void
    {
        $framework = $this->createMock(ContaoFramework::class);
        $framework->expects($this->never())->method('getAdapter');

        $listener = new HideUserFieldsListener($this->mockRequestStack(['act' => 'edit']), $framework);
        $listener(null);

        $this->assertSame(self::PALETTE, $GLOBALS['TL_DCA']['tl_user']['palettes']['default']);
    }

    private function assertFieldsAreRemoved(string $context = 'edit'): void
    {
        $palette = $GLOBALS['TL_DCA']['tl_user']['palettes']['default'];

        foreach (['password', 'pwChange', 'admin', 'disable', 'start', 'stop'] as $field) {
            $this->assertStringNotContainsString($field, $palette, \sprintf('"%s" must not be editable (%s)', $field, $context));
        }

        foreach (['username', 'name', 'email'] as $field) {
            $this->assertTrue(
                $GLOBALS['TL_DCA']['tl_user']['fields'][$field]['eval']['readonly'],
                \sprintf('"%s" must be read-only (%s)', $field, $context),
            );
        }
    }

    private function mockRequestStack(array $query, ?array $sessionIds = null): RequestStack
    {
        $request = Request::create('/contao', 'GET', $query);

        if (null !== $sessionIds) {
            $session = new Session(new MockArraySessionStorage());
            $session->set('CURRENT', ['IDS' => $sessionIds]);
            $request->setSession($session);
        }

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }

    private function mockContaoFramework(array $remoteIds): ContaoFramework
    {
        $users = array_map(
            function (string $remoteId): UserModel {
                $user = $this->createMock(UserModel::class);
                $user->method('__get')->willReturnCallback(
                    static fn (string $key): ?string => 'contaoIdRemoteId' === $key ? $remoteId : null,
                );

                return $user;
            },
            $remoteIds,
        );

        $adapter = $this->createMock(Adapter::class);
        $adapter
            ->method('__call')
            ->willReturnCallback(
                static fn (string $name): ?Collection => 'findMultipleByIds' === $name ? new Collection($users, 'tl_user') : null,
            )
        ;

        $framework = $this->createMock(ContaoFramework::class);
        $framework->method('getAdapter')->with(UserModel::class)->willReturn($adapter);

        return $framework;
    }

    private function mockDataContainer(?int $id = null): DataContainer
    {
        $dataContainer = $this->createMock(DataContainer::class);
        $dataContainer->method('__get')->willReturnCallback(
            static fn (string $key): ?int => 'id' === $key ? $id : null,
        );

        return $dataContainer;
    }
}
