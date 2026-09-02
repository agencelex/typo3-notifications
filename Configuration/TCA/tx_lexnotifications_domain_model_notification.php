<?php declare(strict_types=1);

use Lex\Notifications\Extension;
use Lex\Notifications\NotificationLevel;

$extensionKey = Extension::KEY;
$table = 'tx_lexnotifications_domain_model_notification';
$lll = 'LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang_db.xlf:' . $table;
$slugifiedExtensionKey = str_replace('_', '-', $extensionKey); //lex-notifications

$tx_lexnotifications_domain_model_notification = [
    'ctrl' => [
        'title' => $lll,
        'label' => 'type',
        'label_alt' => 'notifiable_id,notifiable_type',
        //'descriptionColumn' => 'level', <= Uncomment this leads to a nl2br exception in the view
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'delete' => 'deleted',

        'versioningWS' => true,
        'origUid' => 't3_origuid',

        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        //'translationSource' => 'l10n_source',

        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
            'fe_group' => 'fe_group',
        ],
        'searchFields' => 'uid,type,notifiable,notifiable_type,level', // Use fields of you table
        'typeicon_classes' => [
            'default' => $slugifiedExtensionKey . '-notification',
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
    ],
    'columns' => [

        // Place config of your fields here
        'type' => [
            'exclude' => true,
            'label' => $lll . '.type.formlabel',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'notifiable_id' => [
            'exclude' => true,
            'label' => $lll . '.notifiable_id.formlabel',
            'config' => [
                'type' => 'input',
                'size' => 10,
                'eval' => 'int',
                'required' => true,
            ],
        ],
        'notifiable_type' => [
            'exclude' => true,
            'label' => $lll . '.notifiable_type.formlabel',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'eval' => 'trim',
                'required' => true,
            ],
        ],
        'level' => [
            'exclude' => true,
            'label' => $lll . '.level.formlabel',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => collect(NotificationLevel::SUPPORTED_LEVELS)
                    ->map(fn (string $level, int $identifier) => [
                        'label' => $lll . ".level.$identifier.formlabel",
                        'value' => $identifier,
                    ])
                    ->all()
            ],
        ],
        'data' => [
            'exclude' => true,
            'label' => $lll . '.data.formlabel',
            'config' => [
                'type' => 'json',
                'cols' => 40,
                'rows' => 15,
                'enableCodeEditor' => true
            ],
        ],
        'read_at' => [
            'exclude' => true,
            'label' => $lll . '.read_at.formlabel',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'default' => 0,
            ],
        ],

        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'items' => [
                    [
                        'label' => '',
                        'invertStateDisplay' => true
                    ]
                ],
            ],
        ],

        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'readOnly' => true,
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'datetime',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038),
                ],
                'readOnly' => true,
            ],
            'l10n_mode' => 'exclude',
            'l10n_display' => 'defaultAsReadonly',
        ],

        'fe_group' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'items' => [
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hide_at_login',
                        'value' => -1,
                    ],
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.any_login',
                        'value' => -2,
                    ],
                    [
                        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.usergroups',
                        'value' => '--div--',
                    ],
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'readOnly' => true,
            ],
        ],

        'crdate' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
    ],
    'palettes' => [

        // Place your palettes here
        // ...

        'language' => [
            'showitem' => '
                sys_language_uid;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:sys_language_uid_formlabel,l10n_parent
            ',
        ],
        'hidden' => [
            'showitem' => '
                hidden;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:field.default.hidden
            ',
        ],
        'access' => [
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access',
            'showitem' => '
                starttime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel,
                endtime;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel,
                --linebreak--,
                fe_group;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:fe_group_formlabel,
                --linebreak--,editlock
            ',
        ],
            /*
        'crdate' => [
            'exclude' => true,
            'config' => [
                'type' => 'select',
                'renderType' => 'inputDateTime',
                'eval' => 'datetime,int',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038),
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true,
                ],
            ],
        ],
        */
    ],
    'types' => [
        '1' => [
            'showitem' => '
                type,
                notifiable_id,
                notifiable_type,
                level,
                data,
                read_at,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                    --palette--;;hidden,
                    --palette--;;access,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                    --palette--;;language,
                --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
            '
        ]
    ]
];

return $tx_lexnotifications_domain_model_notification;