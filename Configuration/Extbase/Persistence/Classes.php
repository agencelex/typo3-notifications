<?php declare(strict_types=1);

use Lex\Notifications\Domain\Model\DatabaseNotification;
use Lex\Notifications\Domain\Model\Message;
use Lex\Notifications\Domain\Model\NotifiableFrontendUser;

return [
    DatabaseNotification::class => [
        'tableName' => 'tx_lexnotifications_domain_model_notification',
        'properties' => [
            'createdAt' => [
                'fieldName' => 'crdate',
            ]
        ]
    ],

    Message::class => [
        'tableName' => 'tx_lexnotifications_domain_model_message'
    ],

    NotifiableFrontendUser::class => [
        'tableName' => 'fe_users'
    ]
];