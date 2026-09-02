<?php declare(strict_types=1);

use Lex\Notifications\Controller\Backend\NotificationController;
use Lex\Notifications\Extension;

$extensionKey = Extension::KEY;
$slugifiedExtensionKey = str_replace('_', '-', $extensionKey); //lex-notifications
$moduleName = 'notification';

// Documentation : https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ExtensionArchitecture/HowTo/BackendModule/ModuleConfiguration.html#backend-modules-configuration

return [
    'web_notifications' => [
        'parent' => 'web',
        'position' => ['after' => 'web_info'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/page/notification',
        'labels' => 'LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang_mod_' . NotificationController::getModuleName() .'.xlf',
        'extensionName' => Extension::extensionKeyCamelCase(),
        'iconIdentifier' => $slugifiedExtensionKey . '-mod-notification', // lex-notifications-mod-notification
        'controllerActions' => [
            NotificationController::class => [
                'list',
                'create',
                'store',
                'send',
                'resend'
            ]
        ]
    ],
];