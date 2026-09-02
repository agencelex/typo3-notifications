<?php declare(strict_types=1);

use Lex\Notifications\Extension;
use Lex\Notifications\NotificationChannel;
use Lex\Notifications\NotificationLevel;

$extensionKey = Extension::KEY;
$table = 'tx_lexnotifications_domain_model_message';
$lll = 'LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang_db.xlf:' . $table;
$slugifiedExtensionKey = str_replace('_', '-', $extensionKey); //lex-notifications

$tx_lexnotifications_domain_model_message = [
    'ctrl' => [
        'title' => $lll,
        'label' => 'subject',
        //'descriptionColumn' => 'message',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser',
        'delete' => 'deleted',
        'default_sortby' => 'tstamp DESC',
        'searchFields' => 'subject,message',
        'typeicon_classes' => [
            'default' => 'mimetypes-x-content-text',
        ],
        'security' => [
            //'ignorePageTypeRestriction' => true,
            //'ignoreWebMountRestriction' => true,
            //'ignoreRootLevelRestriction' => true,
        ],
    ],
    'interface' => [
        'maxDBListItems' => 30,
        'maxSingleDBListItems' => 50,
        'showRecordFieldList' => 'cruser,crdate'
    ],
    'columns' => [

        // Place config of your fields here
        'level' => [
            'exclude' => true,
            'label' => $lll . '.level.formlabel',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => collect(NotificationLevel::SUPPORTED_LEVELS)
                    ->map(fn (string $level, int $identifier) => [
                        'label' => "LLL:EXT:$extensionKey/Resources/Private/Language/locallang_db.xlf:tx_lexnotifications_domain_model_notification.level.$identifier.formlabel",
                        'value' => $identifier,
                    ])
                    ->all()
            ],
        ],
        'subject' => [
            'exclude' => true,
            'label' => $lll . '.subject.formlabel',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'required' => true,
            ],
        ],
        'message' => [
            'exclude' => true,
            'label' => $lll . '.message.formlabel',
            'config' => [
                'type' => 'text',
                'cols' => 50,
                'rows' => 15,
                'softref' => 'typolink_tag,email[subst],url',
                'required' => true,
            ],
        ],
        'link' => [
            'exclude' => true,
            'label' => $lll . '.link.formlabel',
            'config' => [
                'type' => 'link',
                'size' => 50,
                'appearance' => [
                    'browserTitle' => $lll . '.link_browser_title.formlabel',
                ],
            ],
        ],
        'receivers' => [
            'exclude' => true,
            'label' => $lll . '.receivers.formlabel',
            'config' => [
                'type' => 'group',
                'allowed' => 'fe_users,fe_groups',
                'prepend_tname' => true,
                'multiple' => true,
                'autoSizeMax' => 50,
                'required' => true,
            ],
        ],
        'excluded_recipients' => [
            'exclude' => true,
            'label' => $lll . '.excluded_recipients.formlabel',
            'config' => [
                'type' => 'group',
                'allowed' => 'fe_users,fe_groups',
                'prepend_tname' => true,
                'multiple' => true,
                'autoSizeMax' => 50,
            ],
        ],
        'channels' => [
            'exclude' => true,
            'label' => $lll . '.channels.formlabel',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'items' => [
                    [
                        'label' => ucfirst(NotificationChannel::CHANNEL_MAIL),
                        'value' => NotificationChannel::CHANNEL_MAIL,
                        'icon' => 'install-test-mail'
                    ],
                    [
                        'label' => ucfirst(NotificationChannel::CHANNEL_DATABASE),
                        'value' => NotificationChannel::CHANNEL_DATABASE,
                        'icon' => 'mimetypes-open-document-database'
                    ]
                ],
                'minitems' => 1,
                'multiSelectFilterItems' => [
                    [
                        '',
                        '',
                    ],
                    [
                        NotificationChannel::CHANNEL_MAIL, // filter value of the item
                        ucfirst(NotificationChannel::CHANNEL_MAIL), // item label
                    ],
                    [
                        NotificationChannel::CHANNEL_DATABASE,
                        ucfirst(NotificationChannel::CHANNEL_DATABASE),
                    ]
                ]
            ]
        ],
        // The owner of the message
        'cruser' => [
            'label' => $lll . '.cruser.formlabel',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'crdate' => [
            'label' => $lll . '.crdate.formlabel',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'sent_at' => [
            'exclude' => true,
            'label' => $lll . '.sent_at.formlabel',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'default' => 0,
            ],
        ],
    ],
    'palettes' => [

        // Place your palettes here
        'creation' => [
            'showitem' => '
                cruser, crdate
            ',
        ],

    ],
    'types' => [
        '0' => [
            'showitem' => '
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                    --palette--;;creation,
                    level,
                    subject,
                    message,
                    link,
                    sent_at,
                --div--;Receivers,
                    receivers,
                    excluded_recipients,
                    channels,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            '
        ]
    ]
];

return $tx_lexnotifications_domain_model_message;
