<?php

/**
 * The ext_emconf.php is used in legacy installations not based on Composer to supply information about an extension in
 * the Admin Tools > Extensions module. In these installations the ordering of installed extensions and their dependencies
 * are loaded from this file as well.
 * Changed in version 11.4: In Composer-based installations, the ordering of installed extensions and their dependencies
 * is loaded from the composer.json file, instead of ext_emconf.php
 */

$EM_CONF[$_EXTKEY] = [
    'title'                 => 'Notifications',
    'description'           => 'The extension lets you send notifications to users',
    'category'              => 'plugin',
    'version'               => '1.1.0',
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
            'typo3' => '13.4.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];