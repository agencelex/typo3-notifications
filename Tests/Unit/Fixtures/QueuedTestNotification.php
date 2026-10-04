<?php declare(strict_types=1);

namespace Lex\Notifications\Tests\Unit\Fixtures;

use Illuminate\Contracts\Queue\ShouldQueue;

final class QueuedTestNotification extends TestNotification implements ShouldQueue {}
