<?php declare(strict_types=1);

// Documentation : https://docs.typo3.org/m/typo3/reference-coreapi/11.5/en-us/ApiOverview/Icon/Index.html

use Lex\Notifications\Extension;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

$extensionKey = Extension::KEY;

$iconConfiguration = [];

$imgIcons = [
    // Ex: 'plugin-notifications' => 'notifications.svg',
    'lex-notifications-notification' => 'Models/tx_lexnotifications_domain_model_notification.svg',
    'lex-notifications-mod-notification' => 'Modules/mod-notification.svg',
];

foreach ($imgIcons as $name => $file)
{
    $iconConfiguration[$name] = [
        'provider' => str_ends_with($file, '.svg') ? SvgIconProvider::class : BitmapIconProvider::class,
        'source' => "EXT:$extensionKey/Resources/Public/Icons/$file"
    ];
}

// Add FontAwesome icons here if needed

return $iconConfiguration;