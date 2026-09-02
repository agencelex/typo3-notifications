.. include:: /Includes.rst.txt

.. _configuration:

=============
Configuration
=============

.. _extension-configuration:

Extension Configuration
=======================

The extension does not currently expose options in the TYPO3 Extension
Configuration panel (**Admin Tools > Settings > Extension Configuration**).
All behaviour is controlled through TypoScript or PHP configuration.

.. _typoscript:

TypoScript
==========

The extension ships with empty ``constants.typoscript`` and
``setup.typoscript`` templates. These are reserved for future frontend plugin
configuration. No TypoScript is required for the backend module or the
developer API.

.. _symfony-messenger:

Symfony Messenger (Queue Configuration)
========================================

The ``BackendUserSentMessageToFrontendUser`` notification implements the
``ShouldQueue`` interface, which means it is dispatched via the Symfony
Messenger component by default.

To process the queue, configure a transport and run the messenger worker:

.. code-block:: bash

   vendor/bin/typo3 messenger:consume async --time-limit=3600

Refer to the TYPO3 Core documentation on `Symfony Messenger`_ for full
transport configuration options (Doctrine, Redis, AMQP, etc.).

.. _Symfony Messenger: https://docs.typo3.org/m/typo3/reference-coreapi/13.4/en-us/ApiOverview/MessageBus/Index.html

If you want to send notifications **synchronously** (without a queue worker),
call ``notifyNow()`` instead of ``notify()`` — see :ref:`developer-api`.

.. _backend-module-access:

Backend Module Access
=====================

The **Web > Notifications** module is registered under the ``web`` module
group. Access is controlled via TYPO3's standard backend user/group
permissions.

To grant a backend user group access:

1. Go to **System > Backend Users** and edit the relevant user group.
2. Under **Access Lists > Modules**, tick **Web > Notifications**.
3. Save and clear caches.

.. _notification-levels:

Notification Levels
===================

Levels follow `RFC 5424`_ severity constants:

.. t3-field-list-table::
 :header-rows: 1

 - :Constant: Constant
   :Value: Value
   :Meaning: Meaning

 - :Constant: ``NotificationLevel::LEVEL_INFO``
   :Value: 0
   :Meaning: Informational message

 - :Constant: ``NotificationLevel::LEVEL_NOTICE``
   :Value: 1
   :Meaning: Normal but significant event

 - :Constant: ``NotificationLevel::LEVEL_WARNING``
   :Value: 2
   :Meaning: Warning condition

 - :Constant: ``NotificationLevel::LEVEL_ERROR``
   :Value: 3
   :Meaning: Error condition

 - :Constant: ``NotificationLevel::LEVEL_CRITICAL``
   :Value: 4
   :Meaning: Critical condition

 - :Constant: ``NotificationLevel::LEVEL_ALERT``
   :Value: 5
   :Meaning: Action must be taken immediately

 - :Constant: ``NotificationLevel::LEVEL_EMERGENCY``
   :Value: 6
   :Meaning: System is unusable

.. _RFC 5424: https://datatracker.ietf.org/doc/html/rfc5424

.. _notification-channels:

Notification Channels
=====================

Two built-in channels are available:

.. t3-field-list-table::
 :header-rows: 1

 - :Constant: Constant
   :Value: String key
   :Class: Implementation class

 - :Constant: ``NotificationChannel::CHANNEL_MAIL``
   :Value: ``mail``
   :Class: ``Lex\Notifications\Channel\EmailChannel``

 - :Constant: ``NotificationChannel::CHANNEL_DATABASE``
   :Value: ``database``
   :Class: ``Lex\Notifications\Channel\DatabaseChannel``

Custom channels can be registered by implementing
``Lex\Notifications\Channel\ChannelInterface`` — see :ref:`custom-channels`.
