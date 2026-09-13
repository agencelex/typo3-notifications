<?php

/**
 * The ext_emconf.php is used in legacy installations not based on Composer to supply information about an extension in
 * the Admin Tools > Extensions module. In these installations the ordering of installed extensions and their dependencies
 * are loaded from this file as well.
 */

$EM_CONF[$_EXTKEY] = [
    'title'                 => 'Laravel-style Notification System for TYPO3',
    'description'           => 'Modern Notification System for TYPO3 (Laravel-inspired). Any PHP code — a controller, an Extbase plugin, a domain service, a Scheduler task, a middleware — can send a notification to any object that uses the Notifiable trait. Notifications can be sent via email, SMS, Slack, Telegram, or any other channel.',
    'category'              => 'misc',
    'version'               => '2.0.0',
    'state'                 => 'stable',
    'author'                => 'Agence Lex',
    'author_email'          => 'contact@agencelex.com',
    'author_company'        => 'Agence Lex',

    'autoload' => [
        'psr-4' => [
            'Lex\\Notifications\\' => 'Classes'
        ]
    ],

    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.3.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];