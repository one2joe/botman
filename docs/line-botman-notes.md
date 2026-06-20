# LINE BotMan Notes

This note captures the working setup that connects the local BotMan app in `D:\www\botman` with the local LINE driver in `D:\www\botman-driver-line`.

## What Was Built

- Minimal PHP BotMan app served by `php -S 127.0.0.1:8000 -t public`
- Local LINE driver wired in through Composer `path` repository
- ngrok tunnel pointing to the local PHP server
- Basic message handlers for `hi`, `help`, and a fallback reply

## Important Files

- [`composer.json`](../composer.json)
- [`public/index.php`](../public/index.php)
- [`.env.example`](../.env.example)
- [`.gitignore`](../.gitignore)
- [`README.md`](../README.md)
- LINE driver source: `D:\www\botman-driver-line\src\LineDriver.php`

## Runtime Setup

The app expects these environment variables:

```env
LINE_CHANNEL_ACCESS_TOKEN=...
LINE_CHANNEL_SECRET=...
```

Keep them in `.env` only. Do not commit `.env` to git.

## ngrok

Current public webhook URL used during testing:

```text
https://cosmic-rattler-truly.ngrok-free.app
```

Local tunnel target:

```text
http://localhost:8000
```

## Working Commands

Start the PHP server:

```powershell
php -S 127.0.0.1:8000 -t public
```

Start ngrok:

```powershell
& 'D:\Dropbox\Program\ngrok.exe' http 8000 --host-header=rewrite --url=cosmic-rattler-truly.ngrok-free.app
```

## Driver Fixes Applied

The local LINE driver needed to implement BotMan contract methods that were missing:

- `isConfigured()`
- `getConversationAnswer()`
- `sendRequest()`

That patch removed the fatal error and allowed BotMan to match and reply to LINE webhooks correctly.

## Verification

- PHP endpoint returned `200 OK`
- ngrok received LINE webhook POST requests
- `X-Line-Signature` matched the configured `LINE_CHANNEL_SECRET`
- BotMan successfully replied to `hi`

## Notes

- `Webhook redelivery` was left off during initial debugging.
- The repo intentionally ignores `.env` and `vendor/`.
- Existing deleted files in the git status were not changed.
