.. include:: /Includes.rst.txt

.. _start:

=================
Lex Notifications
=================

:Extension key:
   lex_notifications

:Package name:
   lex/notifications

:Version:
   |release|

:Language:
   en

:Author:
   Agence Lex

:License:
   This document is published under the `Creative Commons BY 4.0`_ license.

:Rendered:
   |today|

.. _Creative Commons BY 4.0: https://creativecommons.org/licenses/by/4.0/

----

**lex_notifications** brings a Laravel-style notification system to TYPO3 13.4+.
Any PHP class — a controller, a plugin, a service, or a domain model — can send
a notification to any object that uses the ``Notifiable`` trait, through any
combination of channels (email, database, Slack, …). The recipient can be a
frontend user, a backend user, an email address, or even an anonymous inline
class: if it uses ``Notifiable``, it can receive notifications.

A ready-to-use backend module is included. It lets editors send messages to
frontend users and serves as a practical code sample for dispatching batch
notifications.

----

**Table of Contents**

.. toctree::
   :maxdepth: 2
   :titlesonly:

   Introduction/Index
   Installation/Index
   Configuration/Index
   Usage/Index
   Developer/Index
   Changelog/Index
