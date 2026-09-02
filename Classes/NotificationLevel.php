<?php declare(strict_types=1);

namespace Lex\Notifications;

final class NotificationLevel
{
    // RFC 5424 specification : https://tools.ietf.org/html/rfc5424
    const LEVEL_INFO = 0;
    const LEVEL_NOTICE = 1;
    const LEVEL_WARNING = 2;
    const LEVEL_ERROR = 3;
    const LEVEL_CRITICAL = 4;
    const LEVEL_ALERT = 5;
    const LEVEL_EMERGENCY = 6;

    const SUPPORTED_LEVELS = [
        self::LEVEL_INFO => 'info',
        self::LEVEL_NOTICE => 'notice',
        self::LEVEL_WARNING => 'warning',
        self::LEVEL_ERROR => 'error',
        self::LEVEL_CRITICAL => 'critical',
        self::LEVEL_ALERT => 'alert',
        self::LEVEL_EMERGENCY => 'emergency',
    ];
}