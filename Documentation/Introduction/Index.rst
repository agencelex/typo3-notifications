.. include:: /Includes.rst.txt

.. _introduction:

============
Introduction
============

.. _what-it-does:

What Does It Do?
================

If you are familiar with the Laravel framework, you already know how flexible,
robust, and modular its notification system is. With dozens of community and
official channels available (Slack, Teams, Push, SMS, VoIP, and more), Laravel
empowers applications to send notifications across a vast network.

**lex_notifications** brings this exact power, extensibility, and flexibility
directly into the TYPO3 ecosystem.

The core idea is simple: any PHP object that uses the ``Notifiable`` trait can
*receive* a notification. Any PHP code — a controller, an Extbase plugin, a
domain service, a Scheduler task, a middleware — can *send* one. The two sides
are decoupled through a ``Notification`` class that describes *what* to send
and *how* to format it for each delivery channel.

There is no constraint on the direction of communication:

*  A **frontend user** can receive a notification when they place an order.
*  A **backend user** can be alerted when a content workflow step requires
   their approval.
*  An **email address** (represented by a class or even a lightweight inline class) can receive
   a transactional email without any database record.
*  Any **custom domain model** becomes a notification recipient with a single
   ``use Notifiable;`` declaration.

Built-in delivery channels:

*  **Email channel** — sends styled HTML/plain-text emails using TYPO3's mail
   system and Fluid templates.
*  **Database channel** — persists notifications in a database table so they
   can be retrieved and displayed as an in-app notification centre.

Both channels are extensible. Implement ``ChannelInterface`` to add Slack,
push notifications, SMS, webhooks, or any other transport.

**The included backend module** (Web > Notifications) is a ready-to-use tool
for editors who need to compose and send messages to frontend users. It also
acts as a reference implementation showing how to dispatch batch notifications
to a resolved list of ``Notifiable`` recipients.

.. _when-to-use:

When Should You Use It?
=======================

Use **lex_notifications** whenever you need to decouple the act of *triggering*
a notification from the act of *delivering* it. Typical scenarios:

*  **E-commerce** — order confirmed, shipment dispatched, invoice available.
*  **Workflow / approval** — content submitted for review, approved, rejected.
*  **Account events** — registration, password reset, login from new device.
*  **System alerts** — an extension notifies a backend user when a background
   job fails.
*  **Cross-user messaging** — a frontend user triggers a notification to
   another frontend user (e.g. a collaboration platform).
*  **Editorial broadcasts** — backend editors send announcements to groups of
   frontend users via the included module.
*  **Any programmatic trigger** — if something happens in your TYPO3
   application and someone needs to know about it, this extension handles the
   delivery.

.. _core-concepts:

Core Concepts
=============

.. rst-class:: dl-parameters

Notifiable
   Any PHP object that uses the ``Notifiable`` trait. It gains ``notify()``
   and ``notifyNow()`` methods and can be passed as the recipient of any
   notification.

Notification
   A PHP class extending ``Lex\Notifications\Notification``. It declares
   which channels to use (``via()``) and how to format the payload for each
   channel (``toMail()``, ``toDatabase()``, ``toSlack()``, …).

Channel
   A delivery mechanism implementing ``ChannelInterface``. A channel receives
   a notifiable and a notification, extracts the relevant payload, and delivers
   it (sends an email, writes a DB row, calls a webhook, …).

NotificationDispatcherInterface
   The central dispatcher. Inject it anywhere in your TYPO3 code to send
   notifications without depending on concrete implementations.

ShouldQueue
   A marker interface. When a notification class implements it, dispatching
   goes through Symfony Messenger so delivery happens asynchronously in a
   CLI worker — keeping your HTTP responses fast.

.. _architecture-overview:

Architecture Overview
=====================

.. code-block:: none

   ┌──────────────────────────────────────────────────────────────┐
   │  Any PHP code: Controller · Plugin · Service · Scheduler     │
   │                                                              │
   │  $user->notify(new OrderConfirmed($order));                  │
   │  // or                                                       │
   │  $dispatcher->send([$userA, $userB], new Announcement());    │
   └─────────────────────────┬────────────────────────────────────┘
                             │
                             ▼
   ┌──────────────────────────────────────────────────────────────┐
   │              NotificationManager (Dispatcher)                │
   │                                                              │
   │  • Iterates notifiable(s)                                    │
   │  • Calls notification->via($notifiable) → channel list       │
   │  • If ShouldQueue → wraps in Messenger message               │
   │  • Otherwise → sends immediately via each channel            │
   └───────┬──────────────────┬────────────────────┬─────────────┘
           │                  │                    │
           ▼                  ▼                    ▼
   ┌──────────────┐  ┌────────────────┐  ┌─────────────────────┐
   │ EmailChannel │  │DatabaseChannel │  │  YourCustomChannel  │
   │ (TYPO3 Mail) │  │ (Extbase / DB) │  │  (Slack, SMS, …)    │
   └──────────────┘  └────────────────┘  └─────────────────────┘

           ▲                  ▲                    ▲
           │       Notifiable recipients            │
   ┌───────┴──────────────────┴────────────────────┴─────────────┐
   │  FrontendUser · BackendUser · InlineClass · Any domain model │
   └──────────────────────────────────────────────────────────────┘

Notifications flow from *any caller* through the ``NotificationManager``,
which delegates to the channels declared in the notification's ``via()``
method. Each channel knows how to format and deliver the payload for its
transport. The recipients can be any combination of objects using the
``Notifiable`` trait.

.. _screenshots:

Screenshots
===========

.. figure:: /Images/backend-list.png
   :alt: Backend module — message list
   :class: with-shadow

   The included backend module lists all composed messages with their send
   status, level indicator and action buttons.

.. figure:: /Images/backend-create.png
   :alt: Backend module — compose message
   :class: with-shadow

   The create form lets editors write a message, select severity level,
   target recipients (individual frontend users or groups) and pick delivery
   channels.
