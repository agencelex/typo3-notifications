.. include:: /Includes.rst.txt

.. _changelog:

=========
Changelog
=========

.. _changelog-1-3-0:

1.3.0
=====

*  **[FEATURE]** Custom channels implementing ``ChannelInterface`` can now
   **be registered** via a Symfony DI tag
   (``notifications.channel``).
*  **[FEATURE]** Optional ``getName(): string`` method on channel classes.
   When present, its return value is used as the channel key in ``via()``.
   When absent, the fully-qualified class name is used as the key.
*  **[FEATURE]** ``NotificationManager`` now collects channels through
   ``#[AutowireIterator('notifications.channel')]`` and stores live service
   instances — channels benefit from full DI, including scoped services.
*  **[BREAKING]** ``NotificationManager::channel()`` now resolves from the
   injected service map instead of calling ``GeneralUtility::makeInstance()``.
   Custom channels previously registered only as public services must add the
   ``notifications.channel`` tag (or implement ``ChannelInterface`` so the
   ``_instanceof`` rule picks them up automatically).
*  **[CHANGE]** Added TYPO3 **14** compatibility (``^13.4 || ^14``).
*  **[CHANGE]** ``AbstractModuleController`` converted to constructor
   injection. Removed deprecated setter-injection methods
   (``injectModuleTemplateFactory``, ``injectPageRenderer``,
   ``injectIconFactory``, ``injectBackendUriBuilder``).
*  **[FIX]** ``?array $channels = null`` parameter type corrected across
   ``NotificationDispatcherInterface``, ``NotificationManager``,
   ``NotificationSender``, and the ``Notifiable`` trait.
*  **[FIX]** TCA: removed deprecated ``interface`` key (dropped in TYPO3 13),
   ``cruser_id`` ctrl field (removed in TYPO3 13), and ``eval => 'trim'`` /
   ``eval => 'int'`` validators (removed in TYPO3 13).
*  **[FIX]** ``notifiable_id`` TCA field changed from ``type=input`` to
   ``type=number``.
*  **[FIX]** SQL schema: removed ``int(11)`` display width, added missing
   ``link`` column in ``tx_lexnotifications_domain_model_message``.

.. _changelog-1-1-0:

1.1.0 — 2024-01-01
===================

*  **[FEATURE]** Added Symfony Messenger queue integration via
   ``ShouldQueue`` marker interface.
*  **[FEATURE]** Added ``NotificationLevel`` constants following RFC 5424
   (Info, Notice, Warning, Error, Critical, Alert, Emergency).
*  **[FEATURE]** Added ``resend`` action to the backend module.
*  **[FEATURE]** Pagination in the backend message list (50 items per page).
*  **[FEATURE]** Added level filter to the backend message list.
*  **[FEATURE]** French (``fr``) localization for all language files.
*  **[FEATURE]** Added ``getDiffCreatedAtForHumans()`` on
   ``DatabaseNotification`` using Carbon.
*  **[CHANGE]** Bumped TYPO3 requirement to **13.4+**.
*  **[CHANGE]** Bumped PHP requirement to **8.2+**.

.. _changelog-1-0-0:

1.0.0 — Initial Release
========================

*  Backend module for composing and sending messages to frontend users.
*  Email channel using TYPO3's mail system and Fluid templates.
*  Database channel for persistent in-app notifications.
*  ``Notifiable`` trait for Extbase domain models.
*  ``HasRouteNotificationForMail`` trait for email routing.
*  ``DatabaseNotificationRepository`` with ``findByNotifiable``,
   ``markAllAsReadForNotifiable`` and ``removeAllForNotifiable`` methods.
*  Abstract ``Notification`` base class for custom notification types.
*  ``NotificationDispatcherInterface`` for dependency injection.
*  ``BackendUserSentMessageToFrontendUser`` built-in notification class.
*  English localization for all language files.
