<?php declare(strict_types=1);

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Type\Map;

if (!class_exists(Map::class)) {
    return [];
}

if (!class_exists(Directive::class)) {
    return [];
}

/*
// Documentation : https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ApiOverview/ContentSecurityPolicy/Index.html#content-security-policy-extension
// Documentation : https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ApiOverview/ContentSecurityPolicy/Index.html#content-security-policy
return Map::fromEntries([

]);
*/

return [];
