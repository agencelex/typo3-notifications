# lex_notifications — TYPO3 Notification System

[![TYPO3 13.4](https://img.shields.io/badge/TYPO3-13.4-orange.svg)](https://typo3.org/)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://www.php.net/)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0--or--later-green.svg)](https://opensource.org/licenses/GPL-2.0)
[![Version](https://img.shields.io/badge/version-1.1.0-brightgreen.svg)](https://extensions.typo3.org/extension/lex_notifications)

A Laravel-style notification system for TYPO3. Any PHP class can send a
notification to any object that uses the `Notifiable` trait — through any
combination of channels (email, database, Slack, …).

There is no constraint on the direction: a frontend user can notify another
frontend user, an extension can alert a backend user, a Scheduler task can
email an address with no domain model at all. If it uses `Notifiable`, it
can receive notifications.

A **backend module** (Web > Notifications) is included for editors who need
to compose and send messages to frontend users. It also serves as a reference
implementation for dispatching batch notifications from PHP code.

---

## Requirements

| Dependency | Version |
|---|---|
| TYPO3 CMS | ^13.4 |
| PHP | ^8.2 |
| nesbot/carbon | ^3.2 |
| illuminate/collections | ^10.48 |

---

## Installation

```bash
composer require lex/notifications
vendor/bin/typo3 extension:activate lex_notifications
vendor/bin/typo3 database:updateschema
```

---

## Quick Start

### 1. Make any class a notification recipient

```php
use Lex\Notifications\Domain\Model\Ability\Notifiable;
use Lex\Notifications\Domain\Model\Ability\HasRouteNotificationForMail;

class FrontendUser extends AbstractEntity
{
    use Notifiable;
    use HasRouteNotificationForMail; // needed for email delivery

    protected string $email = '';
}
```

### 2. Create a notification

```php
use Lex\Notifications\Notification;
use Lex\Notifications\NotificationChannel;
use Lex\Notifications\NotificationLevel;
use TYPO3\CMS\Core\Mail\MailMessage;

final class OrderConfirmed extends Notification
{
    public function __construct(private readonly Order $order) {}

    public function getLevel(): int
    {
        return NotificationLevel::LEVEL_INFO;
    }

    public function via(object $notifiable): array
    {
        return [NotificationChannel::CHANNEL_MAIL, NotificationChannel::CHANNEL_DATABASE];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Order #' . $this->order->getNumber() . ' confirmed')
            ->html('<p>Thank you! Your order is being processed.</p>')
            ->to($notifiable->getEmail());
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'level'   => $this->getLevel(),
            'subject' => 'Order #' . $this->order->getNumber() . ' confirmed',
            'message' => 'Your order has been received.',
        ];
    }
}
```

### 3. Send it

```php
// From the notifiable itself
$user->notify(new OrderConfirmed($order));         // queued (if ShouldQueue)
$user->notifyNow(new OrderConfirmed($order));      // immediate

// From any service via the dispatcher
$this->notificationDispatcher->send($user, new OrderConfirmed($order));
$this->notificationDispatcher->send([$userA, $userB], new Announcement());
```

---

## Who Can Be a Recipient?

Any object that uses the `Notifiable` trait — regardless of class hierarchy:

```php
// Frontend user → frontend user
$recipient->notify(new ContentSharedWithYou($page, $sender));

// Extension → backend user (plain class, no DB record needed)
$admin = new class('admin@example.com') {
    use Notifiable;
    use HasRouteNotificationForMail;
    public function __construct(public readonly string $email) {}
};
$admin->notifyNow(new SchedulerJobFailed($error));

// Any code → inline email recipient
$contact = new class('customer@example.com') {
    use Notifiable;
    use HasRouteNotificationForMail;
    public function __construct(public readonly string $email) {}
};
$contact->notifyNow(new OrderReceiptEmail($order));
```

---

## Channels

| Key | Class | Description |
|---|---|---|
| `mail` | `EmailChannel` | HTML/plain-text email via TYPO3 mail system |
| `database` | `DatabaseChannel` | Persisted in-app notifications via Extbase |

Add any channel by implementing `ChannelInterface`:

```php
final class SlackChannel implements ChannelInterface
{
    public function send(object $notifiable, Notification $notification): void
    {
        $this->slack->post($notifiable->getSlackWebhookUrl(), $notification->toSlack($notifiable));
    }
}
```

Return the channel's string key from `via()` and it will be resolved automatically.

---

## Queue Support

Implement the `ShouldQueue` marker interface to dispatch via Symfony Messenger:

```php
final class OrderConfirmed extends Notification implements ShouldQueue { }
```

Run the worker:

```bash
vendor/bin/typo3 messenger:consume async --time-limit=3600
```

Call `notifyNow()` / `sendNow()` to bypass the queue at any time.

---

## Notification Levels (RFC 5424)

| Constant | Value |
|---|---|
| `NotificationLevel::LEVEL_INFO` | 0 |
| `NotificationLevel::LEVEL_NOTICE` | 1 |
| `NotificationLevel::LEVEL_WARNING` | 2 |
| `NotificationLevel::LEVEL_ERROR` | 3 |
| `NotificationLevel::LEVEL_CRITICAL` | 4 |
| `NotificationLevel::LEVEL_ALERT` | 5 |
| `NotificationLevel::LEVEL_EMERGENCY` | 6 |

---

## Backend Module

**Web > Notifications** lets editors:

- Compose messages (subject, body, level, link)
- Target frontend users or groups, with optional exclusions
- Choose delivery channels (email and/or database)
- Send immediately or queue for later, and resend at any time

The module source (`Classes/Controller/Backend/NotificationController.php`)
is intentionally simple and can be used as a reference for dispatching
batch notifications from PHP code.

---

## Reading In-App Notifications

```php
// In a frontend plugin
$uid = $this->getContext()->getAspect('frontend.user')->get('id');

$notifications = $this->notificationRepository->findByNotifiable($uid);
$this->notificationRepository->markAllAsReadForNotifiable($uid);
$this->notificationRepository->removeAllForNotifiable($uid);
```

---

## Development

```bash
# Code style
composer run cgl

# Static analysis
composer run phpstan

# Tests
composer run test
```

---

## Documentation

Full documentation: https://docs.typo3.org/p/lex/notifications/1.1/en-us/

---

## License

GPL-2.0-or-later — see [LICENSE](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html).

## Author

[Agence Lex](https://www.agencelex.com/) — contact@agencelex.com
