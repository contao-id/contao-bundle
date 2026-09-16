<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle\Model;

enum ContaoIdUserField: string
{
    case Username = 'username';
    case Name = 'name';
    case Email = 'email';
    case Groups = 'groups';
    case Password = 'password';
    case PwChange = 'pwChange';
    case Admin = 'admin';
    case Disable = 'disable';
    case Start = 'start';
    case Stop = 'stop';
    case RemoteId = 'contaoIdRemoteId';

    public static function values(array $except = []): array
    {
        return array_column(
            array_filter(self::cases(), static fn (self $field) => !\in_array($field, $except, true)),
            'value',
        );
    }
}
