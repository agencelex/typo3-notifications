.. include:: /Includes.rst.txt

.. _developer-api:

====================
Developer Reference
====================

.. contents::
   :depth: 2
   :local:

.. _notifiable-overview:

The Notifiable Trait
====================

The ``Notifiable`` trait is the only requirement for a class to *receive*
notifications. Add it to any PHP object — Extbase entity, plain PHP class,
anonymous class — and that object immediately becomes a valid notification
recipient.

.. code-block:: php

   use Lex\Notifications\Domain\Model\Ability\Notifiable;

   class MyModel
   {
       use Notifiable;
   }

   // That's it. Now you can do:
   $instance = new MyModel();
   $instance->notify(new SomeNotification());

There is no registry, no database table for recipients, no configuration.
Any object with the trait can be passed to the dispatcher.

.. _making-a-model-notifiable:

Making a Model Notifiable
=========================

For **email delivery**, also add ``HasRouteNotificationForMail``. This trait
provides the ``routeNotificationForMail()`` method, which the email channel
calls to resolve the recipient's email address. Only ``getEmail()`` is
required; ``getFirstName()`` and ``getLastName()`` are optional and, when
present, are used to build the recipient name (``"John Doe" <john.doe@example.com>``):

.. code-block:: php

   use Lex\Notifications\Domain\Model\Ability\HasRouteNotificationForMail;
   use Lex\Notifications\Domain\Model\Ability\Notifiable;
   use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

   class FrontendUser extends AbstractEntity
   {
       use Notifiable;
       use HasRouteNotificationForMail;

       // Required when using HasRouteNotificationForMail
       public function getEmail(): string { return $this->email; }

       // Optional. No need to define them
       public function getFirstName(): ?string { return $this->firstName; }
       public function getLastName(): ?string { return $this->lastName; }
   }

The ``Notifiable`` trait exposes three methods:

.. code-block:: php

   // Dispatches via Symfony Messenger if the notification implements ShouldQueue
   $user->notify(new OrderConfirmed($order));

   // Always sends immediately, ignoring ShouldQueue.
   // The optional second argument overrides the channels returned by via().
   $user->notifyNow(new OrderConfirmed($order));
   $user->notifyNow(new OrderConfirmed($order), [NotificationChannel::CHANNEL_DATABASE]);

   // Returns the route (address, webhook URL, …) of the notifiable for a channel.
   // Used by channels; see "Routing" below.
   $user->routeNotificationFor(NotificationChannel::CHANNEL_MAIL, $notification);

.. _routing:

Routing
-------

A channel asks the notifiable *where* to deliver by calling
``routeNotificationFor(string $channel, Notification $notification)``. The
``Notifiable`` trait resolves it by convention:

