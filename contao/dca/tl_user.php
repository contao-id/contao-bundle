<?php

declare(strict_types=1);

use Doctrine\DBAL\Types\Types;

$GLOBALS['TL_DCA']['tl_user']['fields'] = [
    ...$GLOBALS['TL_DCA']['tl_user']['fields'],
    ...[
        'contaoIdRemoteId' => [
            'exclude' => true,
            'eval' => [
                'doNotShow' => true,
                'doNotCopy' => true,
            ],
            'sql' => [
                'type' => Types::STRING,
                'default' => '',
            ],
        ],
    ]
];
