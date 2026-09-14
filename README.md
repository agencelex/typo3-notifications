# lex_notifications — TYPO3 Notification System

[![TYPO3 13.4](https://img.shields.io/badge/TYPO3-13.4-orange.svg)](https://typo3.org/)
[![TYPO3 14](https://img.shields.io/badge/TYPO3-14-orange.svg)](https://typo3.org/)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://www.php.net/)
[![License: GPL-2.0-or-later](https://img.shields.io/badge/License-GPL--2.0--or--later-green.svg)](https://opensource.org/licenses/GPL-2.0)
[![Version](https://img.shields.io/badge/version-2.0.0-brightgreen.svg)](https://extensions.typo3.org/extension/lex_notifications)

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
|---|---------|
| TYPO3 CMS | ^13.4 \|\| ^14 |
| PHP | ^8.2    |
| nesbot/carbon | ^3.2    |
| illuminate/collections | ^12.69  |

---

## Installation

```bash
composer require agencelex/notifications
vendor/bin/typo3 extension:setup
vendor/bin/typo3 upgrade:run
vendor/bin/typo3 cache:flush
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
    use HasRouteNotificationForMail; // Needed for email delivery, remove if not needed

    // When using HasRouteNotificationForMail
    public function getEmail(): string { return 'john.doe@example.com'; }
    public function getFirstName(): ?string { return null; }
    public function getLastName(): ?string { return null; }
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

    // Optional - If you want to specify a notification level different from INFO
    public function getLevel(): int
    {
        return NotificationLevel::LEVEL_INFO;
    }

    // Specify delivery channels
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
$this->notificationDispatcher->sendNow($user, new OrderConfirmed($order));

// Multiple recipients
$this->notificationDispatcher->send([$userA, $userB], new Announcement());

// To a specific channel
$this->notificationDispatcher->channel(NotificationChannel::CHANNEL_DATABASE)->send($user, new InvoicePaid($invoice));
```

---

## Who Can Be a Recipient?

Any object that uses the `Notifiable` trait — regardless of class hierarchy:

```php
// Frontend user → frontend user
$sender = $this->notifiableFrontendUserRepository->findByUid($senderUid);
$recipients = $this->notifiableFrontendUserRepository->findByUids($recipientUids);
$this->notificationDispatcher->send($recipients, new ContentSharedWithYou($page, $sender));

// Extension → backend user (plain class, no DB record needed)
$admin = new class($backendEmail, $backendRealName) {
    use Notifiable;
    use HasRouteNotificationForMail;

    protected string $firstName;
    protected string $lastName;

    public function __construct(protected readonly string $email, string $fullName) {
        $parts = explode(' ',trim($fullName), 2);
        $this->firstName = $parts[0] ?? '';
        $this->lastName = $parts[1] ?? '';
    }
    public function getEmail(): string { return $this->email; }
    public function getFirstName(): ?string { return $this->firstName ; }
    public function getLastName(): ?string { return $this->lastName; }
};
$admin->notifyNow(new SchedulerJobFailed($error));

// Any code → inline email recipient
$contact = new class($data) {
    use Notifiable;
    public function __construct(protected array $data) {}
};
$contact->notifyNow(new OrderReceiptEmail($order));
```

---

## Channels

Two built-in channels are included:

| Key | Class | Description |
|---|---|---|
| `mail` | `EmailChannel` | HTML/plain-text email via TYPO3 mail system |
| `database` | `DatabaseChannel` | Persisted in-app notifications via Extbase |

### Adding a Custom Channel

Implement `ChannelInterface` — the channel is **automatically registered** with no
additional configuration required:

```php
use Lex\Notifications\Channel\ChannelInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Lex\Notifications\Notification;

#[AutoconfigureTag('notifications.channel')]
final class SlackChannel implements ChannelInterface
{
    public function __construct(private readonly SlackClient $slack) {}

    // Optional: provide a short string key used in via().
    // If omitted, the fully qualified class name is used as the key.
    public function getName(): string
    {
        return 'slack';
    }

    public function send(object $notifiable, Notification $notification): void
    {
        $this->slack->post(
            $notifiable->routeNotificationForSlack(),
            $notification->toSlack($notifiable)
        );
    }
}
```

Then return the key from `via()`:

```php
public function via(object $notifiable): array
{
    return ['slack', NotificationChannel::CHANNEL_DATABASE];
}
```

The `NotificationManager` resolves the channel by its key at send time. To ensure a custom channel is properly detected and registered into the manager's iterator, you must assign the `notifications.channel` tag to your class.

You can achieve this in **one of three ways**:

#### 1. Declaration in `Services.yaml`
Add your concrete channel class manually with the required tag in your extension's `Configuration/Services.yaml`:

```yaml
services:
    Lex\Notifications\MicrosoftTeams\Channel\TeamsChannel:
        tags: ['notifications.channel']
```

#### 2. Using `#[AutoconfigureTag]` in the channel class (Recommended)
Keep your YAML file clean by adding the Symfony attribute directly above your class definition:

```php
use Lex\Notifications\Channel\ChannelInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('notifications.channel')]
final class TeamsChannel implements ChannelInterface
{
    // ... Your channel logic
}
```

#### 3. Using `#[Autoconfigure]` in the channel class
If you need to change other service options (like visibility) while registering the tag, you can pass the tags directly into the `#[Autoconfigure]` attribute:

```php
use Lex\Notifications\Channel\ChannelInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true, tags: ['notifications.channel'])]
final class TeamsChannel implements ChannelInterface
{
    // ... Your channel logic
}
```


---

## Queue Support

Implement the `ShouldQueue` marker interface to dispatch via Symfony Messenger:

```php
final class OrderConfirmed extends Notification implements ShouldQueue { }
```

Run the worker:

```bash
vendor/bin/typo3 messenger:consume
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

// Assign the notifications to the view
$this->view->assign('notifications', $notifications);

// Later, mark as read or remove all
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

Full documentation: https://docs.typo3.org/p/agencelex/notifications/1.3/en-us/

---

## License

GPL-2.0-or-later — see [LICENSE](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html).

## Author

[Agence Lex](https://www.agencelex.com/)
