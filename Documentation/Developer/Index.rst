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
calls to resolve the recipient's email address. It expects an ``$email``
property on the class:

.. code-block:: php

   use Lex\Notifications\Domain\Model\Ability\HasRouteNotificationForMail;
   use Lex\Notifications\Domain\Model\Ability\Notifiable;
   use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

   class FrontendUser extends AbstractEntity
   {
       use Notifiable;
       use HasRouteNotificationForMail;

       protected string $email = '';
   }

The ``Notifiable`` trait exposes two methods:

.. code-block:: php

   // Dispatches via Symfony Messenger if the notification implements ShouldQueue
   $user->notify(new OrderConfirmed($order));

   // Always sends immediately, ignoring ShouldQueue
   $user->notifyNow(new OrderConfirmed($order), ['mail', 'database']);

.. _recipient-examples:

Who Can Be a Recipient?
=======================

Because the only requirement is the ``Notifiable`` trait, the recipient can
be anything. Here are common patterns.

**Frontend user notifies another frontend user**

A collaboration feature where one user shares content with another:

.. code-block:: php

   // In a frontend plugin action
   $recipient = $this->frontendUserRepository->findByUid($targetUid);
   $recipient->notify(new ContentSharedWithYou($page, $sender));

**Extension notifies a backend user**

A Scheduler task or service that alerts an admin when a background job fails:

.. code-block:: php

   // BackendNotifiableUser wraps a TYPO3 backend user record
   class BackendNotifiableUser
   {
       use Notifiable;
       use HasRouteNotificationForMail;

       public function __construct(
           public readonly string $email,
           public readonly string $username,
       ) {}
   }

   $admin = new BackendNotifiableUser(
       email: $backendUserRecord['email'],
       username: $backendUserRecord['username'],
   );
   $admin->notifyNow(new SchedulerJobFailed($taskName, $errorMessage));

**Inline / anonymous notifiable (no database record needed)**

Send a one-off notification to any email address without a domain model:

.. code-block:: php

   $recipient = new class('info@example.com') {
       use Notifiable;
       use HasRouteNotificationForMail;

       public function __construct(public readonly string $email) {}
   };

   $recipient->notifyNow(new ContactFormReceived($formData));

**Multiple recipients of different types in one call**

The dispatcher accepts an array of notifiables — they do not need to be the
same class:

.. code-block:: php

   $this->notificationDispatcher->send(
       [$frontendUser, $backendAdmin, $externalEmail],
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
        */
       public function getLevel(): int
       {
           return NotificationLevel::LEVEL_INFO;
       }

       /**
        * Which channels to use. Receives the notifiable so you can adapt
        * the channel list per recipient type.
        *
        * @return string[]
        */
       public function via(object $notifiable): array
       {
           return [
               NotificationChannel::CHANNEL_MAIL,
               NotificationChannel::CHANNEL_DATABASE,
           ];
       }

       /**
        * Payload for the email channel.
        */
       public function toMail(object $notifiable): MailMessage
       {
           return (new MailMessage())
               ->subject('Order #' . $this->order->getNumber() . ' confirmed')
               ->html('<p>Thank you! Your order is being processed.</p>')
               ->to($notifiable->getEmail());
       }

       /**
        * Payload for the database channel.
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
   }

Only implement the channel methods you actually use. If your notification only
sends email, there is no need for ``toDatabase()``.

.. _queuing-notifications:

Queuing Notifications
=====================

Implement the ``ShouldQueue`` marker interface to have your notification
dispatched asynchronously via Symfony Messenger:

.. code-block:: php

   use TYPO3\CMS\Core\Messaging\ShouldQueue;

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
recipients:

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

.. _practical-use-cases:

Practical Use Cases
===================

**Workflow approval alert to a backend user**

.. code-block:: php

   // In a DataHandler hook or custom service
   $responsible = new BackendNotifiableUser(email: 'editor@example.com');
   $this->notifications->sendNow(
       $responsible,
       new ContentPendingReview($pageUid, $submitter),
   );

**Frontend user triggers a notification to another frontend user**

.. code-block:: php

   // In a frontend plugin action (e.g. a messaging feature)
   $sender   = $this->frontendUserRepository->findByUid($senderUid);
   $receiver = $this->frontendUserRepository->findByUid($receiverUid);

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

   $contact = new class('customer@example.com') {
       use \Lex\Notifications\Domain\Model\Ability\Notifiable;
       use \Lex\Notifications\Domain\Model\Ability\HasRouteNotificationForMail;
       public function __construct(public readonly string $email) {}
   };

   $contact->notifyNow(new OrderReceiptEmail($order));

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

Implement ``ChannelInterface`` to create any delivery channel you need:

.. code-block:: php

   namespace MyVendor\MyExtension\Notification\Channel;

   use Lex\Notifications\Channel\ChannelInterface;
   use Lex\Notifications\Notification;

   final class SlackChannel implements ChannelInterface
   {
       public function __construct(
           private readonly SlackClient $slack,
       ) {}

       public function send(object $notifiable, Notification $notification): void
       {
           $payload = $notification->toSlack($notifiable);
           $this->slack->post($notifiable->getSlackWebhookUrl(), $payload);
       }
   }

Register the channel as a service in ``Services.yaml`` and return its string
key (e.g. ``'slack'``) from your notification's ``via()`` method. The
``NotificationManager`` will resolve it from the container automatically.

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
