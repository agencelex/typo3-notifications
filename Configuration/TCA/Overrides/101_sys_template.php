<?php declare(strict_types=1);

use Lex\Notifications\Extension;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

// Include the configuration of the website, so it can be displayed when creating a template record
ExtensionManagementUtility::addStaticFile(
    Extension::KEY,
    'Configuration/TypoScript',
    'Notifications'
);