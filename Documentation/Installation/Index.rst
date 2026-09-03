.. include:: /Includes.rst.txt

.. _installation:

============
Installation
============

.. _requirements:

Requirements
============

*  TYPO3 CMS **13.4** or higher
*  PHP **8.2** or higher
*  Composer-based TYPO3 installation (strongly recommended)

The extension requires two additional PHP packages that are pulled in
automatically via Composer:

*  ``nesbot/carbon`` ^3.2 — human-readable date/time differences
*  ``illuminate/collections`` ^12.69 — fluent collection helpers

.. _install-via-composer:

Installation via Composer (Recommended)
========================================

.. code-block:: bash

   composer require agencelex/notifications
   vendor/bin/typo3 extension:setup
   vendor/bin/typo3 upgrade:run
   vendor/bin/typo3 cache:flush

.. _install-via-ter:

Installation via TER (non-Composer)
=====================================

.. note::
   Non-Composer installations are supported but not recommended for new
   projects. Ensure the third-party packages listed above are available via
   your autoloader.

1. Download the extension from the `TYPO3 Extension Repository`_.
2. Upload and install it via **Admin Tools > Extensions**.
3. Run **Admin Tools > Maintenance > Analyze Database Structure** to
   create the required tables.

.. _TYPO3 Extension Repository: https://extensions.typo3.org/extension/lex_notifications

.. _post-install:

Post-Installation Steps
=======================

No configuration needed. Login in the backend, and verify that the module **Notification Messages** is present.

.. _database-tables:

Database Tables
===============

The extension creates two tables:

.. t3-field-list-table::
 :header-rows: 1

 - :Field: Table
   :Description: Purpose

 - :Field: ``tx_lexnotifications_domain_model_notification``
   :Description: Stores in-app (database-channel) notifications for frontend
                 users. Each row represents one unread or archived notification.

 - :Field: ``tx_lexnotifications_domain_model_message``
   :Description: Stores messages composed in the backend module, including
                 recipient lists, delivery channels and send status.