*  ``database`` → ``null`` (the database channel uses the notifiable's UID instead).
*  any other channel → calls ``routeNotificationFor<Channel>($notification)``
   if the method exists (e.g. ``routeNotificationForMail()``,
   ``routeNotificationForSlack()``), otherwise returns ``null``.

.. code-block:: php

   class FrontendUser extends AbstractEntity
   {
       use Notifiable;
       use HasRouteNotificationForMail;

       public function routeNotificationForSlack(?Notification $notification = null): string
       {
           return $this->slackWebhookUrl;
       }
   }

.. note::
   The convention relies on ``ucfirst()`` of the channel key, so it only works
   for channel keys that are valid PHP identifiers (``slack``, ``teams``…).
   A key such as ``my-channel`` cannot map to a method name.

.. _recipient-examples:

Who Can Be a Recipient?
=======================

Because the only requirement is the ``Notifiable`` trait, the recipient can
be anything. Here are common patterns.

**Frontend user notifies another frontend user**

A collaboration feature where one user shares content with another:

.. code-block:: php

   // In a frontend plugin action
   $sender = $this->notifiableFrontendUserRepository->findByUid($senderUid);
   $recipients = $this->notifiableFrontendUserRepository->findByUids($recipientUids);
   $this->notificationDispatcher->send($recipients, new ContentSharedWithYou($page, $sender));

**Extension notifies a backend user**

A Scheduler task or service that alerts an admin when a background job fails:

.. code-block:: php

   // An inline class wrapping a backend user record — no persistent domain model needed
   $admin = new class($backendUserRecord['email'], $backendUserRecord['realName']) {
       use Notifiable;
       use HasRouteNotificationForMail;

       protected string $firstName;
       protected string $lastName;

       public function __construct(protected readonly string $email, string $fullName) {
           $parts = explode(' ', trim($fullName), 2);
           $this->firstName = $parts[0] ?? '';
           $this->lastName  = $parts[1] ?? '';
       }
       public function getEmail(): string { return $this->email; }
       public function getFirstName(): ?string { return $this->firstName; }
       public function getLastName(): ?string { return $this->lastName; }
   };
   $admin->notifyNow(new SchedulerJobFailed($taskName, $errorMessage));

**Inline notifiable (no database record needed)**

Send a one-off notification to any email address without a domain model:

.. code-block:: php

   $recipient = new class('info@example.com') {
       use Notifiable;
       use HasRouteNotificationForMail;

       public function __construct(protected readonly string $email) {}
       public function getEmail(): string { return $this->email; }
   };

   $recipient->notifyNow(new ContactFormReceived($formData));

.. tip::
   For this kind of one-off recipient, an on-demand notification is even
   shorter — no class to write:

   .. code-block:: php

      $this->notificationDispatcher
          ->route(NotificationChannel::CHANNEL_MAIL, 'info@example.com')
          ->notifyNow(new ContactFormReceived($formData));

   See :ref:`on-demand-notifications`.

**Multiple recipients of different types in one call**

The dispatcher accepts an array of notifiables — they do not need to be the
same class:

.. code-block:: php

   $team = [$teamMemberA, $teamMemberB, ...];

   $this->notificationDispatcher->send(
       $team,
       new ImportantAnnouncement($text),
   );

Each recipient's ``via()`` result can differ, so the notification class can
adapt channels based on the notifiable type:

.. code-block:: php

   public function via(object $notifiable): array
   {
       // Only store in-DB for actual frontend user records
       if ($notifiable instanceof NotifiableFrontendUser) {
           return [NotificationChannel::CHANNEL_MAIL, NotificationChannel::CHANNEL_DATABASE];
       }

       return [NotificationChannel::CHANNEL_MAIL];
   }

.. _creating-a-notification:

Creating a Notification
=======================

Extend the abstract ``Notification`` class and implement the methods for each
channel your notification uses:

.. code-block:: php

   namespace MyVendor\MyExtension\Notification;

   use Lex\Notifications\Notification;
   use Lex\Notifications\NotificationChannel;
   use Lex\Notifications\NotificationLevel;
   use TYPO3\CMS\Core\Mail\MailMessage;

   final class OrderConfirmed extends Notification
   {
       public function __construct(
           private readonly Order $order,
       ) {}

       /**
        * RFC 5424 severity level for this notification.
        * Optional - Only if you want to specify a notification level different from INFO
        */
       public function getLevel(): int
       {
           return NotificationLevel::LEVEL_INFO;
       }

       /**
        * Which channels to use. Receives the notifiable so you can adapt
        * the channel list per recipient type/preference.
        *
        * @return string[]
        */
       public function via(object $notifiable): array
       {
           return $notifiable->prefers_sms ? ['vonage'] : [NotificationChannel::CHANNEL_MAIL, NotificationChannel::CHANNEL_DATABASE];
       }

       /**
        * Optional - Payload for the email channel.
        * Build your own MailMessage if you want.
        */
       public function toMail(object $notifiable): MailMessage
       {
           return (new MailMessage())
               ->subject('Order #' . $this->order->getNumber() . ' confirmed')
               ->html('<p>Thank you! Your order is being processed.</p>')
               // Optional: when no recipient is set, the email channel uses
               // $notifiable->routeNotificationFor('mail', $this)
               ->to($notifiable->routeNotificationForMail($this));
       }

       /**
        * Optional - Payload for the database channel.
        * Returned array is JSON-encoded and stored as-is.
        *
        * @return array<string, mixed>
        */
       public function toDatabase(object $notifiable): array
       {
           return [
               'level'    => $this->getLevel(),
               'subject'  => 'Order #' . $this->order->getNumber() . ' confirmed',
               'message'  => 'Your order has been received and is being processed.',
               'order_id' => $this->order->getUid(),
           ];
       }

       /**
        * Custom channel - Payload for the Slack channel.
        */
       public function toSlack(object $notifiable): SlackMessage
       {
           return (new SlackMessage)
               ->...
               ->...
               ->...;
       }
   }

Only implement the channel methods you actually use. If your notification only
sends email, there is no need for ``toDatabase()``. Calling a channel whose
``to…()`` method is missing throws a
``TYPO3\CMS\Core\Utility\Exception\NotImplementedMethodException``.

By default (when ``via()`` is not overridden), a notification is sent through
the ``database`` and ``mail`` channels.

.. _queuing-notifications:

Queuing Notifications
=====================

Implement the ``ShouldQueue`` marker interface to have your notification
dispatched asynchronously via Symfony Messenger:

.. code-block:: php

   use Illuminate\Contracts\Queue\ShouldQueue;

   final class OrderConfirmed extends Notification implements ShouldQueue
   {
       // No extra methods needed — the interface is a marker only.
   }

When ``ShouldQueue`` is implemented, calling ``notify()`` wraps the
notification in a ``NotificationQueued`` Messenger message. A CLI worker must
be running to process the queue:

.. code-block:: bash

   vendor/bin/typo3 messenger:consume async --time-limit=3600

Call ``notifyNow()`` or ``sendNow()`` to bypass the queue and deliver
immediately regardless of ``ShouldQueue``.

.. _using-the-dispatcher-directly:

Using the Dispatcher Directly
==============================

Inject ``NotificationDispatcherInterface`` into any service, controller, or
plugin. This is the recommended approach when you do not have a direct
reference to a notifiable object, or when you need to send to multiple
recipients. The interface is implemented by ``NotificationManager`` and exposes:

.. t3-field-list-table::
 :header-rows: 1

 - :Method: Method
   :Description: Description

 - :Method: ``send($notifiables, $notification)``
   :Description: Sends to one notifiable or an array of notifiables. Queued
                 through Symfony Messenger if the notification implements
                 ``ShouldQueue``.

 - :Method: ``sendNow($notifiables, $notification, ?array $channels = null)``
   :Description: Sends immediately, ignoring ``ShouldQueue``. ``$channels``
                 overrides ``via()`` for every notifiable.

 - :Method: ``channel(?string $name = null)``
   :Description: Returns a channel instance (the ``mail`` channel when no
                 name is given). Throws ``InvalidArgumentException`` for an
                 unknown channel.

 - :Method: ``route(string $channel, mixed $route)``
   :Description: Starts an on-demand notification. See
                 :ref:`on-demand-notifications`.

 - :Method: ``routes(array $routes)``
   :Description: Starts an on-demand notification on several channels at once.

.. code-block:: php

   use Lex\Notifications\NotificationDispatcherInterface;

   final class OrderService
   {
       public function __construct(
           private readonly NotificationDispatcherInterface $notifications,
           private readonly FrontendUserRepository $userRepository,
       ) {}

       public function completeOrder(Order $order): void
       {
           $buyer = $this->userRepository->findByUid($order->getBuyerUid());

           // Dispatches via Messenger queue if ShouldQueue is implemented
           $this->notifications->send($buyer, new OrderConfirmed($order));

           // Forces immediate delivery
           $this->notifications->sendNow($buyer, new OrderConfirmed($order));
       }
   }

Send to a batch of recipients in one call:

.. code-block:: php

   $subscribers = $this->frontendUserRepository->findByNewsletterGroup($groupId);

   $this->notifications->send(
       $subscribers->toArray(),
       new MonthlyNewsletter($content),
   );

The dispatcher iterates each notifiable independently, so a failed delivery
for one recipient does not block the others.

You can also target a single specific channel for one call using ``channel()``:

.. code-block:: php

   // Deliver only via the database channel, regardless of what via() returns
   $this->notificationDispatcher->channel(NotificationChannel::CHANNEL_DATABASE)
       ->send($user, new InvoicePaid($invoice));

.. note::
   ``channel(...)->send()`` talks to the channel directly: ``via()`` is not
   called and ``ShouldQueue`` is ignored, so delivery is always immediate.

.. _on-demand-notifications:

On-Demand Notifications
=======================

Sometimes the recipient is not a model at all: a guest who left an email
address, a support mailbox, a Teams room. Instead of writing a throwaway
class, ask the dispatcher for an **anonymous notifiable** and give it a route
for each channel:

.. code-block:: php

   use Lex\Notifications\NotificationChannel;
   use Lex\Notifications\NotificationDispatcherInterface;
   use Symfony\Component\Mime\Address;

   public function __construct(
       private readonly NotificationDispatcherInterface $notificationDispatcher,
   ) {}

   // One channel
   $this->notificationDispatcher
       ->route(NotificationChannel::CHANNEL_MAIL, 'guest@example.com')
       ->notify(new OrderReceiptEmail($order));

   // Several channels: chain route() calls
   $this->notificationDispatcher
       ->route(NotificationChannel::CHANNEL_MAIL, new Address('support@example.com', 'Support'))
       ->route('slack', '#orders')
       ->notify(new OrderReceived($order));

   // Several channels at once: routes()
   $this->notificationDispatcher
       ->routes([
           NotificationChannel::CHANNEL_MAIL => 'support@example.com',
           'slack' => '#orders',
       ])
       ->notifyNow(new OrderReceived($order));

``route()`` and ``routes()`` return a ``Lex\Notifications\AnonymousNotifiable``.
It uses the ``Notifiable`` trait, so ``notify()`` (queued if ``ShouldQueue``)
and ``notifyNow()`` (immediate) work as usual, and so does
``$this->notificationDispatcher->send($anonymous, ...)``.

Things to know:

*  **The route is whatever the channel expects.** For ``mail``, it can be a
   string or a ``Symfony\Component\Mime\Address``. For a custom channel, it
   can be any value (webhook URL, room ID, phone number…).
*  **via() still decides.** Only the channels returned by the notification's
   ``via()`` are used. A routed channel that ``via()`` doesn't return is
   ignored.
*  **The database channel is not supported.** An anonymous recipient has no
   UID to store, so routing to ``database`` throws an
   ``InvalidArgumentException``.
*  **Validation:** ``route()`` on the dispatcher checks that the channel is
   registered and throws an ``InvalidArgumentException`` if it isn't.
   ``routes()`` and any chained ``->route()`` calls don't check; an unknown
   channel only fails when the notification is sent.
*  **Queued on-demand notifications** go through Symfony Messenger like any
   other. With an asynchronous transport, routes must be serializable
   (strings, ``Address`` objects…).

Custom channels should read the route with
``$notifiable->routeNotificationFor('<channel>', $notification)``. That works
for both anonymous notifiables and models: on a model, the ``Notifiable``
trait forwards the call to ``routeNotificationFor<Channel>()`` (see
:ref:`routing`).

.. _practical-use-cases:

Practical Use Cases
===================

**Workflow approval alert to a backend user**

.. code-block:: php

   // In a DataHandler hook or custom service
   $this->notifications
       ->route(NotificationChannel::CHANNEL_MAIL, 'editor@example.com')
       ->notifyNow(new ContentPendingReview($pageUid, $submitter));

**Frontend user triggers a notification to another frontend user**

.. code-block:: php

   // In a frontend plugin action (e.g. a messaging feature)
   $sender   = $this->notifiableFrontendUserRepository->findByUid($senderUid);
   $receiver = $this->notifiableFrontendUserRepository->findByUid($receiverUid);

   $receiver->notify(new NewMessageReceived($sender, $messageText));

**Extension notifies multiple channels for different severity levels**

.. code-block:: php

   final class PaymentFailed extends Notification
   {
       public function via(object $notifiable): array
       {
           // Critical failures go to mail + database + Slack
           return [
               NotificationChannel::CHANNEL_MAIL,
               NotificationChannel::CHANNEL_DATABASE,
               'slack',
           ];
       }
   }

**Sending to a plain email without any domain model**

.. code-block:: php

   // On-demand: no class needed
   $this->notifications
       ->route(NotificationChannel::CHANNEL_MAIL, 'customer@example.com')
       ->notifyNow(new OrderReceiptEmail($order));

   // Or with an inline class, if you need more than an address
   $contact = new class('customer@example.com') {
       use \Lex\Notifications\Domain\Model\Ability\Notifiable;
       use \Lex\Notifications\Domain\Model\Ability\HasRouteNotificationForMail;
       public function __construct(protected readonly string $email) {}
       public function getEmail(): string { return $this->email; }
   };

   $contact->notifyNow(new OrderReceiptEmail($order));

**Alerting a support mailbox and a Teams room at once**

.. code-block:: php

   $this->notifications
       ->routes([
           NotificationChannel::CHANNEL_MAIL => new Address('support@example.com', 'Support'),
           'teams' => 'https://example.webhook.office.com/…',
       ])
       ->notify(new PaymentFailed($order));

.. _reading-database-notifications:

Reading Database Notifications
===============================

Inject ``DatabaseNotificationRepository`` to query stored notifications
for the currently logged-in frontend user:

.. code-block:: php

   use Lex\Notifications\Domain\Repository\DatabaseNotificationRepository;

   class NotificationController extends ActionController
   {
       public function __construct(
           private readonly DatabaseNotificationRepository $notificationRepository,
       ) {}

       public function indexAction(): ResponseInterface
       {
           $uid = $this->getContext()->getAspect('frontend.user')->get('id');

           $this->view->assign(
               'notifications',
               $this->notificationRepository->findByNotifiable($uid),
           );
           return $this->htmlResponse();
       }

       public function markAllReadAction(): ResponseInterface
       {
           $uid = $this->getContext()->getAspect('frontend.user')->get('id');
           $this->notificationRepository->markAllAsReadForNotifiable($uid);
           return $this->redirect('index');
       }
   }

Available repository methods:

.. t3-field-list-table::
 :header-rows: 1

 - :Method: Method
   :Description: Description

 - :Method: ``findByNotifiable(int $uid)``
   :Description: Returns all notifications for a notifiable UID, ordered
                 by creation date descending.

 - :Method: ``markAllAsReadForNotifiable(int $uid)``
   :Description: Sets ``read_at`` to the current timestamp for every unread
                 notification belonging to that UID.

 - :Method: ``removeAllForNotifiable(int $uid)``
   :Description: Permanently deletes all notifications for that UID.

.. _database-notification-model:

DatabaseNotification Model
==========================

Each stored notification exposes:

.. t3-field-list-table::
 :header-rows: 1

 - :Getter: Getter
   :Type: Type
   :Description: Description

 - :Getter: ``getType()``
   :Type: string
   :Description: Fully-qualified notification class name.

 - :Getter: ``getLevel()``
   :Type: int
   :Description: RFC 5424 severity level.

 - :Getter: ``getData()``
   :Type: string
   :Description: Raw JSON payload as stored by ``toDatabase()``.

 - :Getter: ``getDataAsArray()``
   :Type: array
   :Description: Decoded payload as a PHP array.

 - :Getter: ``getReadAt()``
   :Type: \\DateTime\|null
   :Description: Read timestamp, or ``null`` if unread.

 - :Getter: ``getCreatedAt()``
   :Type: \\DateTime
   :Description: Creation timestamp.

 - :Getter: ``getDiffCreatedAtForHumans()``
   :Type: string
   :Description: Human-readable relative time (e.g. "3 minutes ago").

 - :Getter: ``markAsRead()``
   :Type: void
   :Description: Sets ``readAt`` to the current date/time.

.. _custom-channels:

Adding Custom Channels
======================

**Step 1 — implement the interface**

.. code-block:: php

   namespace MyVendor\MyExtension\Notification\Channel;

   use Lex\Notifications\Channel\ChannelInterface;
   use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
   use Lex\Notifications\Notification;

   #[AutoconfigureTag('notifications.channel')]
   final class SlackChannel implements ChannelInterface
   {
       public function __construct(
           private readonly SlackClient $slack,
       ) {}

       /**
        * Optional. Provides a short string key used in via().
        * When omitted, the fully-qualified class name is used as the key.
        */
       public function getName(): string
       {
           return 'slack';
       }

       public function send(object $notifiable, Notification $notification): void
       {
           $payload = $notification->toSlack($notifiable);

           // Works for models (routeNotificationForSlack()) and on-demand recipients
           $webhookUrl = $notifiable->routeNotificationFor('slack', $notification);

           $this->slack->post($webhookUrl, $payload);
       }
   }

**Step 2 — return the key from** ``via()``

.. code-block:: php

   public function via(object $notifiable): array
   {
       return ['slack', NotificationChannel::CHANNEL_MAIL];
   }

The ``NotificationManager`` resolves the channel by its key at send time.
If ``getName()`` is not defined, use the fully-qualified class name as the
key in ``via()``:

.. code-block:: php

   public function via(object $notifiable): array
   {
       return [MyVendor\MyExtension\Notification\Channel\SlackChannel::class];
   }

.. note::
   Dependency injection works normally. Declare constructor arguments as
   usual and they will be autowired by the Symfony DI container.

Channel key lookup order:

1. ``getName()`` — if the method exists on the channel class.
2. Fully-qualified class name — as a fallback.

To ensure a custom channel is properly detected and registered into the manager's iterator, you must assign the ``notifications.channel`` tag to your class.
Implementing ``ChannelInterface`` alone is not enough in a third-party
extension: Symfony's ``_instanceof`` rules only apply to the services of the
file that declares them.

You can achieve this in **one of three ways**:

**Option A: Declaration in** ``Services.yaml``

Add your concrete channel class manually with the required tag in your extension's :file:`Configuration/Services.yaml`:

.. code-block:: yaml

   services:
       Lex\Notifications\MicrosoftTeams\Channel\TeamsChannel:
           tags: ['notifications.channel']

**Option B: Using** ``#[AutoconfigureTag]`` **in the channel class (Recommended)**

Keep your YAML file clean by adding the Symfony attribute directly above your class definition:

.. code-block:: php

   use Lex\Notifications\Channel\ChannelInterface;
   use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

   #[AutoconfigureTag('notifications.channel')]
   final class TeamsChannel implements ChannelInterface
   {
       // ... Your channel logic
   }

**Option C: Using** ``#[Autoconfigure]`` **in the channel class**

If you need to change other service options (like visibility) while registering the tag, you can pass the tags directly into the ``#[Autoconfigure]`` attribute:

.. code-block:: php

   use Lex\Notifications\Channel\ChannelInterface;
   use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

   #[Autoconfigure(public: true, tags: ['notifications.channel'])]
   final class TeamsChannel implements ChannelInterface
   {
       // ... Your channel logic
   }

.. _email-templates:

Customising Email Templates
============================

Email templates live in:

.. code-block:: none

   Resources/Private/
   ├── Layouts/Email/
   │   ├── NotificationLayout.html   # HTML wrapper
   │   └── NotificationLayout.txt    # Plain-text wrapper
   └── Templates/Email/
       ├── BackendUserSentMessageToFrontendUser.html
       └── BackendUserSentMessageToFrontendUser.txt

Override them in your site package by adjusting the Fluid template paths.
The built-in templates receive these variables:

.. t3-field-list-table::
 :header-rows: 1

 - :Variable: Variable
   :Type: Type
   :Description: Description

 - :Variable: ``{level}``
   :Type: int
   :Description: Notification severity level.

 - :Variable: ``{subject}``
   :Type: string
   :Description: Message subject/title.

 - :Variable: ``{message}``
   :Type: string
   :Description: Message body (may contain HTML).

 - :Variable: ``{link}``
   :Type: string\|null
   :Description: Optional call-to-action URL or typolink string.

For your own ``Notification`` subclasses you can use a completely different
template — simply return the rendered HTML from your ``toMail()`` method using
whatever rendering approach fits your extension.
