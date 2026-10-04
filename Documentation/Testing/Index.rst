.. include:: /Includes.rst.txt

.. _testing:

=================
Running the Tests
=================

The extension ships two test suites, compatible with TYPO3 **13.4** and
**14**:

.. t3-field-list-table::
 :header-rows: 1

 - :Suite: Suite
   :Location: Location
   :Covers: What it covers

 - :Suite: Unit
   :Location: ``Tests/Unit/``
   :Covers: Manager, channels, traits, models, queue handler. No TYPO3
            instance.

 - :Suite: Functional
   :Location: ``Tests/Functional/``
   :Covers: A real TYPO3 instance: DI wiring, email delivery, database
            storage, queue handler, repositories, custom channels registered
            by another extension, and every public way of sending a
            notification.

The functional tests load a fixture extension
(``Tests/Functional/Fixtures/Extensions/notifications_test``). It plays the
role of a third-party integrator: it registers custom channels and captures
sent emails in memory, so no mail is ever sent. By default they use
**SQLite**, so no database server is needed.

.. _testing-without-ddev:

Without DDEV
============

Requires PHP 8.2+ (with ``pdo_sqlite``) and Composer, run from the extension
directory:

.. code-block:: bash

   composer install

   # Unit tests
   .Build/bin/phpunit -c Build/phpunit-unit.xml
   # or: composer test:php:unit

   # Functional tests
   .Build/bin/phpunit -c Build/phpunit-functional.xml
   # or: composer test:php:functional

.. warning::
   Composer cannot install the dependencies on an **exFAT** volume (plugin
   installation fails). Use an APFS/ext4 disk, or DDEV.

.. _testing-with-ddev:

With DDEV
=========

The extension directory contains a DDEV configuration:

.. code-block:: bash

   ddev start
   ddev composer install

   # Unit tests
   ddev exec .Build/bin/phpunit -c Build/phpunit-unit.xml

   # Functional tests on SQLite
   ddev exec .Build/bin/phpunit -c Build/phpunit-functional.xml

   # Functional tests on the DDEV MySQL server
   ddev exec typo3DatabaseDriver=mysqli typo3DatabaseHost=db \
       typo3DatabaseUsername=root typo3DatabasePassword=root typo3DatabaseName=func_test \
       .Build/bin/phpunit -c Build/phpunit-functional.xml

The functional tests create one database per test class (prefixed with
``typo3DatabaseName``), so the database user needs ``CREATE DATABASE``
privileges. That's why the example uses ``root``.

.. _testing-options:

Useful Options
==============

.. code-block:: bash

   # Run a single test class or method
   .Build/bin/phpunit -c Build/phpunit-functional.xml --filter PublicApiTest
   .Build/bin/phpunit -c Build/phpunit-unit.xml --filter sendNowBypassesTheMessageBus

   # Show deprecations (useful when preparing the next TYPO3 major)
   .Build/bin/phpunit -c Build/phpunit-functional.xml --display-deprecations

   # Test against a specific TYPO3 version
   composer update -W --with "typo3/cms-core:^13.4"
   composer update -W --with "typo3/cms-core:^14.3"

Any other database can be used through the ``typo3DatabaseDriver``,
``typo3DatabaseHost``, ``typo3DatabasePort``, ``typo3DatabaseUsername``,
``typo3DatabasePassword`` and ``typo3DatabaseName`` environment variables.

.. _testing-own-code:

Testing Your Own Notifications
==============================

The fixture extension is also a working example of how to test code that
sends notifications:

*  ``Classes/Mail/CapturingTransport.php`` — a mail transport that keeps sent
   emails in memory. Enable it in a functional test with
   ``$GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = CapturingTransport::class``
   (or ``$configurationToUseInTestInstance``), then assert on
   ``CapturingTransport::$messages``.
*  ``Classes/Channel/AttributeTaggedChannel.php`` — a custom channel that
   records what it receives, registered with ``#[AutoconfigureTag]``.
*  ``Configuration/Services.yaml`` — registers ``YamlTaggedChannel`` with an
   explicit ``notifications.channel`` tag.

In unit tests, mock ``NotificationDispatcherInterface`` and register it with
``GeneralUtility::addInstance()`` to assert that ``notify()`` /
``notifyNow()`` are called, without delivering anything:

.. code-block:: php

   $dispatcher = $this->createMock(NotificationDispatcherInterface::class);
   $dispatcher->expects(self::once())->method('send')->with($user, $notification);
   GeneralUtility::addInstance(NotificationDispatcherInterface::class, $dispatcher);

   $user->notify($notification);
