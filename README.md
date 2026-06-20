# BotMan LINE Demo

Minimal BotMan app wired to the local LINE driver in `../botman-driver-line`.

## Setup

```bash
composer install
copy .env.example .env
```

Fill in your LINE credentials in `.env`.

## Run locally

```bash
composer serve
```

Then expose it with ngrok:

```bash
ngrok http 8000
```

Use the ngrok HTTPS URL as your LINE webhook URL.

## Testing

```bash
composer test
```

Run tests before every commit/push. Tests cover the bot response pipeline using `FakeDriver` (no external API calls).

## Webhook path

Point LINE to the root URL of this app. The BotMan listener handles the POST webhook directly.
