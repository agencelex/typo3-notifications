# CLAUDE.md — lex_notifications

Laravel-style notification system for TYPO3 13.4 / 14.x (PHP ^8.2).
Composer `agencelex/notifications`, ext key `lex_notifications`, namespace `Lex\Notifications\`.
User-facing docs: `README.md` and `Documentation/` (RST). Keep both in sync when the public API changes.

## Architecture (read this instead of scanning Classes/)

- `NotificationDispatcherInterface` (public service, aliased to `NotificationManager`):
  `send()`, `sendNow(..., ?array $channels)`, `channel(?string)`, `route()`, `routes()`.
- `NotificationManager`: gets channels via `#[AutowireIterator('notifications.channel')]`, keyed by
  `getName()` if present, else the short class name. The default channel is `mail`. `route()` validates that the channel exists;
  `routes()` does not. Delegates to `NotificationSender`.
- `NotificationSender` (readonly): `send()` → if `ShouldQueue` (Illuminate contract), dispatches
  `Queue\Message\NotificationQueued` on the Symfony Messenger bus; else `sendNow()`. `sendNow()` iterates
  notifiables × channels (`$channels ?: via()`), giving each channel a *clone* of the notification;
  a channel is skipped when `$notifiable->routeNotificationFor($channel, $n)` is falsy.
- `Queue\Handler\SendQueuedNotificationNow`: messenger handler (tag in Services.yaml) → `sendNow()`.
  TYPO3's default messenger routing is sync, so queued notifications are delivered in-request unless an async transport is configured.
- `Notification` (abstract): `via()` defaults to `[database, mail]`; `toMail()`/`toDatabase()` throw
  `NotImplementedMethodException`; `getType()` = static::class; `$level` (NotificationLevel, RFC 5424 0–6).
- `Domain\Model\Ability\Notifiable` trait: `notify()`/`notifyNow()` resolve the dispatcher via
  `GeneralUtility::makeInstance(NotificationDispatcherInterface::class)`; `routeNotificationFor($channel, $n)`:
  `database` → true, otherwise calls `routeNotificationFor<UpperCamelCase channel>($n)` if it exists
  (`-`/`_`/space are separators: `my-channel` → `routeNotificationForMyChannel`).
- `HasRouteNotificationForMail` trait: requires `getEmail()`; `getFirstName()`/`getLastName()` are optional → Symfony `Address`.
- `AnonymousNotifiable`: on-demand recipient (routes per channel); rejects `database` with `InvalidArgumentException`.
- Channels (`Channel\`): `EmailChannel` (`mail`; sets To from `routeNotificationFor('mail')` if the MailMessage has none),
  `DatabaseChannel` (`database`; stores `DatabaseNotification` with `sys_language_uid=-1`, needs `$notifiable->getUid()`).
- Models/repos: `DatabaseNotification` (table `tx_lexnotifications_domain_model_notification`, `createdAt`↔`crdate`,
  data = JSON), `Message` (backend module records), `NotifiableFrontendUser` (mapped to `fe_users`).
  `DatabaseNotificationRepository::findByNotifiable / markAllAsReadForNotifiable / removeAllForNotifiable`.
- `Notification\BackendUserSentMessageToFrontendUser` (ShouldQueue) is used by `Controller\Backend\NotificationController::sendAction`.
  `toMail()` renders the Fluid template `Templates/Email/BackendUserSentMessageToFrontendUser`; it needs `$GLOBALS['TYPO3_REQUEST']` or a site, else
  `SiteNotFoundException` (code 1746276915).

## Gotchas

- Custom channels in *other* extensions must be tagged `notifications.channel` (attribute `#[AutoconfigureTag]` or
  Services.yaml). The `_instanceof` rule in this extension's Services.yaml only applies to its own services.
- Adding methods to `NotificationDispatcherInterface` is a breaking change (record it in `Documentation/Changelog`).
- `composer install` FAILS on exFAT volumes (the original checkout lives on an exFAT drive): plugin init error. Install on APFS/ext4,
  in DDEV, or in a mirror (`rsync -a --exclude .Build --exclude '._*' ./ /tmp/x/`). exFAT `._*` files also break
  the docs renderer → render from a clean copy.
- `composer test` also runs `phplint`, which isn't installed → use `test:php:unit` / `test:php:functional`.
- Known v14.3 deprecations come from the extension (ext_emconf.php without composer `version`/`providesPackages`,
  ext_tables.php, TCA `searchFields`); they don't fail the suite.
- `b13/make` was removed from require-dev (no TYPO3 14 release).

## Tests

```bash
composer install
.Build/bin/phpunit -c Build/phpunit-unit.xml          # 87 tests, no TYPO3 instance
.Build/bin/phpunit -c Build/phpunit-functional.xml    # 62 tests, SQLite by default (memory_limit 1G set in xml)
composer update -W --with "typo3/cms-core:^13.4"      # or ^14.3 to switch TYPO3 major
```
DDEV: `ddev start && ddev composer install && ddev exec .Build/bin/phpunit -c Build/...`; MySQL via env
`typo3DatabaseDriver=mysqli typo3DatabaseHost=db typo3DatabaseUsername=root typo3DatabasePassword=root typo3DatabaseName=func_test`.

Layout:
- `Tests/Unit/Fixtures/`: `TestNotification(?channels, payload, explicitRecipient, level)`, `QueuedTestNotification`,
  `BareNotification`, `NotifiableUser(uid, email, first, last, slackWebhook)`, `EmailOnlyRecipient` (no getUid → mail only),
  `LastNameOnlyRecipient`, `RecordingChannel(name)`, `UnnamedChannel`.
- `Tests/Functional/AbstractNotificationsFunctionalTestCase`: loads `agencelex/notifications` + fixture ext
  `Tests/Functional/Fixtures/Extensions/notifications_test` (autoload `Lex\NotificationsTest\`), mail transport
  `CapturingTransport` (static `$messages`), sets a BE `TYPO3_REQUEST`, helper `fetchNotificationRows()`.
  Fixture channels: `AttributeTaggedChannel` (name `attribute-tagged`, records route), `YamlTaggedChannel` (FQCN key).
- `Tests/Functional/PublicApiTest.php` covers every documented call (notify, notifyNow, send/sendNow arrays,
  channel()->send, route()->route()->notify, routes()->notify). CSV fixtures in `Tests/Functional/Fixtures/Database/`.
- Conventions: PHPUnit attributes (`#[Test]`), `createStub()` when no expectations (PHPUnit 12 notices), `final` test classes.
  Supported: testing-framework ^8.2 || ^9, PHPUnit ^11.2 || ^12.1.

## Docs

Render: `docker run --rm -v <clean copy>:/project ghcr.io/typo3-documentation/render-guides:latest --config=Documentation --fail-on-log`.
Pages: Introduction, Installation, Configuration, Usage (backend module), Developer (API, on-demand, routing, channels), Testing, Changelog.
