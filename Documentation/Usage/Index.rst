.. include:: /Includes.rst.txt

.. _usage:

===========================================
Backend Module: Web > Notification Messages
===========================================

.. note::
   The backend module is **one built-in application** of the notification
   system, not the primary feature. For sending notifications from PHP code,
   see :ref:`developer-api`. The module source code
   (``Classes/Controller/Backend/NotificationController.php``) is also a
   useful **reference implementation** of how to dispatch batch notifications
   to a resolved list of ``Notifiable`` recipients.

After installation, navigate to **Web > Notification Messages** in the TYPO3 backend.
The module lets editors compose messages and deliver them to frontend users
(individuals or groups) via email and/or database notifications.

.. _listing-messages:

Listing Messages
================

The module overview displays all composed messages in a paginated list
(50 per page). Each row shows:

*  **Level** — severity badge (Info, Notice, Warning, Error, …)
*  **Subject** — message title
*  **Created** — creation date and author
*  **Sent at** — timestamp of last send, or "Not sent" if pending
*  **Actions** — Send / Resend buttons

Use the filter bar at the top to narrow results by level.

.. _composing-a-message:

Composing a Message
===================

Click the **Create** button (pencil icon) in the module button bar.

.. t3-field-list-table::
 :header-rows: 1

 - :Field: Field
   :Required: Required
   :Description: Description

 - :Field: Level
   :Required: Yes
   :Description: Severity of the notification (Info, Notice, Warning, …).
                 Displayed as a badge in both the backend list and the
                 notification email.

 - :Field: Subject
   :Required: Yes
   :Description: Short title of the message (max 255 characters).

 - :Field: Message
   :Required: Yes
   :Description: Full message body. Supports basic HTML.

 - :Field: Link
   :Required: No
   :Description: Optional URL or internal TYPO3 page link displayed as a
                 call-to-action button in the notification.

 - :Field: Recipients
   :Required: Yes
   :Description: Select one or more **frontend users** or **frontend user
                 groups**. All members of selected groups will receive the
                 notification.

 - :Field: Excluded recipients
   :Required: No
   :Description: Frontend users to exclude from delivery (useful to target a
                 group but skip specific members).

 - :Field: Channels
   :Required: No
   :Description: Select one or more delivery channels: **Mail** and/or
                 **Database**. If left empty, both channels are used.

Click **Save** to store the message without sending it.

.. _sending-messages:

Sending Messages
================

After saving (or from the list view), click the **Send** button. The module
will:

1. Resolve all recipient UIDs from the selected users and groups, minus any
   excluded recipients.
2. Remove duplicates.
3. Load ``NotifiableFrontendUser`` objects for each resolved UID.
4. Instantiate a ``BackendUserSentMessageToFrontendUser`` notification.
5. Call ``$dispatcher->send($recipients, $notification)`` — the same call you
   would make from your own PHP code.
6. Record the ``sent_at`` timestamp on the message record.
7. Display a flash message confirming how many recipients were notified.

.. note::
   ``BackendUserSentMessageToFrontendUser`` implements ``ShouldQueue``, so
   actual delivery is handled by the Symfony Messenger worker. If no worker is
   running, messages sit in the queue until it is started. To bypass the queue,
   call ``sendNow()`` — see :ref:`developer-api`.

.. _resending-messages:

Resending Messages
==================

Click **Resend** to dispatch a message again to the same recipients and
channels. The ``sent_at`` timestamp is updated. Useful when delivery failed
or you want to send a reminder.

.. _module-as-code-sample:

Using the Module as a Code Sample
==================================

The backend module is deliberately simple so that its source code is easy
to read. If you need to send batch notifications from your own extension,
study ``NotificationController::sendAction()`` — it shows the full pattern:

.. code-block:: php

   // Simplified version of what the module does:

   $recipients = $this->resolveRecipients($message);   // returns Notifiable[]

   $notification = new BackendUserSentMessageToFrontendUser(
       subject:  $message->getSubject(),
       message:  $message->getMessage(),
       level:    $message->getLevel(),
       link:     $message->getLink(),
       channels: $this->parseChannels($message),
   );

   $this->notificationDispatcher->send($recipients, $notification);

Replace ``BackendUserSentMessageToFrontendUser`` with your own
``Notification`` subclass and ``$recipients`` with any collection of
``Notifiable`` objects to adapt this pattern to your use case.
