.. include:: /Includes.rst.txt

.. _changelog:

=========
Changelog
=========

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
