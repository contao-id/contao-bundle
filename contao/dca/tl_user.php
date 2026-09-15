<?php

declare(strict_types=1);

use ContaoId\ContaoBundle\ContaoIdUser;
use Doctrine\DBAL\Types\Types;

$GLOBALS['TL_DCA']['tl_user']['fields'] = [
    ...$GLOBALS['TL_DCA']['tl_user']['fields'],
    ...[
        ContaoIdUser::REMOTE_ID_FIELD => [
            'exclude' => true,
            'eval' => [
                'doNotShow' => true,
            ],
            'sql' => [
                'type' => Types::STRING,
                'default' => '',
                'length' => 255,
            ],
        ],
    ]
];
