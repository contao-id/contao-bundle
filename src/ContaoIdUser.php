<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle;

final class ContaoIdUser
{
    public const REMOTE_ID_FIELD = 'contaoIdRemoteId';

    public const MANAGED_FIELDS = [
        'username',
        'name',
        'email',
        'groups',
        'password',
        'pwChange',
        'admin',
        'disable',
        'start',
        'stop',
        self::REMOTE_ID_FIELD,
    ];

    private function __construct()
    {
    }
}
