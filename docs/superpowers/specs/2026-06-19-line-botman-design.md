# LINE BotMan Chatbot — Design Spec

Date: 2026-06-19
Status: Draft
Author: one2joe

## Overview

Build a LINE Messenger chatbot using BotMan (PHP) as a proof-of-concept that demonstrates the major LINE Messaging API features and can be exercised locally through ngrok. The project is decomposed into two public GitHub repositories: a reusable LINE driver package and a showcase bot.

### Goals

- Let a user chat with the bot in LINE end-to-end.
- Run locally on one development machine with `php -S` and ngrok.
- Keep the driver reusable and the bot as a showcase app.
- Ship a first version that is feature-complete for the demo scope.
- Make logs easy for humans and AI tools to debug.
- Include unit, integration, and smoke testing before release.

### Release Definition

The project is ready when all of the following are true:

- The bot replies successfully in LINE through a verified webhook.
- The showcase bot supports all demo commands and sample flows described in this spec.
- The driver can parse webhook payloads, buffer replies, flush grouped replies, and download media.
- Health checks work.
- Logs contain enough structured detail for AI-assisted debugging.
- Unit, integration, and smoke tests pass.
- A release checklist and rollback note exist.

## Repository Structure

### `one2joe/botman-driver-line`

A reusable BotMan driver for LINE Messaging API, packaged as a standalone Composer library for public distribution.

```
src/
├── LineDriver.php               # Single driver, no sub-drivers
├── Events/
│   ├── AbstractEvent.php        # Implements DriverEventInterface
│   ├── Follow.php
│   ├── Unfollow.php
│   ├── Join.php
│   ├── Leave.php
│   └── Postback.php
└── Extensions/
    ├── Templates/
    │   ├── Buttons.php
    │   ├── Carousel.php
    │   ├── CarouselColumn.php
    │   ├── Confirm.php
    │   ├── ImageCarousel.php
    │   ├── ImageCarouselColumn.php
    │   └── Actions/
    │       └── TemplateAction.php
    └── Imagemap/
        ├── Imagemap.php
        └── Area.php
tests/
├── LineDriverTest.php
├── Events/
└── Extensions/
```

### `one2joe/botman-line-bot`

A showcase LINE chatbot that demonstrates all features. Uses the driver as a local path dependency during development.

```
├── bot.php                      # Entry point (PHP built-in server)
├── config.php                   # Real credentials (gitignored)
├── config.example.php           # Template (committed)
├── config.test.php              # Test credentials (committed)
├── routes/botman.php            # All hears() patterns
├── ngrok.bat                    # Quick tunnel launcher
├── cache/                       # FileCache (auto-created)
├── storage/                     # FileStorage (auto-created)
├── media/                       # Static files for LINE media
├── src/
│   ├── Conversations/
│   │   ├── TutorialConversation.php
│   │   └── NameConversation.php
│   └── Showcase/
│       ├── FlexMessageHelper.php
│       └── TemplateHelper.php
├── tests/
│   ├── Unit/
│   │   ├── Conversations/
│   │   └── Showcase/
│   └── Integration/
│       └── BotServerTest.php
└── .github/workflows/test.yml
```

## Architecture

### Stack

- **Language:** PHP >= 8.0 (8.4 in development)
- **Framework:** None (Pure PHP, built-in server `php -S`)
- **Library:** `botman/botman ^2.0`
- **Webhook:** `POST /botman`
- **Cache:** `BotMan\BotMan\Cache\FileCache`
- **Storage:** `BotMan\BotMan\Storages\Drivers\FileStorage`
- **Tunnel:** ngrok
- **CI:** GitHub Actions (matrix PHP 8.1–8.4)
- **LINE OA ID:** `@340bzlph`

### Operating Mode

- Primary runtime target is a single developer machine.
- The first implementation must be runnable locally without infrastructure beyond PHP and ngrok.
- Production deployment may be added later, but this spec must remain compatible with that path.

### Configuration Strategy

- Use `config.php` for real values.
- Use `config.example.php` as the committed template.
- Configuration must support:
  - LINE credentials
  - cache TTL
  - log level
  - endpoint/path values that may vary
- TTL values must be configurable.
- State should persist across requests and expire on a configurable schedule.

### LINE Driver (`LineDriver`)

Extends `BotMan\BotMan\Drivers\HttpDriver`. Single class manages all message types — no sub-drivers.

| Method | Behavior |
|--------|----------|
| `buildPayload(Request)` | Parse incoming webhook JSON body, extract `events`, set `$this->event`, validate `X-LINE-SIGNATURE` using HMAC-SHA256 with `channel_secret` |
| `matchesRequest()` | HMAC-SHA256 signature valid AND events array non-empty. Returns `false` on bad signature (→ 200 OK silent) |
| `getMessages()` | **Iterate all events** in the `events` array. For each event, create `IncomingMessage`. Detect message type: text → use actual text; image/audio/video → set `Image::PATTERN` etc. + extras(`messageId`); location → `Location::PATTERN` + `setLocation()`; sticker → extras(`packageId`, `stickerId`); file → extras(`fileName`, `fileSize`); postback → set `data` as text, `displayText` in extras |
| `getConversationAnswer(IncomingMessage)` | Return `Answer::create($message->getText())` |
| `getUser(IncomingMessage)` | Return `new User($matchingMessage->getSender())` (userId only) |
| `hasMatchingEvent()` | Return `Follow`/`Unfollow`/`Join`/`Leave` event objects for event-type webhooks. **Postback is NOT routed through this method** — it goes through `getMessages()` text matching |
| `buildServicePayload($message, $matchingMessage, $additionalParameters)` | Detect input type in order: `Buttons`/`Carousel`/`Confirm`/`ImageCarousel` → LINE template JSON; `Question` → text + Quick Reply (map `addButton()` actions as message type); `OutgoingMessage` → check attachment (Image/Video/Audio/Location) + text fallback; raw `array` → pass through as Flex Message |
| `sendPayload($payload)` | **Buffer** reply into `$this->replyBuffer[]` with `['replyToken' => ..., 'messages' => [...]]`. Does NOT send immediately |
| `messagesHandled()` | **Flush buffer**: group replies by `replyToken`, slice each group to **max 5 messages** (LINE limit), POST each group to `/v2/bot/message/reply`. Dropped messages > 5 are logged |
| `sendRequest($endpoint, $params, $matchingMessage)` | Low-level API request — maps to LINE API with auth header |
| `getMessageContent($messageId)` | Download media content from `GET /v2/bot/message/{messageId}/content`. Returns raw binary |
| `isConfigured()` | `channel_secret` AND `channel_access_token` non-empty |
| `setLogger(callable)` | Register a logging callback. Callback receives `(string $level, string $message, array $context = [])` |

#### Reply Flow

1. User's callback calls `$bot->reply(...)` → BotMan calls `buildServicePayload()` → returns payload array
2. BotMan calls `sendPayload()` → driver appends to buffer (does NOT send to LINE)
3. BotMan's `listen()` finishes → calls `messagesHandled()` if method exists
4. `messagesHandled()` groups buffer by replyToken, sends all in one POST per token (max 5 per token)

#### Logging Format

Logging must be optimized for AI-assisted debugging.

- Primary output format: JSON.
- Each log record should include at least:
  - `timestamp`
  - `level`
  - `event`
  - `message`
  - `context`
  - `request_id`
  - `reply_token`
  - `raw_request`
  - `raw_response`
- Request and response bodies should be captured when available.
- Logging should be structured enough that a model can reconstruct a request lifecycle from the output alone.
- Driver logging remains callback-based; the bot decides how to render or persist records.

### LINE API Integration

| Endpoint | Method | Usage |
|----------|--------|-------|
| `/v2/bot/message/reply` | POST | Reply to user (replyToken scoped) |
| `/v2/bot/message/{messageId}/content` | GET | Download media (image, video, audio) |

API base URL: `https://api.line.me/v2/bot` (overridable via config for testing)

### Template Builder Classes

All template builders have a `toArray()` method that returns the LINE API JSON structure.

| Class | LINE Template Type |
|-------|-------------------|
| `Buttons` | `buttons` |
| `Carousel` | `carousel` (contains `CarouselColumn` items) |
| `Confirm` | `confirm` |
| `ImageCarousel` | `image_carousel` (contains `ImageCarouselColumn` items) |

#### TemplateAction (9 action types)

| Method | Action Type | Parameters |
|--------|-------------|------------|
| `->message($text)` | `message` | Required: `text` |
| `->postback($data, $displayText = null)` | `postback` | Required: `data`. Optional: `displayText` |
| `->uri($uri)` | `uri` | Required: `uri` |
| `->datetimePicker($mode, $initial = null, $max = null, $min = null)` | `datetimepicker` | Required: `mode` (date/time/datetime). Optional: `initial`, `max`, `min` |
| `->camera()` | `camera` | No parameters |
| `->cameraRoll()` | `cameraRoll` | No parameters |
| `->location()` | `location` | No parameters |
| `->clipboard($text)` | `clipboard` | Required: `clipboardText` |

#### altText auto-fallback

If user does not provide `altText` on a template, the driver auto-generates from the first text content available (title, text, or first button label).

## Bot Logic

### Entry Point (`bot.php`)

```
GET  /              → 200 JSON health response
GET  /media/{file}  → Serve static files from media/ directory
POST /botman        → LINE webhook processing
                       1. Check empty events → 200 OK
                       2. Create FileCache + FileStorage dirs if needed
                       3. BotManFactory::create(config, cache, request, storage)
                       4. Add hears() and on() from routes/botman.php
                       5. $botman->listen() wrapped in try-catch
                       6. Always return 200 OK
อื่นๆ               → 200 OK
```

#### Health Endpoint

The root endpoint must return JSON with:

- basic application status
- config readiness
- version/build info

Version/build info must include:

- package/version information
- git commit SHA when available

The health endpoint exists to support quick smoke checks after restart, deploy, or ngrok setup.

### Conversation Commands (Thai + English)

| hears() pattern | Trigger | Response |
|-----------------|---------|----------|
| `สวัสดี|hi|hello` | User says hello | Text + context-aware Quick Reply |
| `/ช่วยเหลือ|/help` | Help command | Buttons template with command list, descriptions, and short examples |
| `/เมนู|/menu` | Menu command | Carousel with feature cards, descriptions, and usage examples |
| `/แนะนำ|/tutorial` | Tutorial command | Start `TutorialConversation` |
| `/ชื่อ|/name` | Name command | Start `NameConversation` |
| `/เทมเพลต|/template` | Template demo | Buttons → chooses sub-template type |
| `/เฟล็กซ์|/flex` | Flex demo | Flex Message bubble |
| `__image__` | User sent image | "I see you sent an image! (Demonstration)" |
| `__sticker__` | User sent sticker | "Nice sticker!" + extras data |
| `__file__` | User sent file | "File received!" |
| `__audio__` | User sent audio | "Audio received!" |
| `__video__` | User sent video | "Video received!" |
| `on('follow')` | User adds bot | Start `TutorialConversation` |
| fallback `(.*)` | No pattern matched | Fallback message with navigation quick reply |

### Conversations

**NameConversation:**
1. Ask "ชื่ออะไรครับ / What's your name?"
2. User replies → save via `$this->user->save(['name' => $answer->getText()])`
3. Reply "ยินดีที่ได้รู้จัก {name}! / Nice to meet you, {name}!"
4. Next `hears('สวัสดี')` → greet by stored name

**TutorialConversation (auto-starts on follow):**
1. "มาเริ่มเรียนรู้กันเลย! Step 1: พิมพ์ /เฟล็กซ์ หรือ /flex"
2. When user types `/เฟล็กซ์`: reply Flex → advance to Step 2
3. Step 2: "Step 2: พิมพ์ /เทมเพลต หรือ /template"
4. Step 3: "Step 3: พิมพ์ /ชื่อ หรือ /name"
5. Step 4: "Step 4: ส่งสติกเกอร์มาให้ฉันดูสิ!"
6. Step 5: "🎉 เรียนจบแล้ว! ลองพิมพ์ /เมนู เพื่อดูทั้งหมด"

## Testing

### `botman-driver-line` — Unit Tests

PHPUnit 11 + Mockery. Mock the HTTP client (`Curl`).

| Test | Coverage |
|------|----------|
| `LineDriverTest` | Signature validation (valid/bad/missing HMAC), buildPayload parsing, matchesRequest for all conditions, getMessages for all event types (text, image, audio, video, location, sticker, file, postback, follow), buildServicePayload for all input types (Buttons, Carousel, Confirm, ImageCarousel, Question, OutgoingMessage, raw array), sendPayload buffering, messagesHandled flush + grouping + 5-slice, getMessageContent, isConfigured |
| `Events/*Test` | Each event class returns correct getName() and passes payload |
| `Extensions/*Test` | Each template builder produces correct toArray() JSON, TemplateAction produces correct action JSON for all 9 types, altText fallback logic |

### `botman-line-bot` — Unit + Integration Tests

**Unit tests:**

| Test | What it tests |
|------|--------------|
| `TutorialConversationTest` | Step progression, name saving, completion |
| `NameConversationTest` | Ask name, save, greet |
| `FlexMessageHelperTest` | Returns valid Flex JSON structure |
| `TemplateHelperTest` | Returns valid template builder instances |

**Integration tests (BotServerTest):**

```
[PHPUnit]                         [Bot Server]              [Mock LINE API]
    |                                  |                          |
    |── POST /botman (HMAC) ─────────→ |                          |
    |                                  |── POST /message/reply ──→|
    |                                  |                          |
    |←──────── 200 OK ──────────────── |                          |
    |── GET mock log ────────────────→ |                          |
    |←──── assert payload ──────────── |                          |
```

Setup:
1. `proc_open()`: Start `MockLineApiServer.php` on a free port
2. `proc_open()`: Start `php -S localhost:{botPort} bot.php` with `config.test.php`
3. Test sends HTTP POST with real LINE-format body + real HMAC-SHA256 signature (using `test-secret`)
4. Assert 200 response
5. Read mock LINE API log → assert reply payload, max 5 messages, correct structure

Test scenarios:
- `test_สวัสดี_triggers_reply_with_quick_reply()` — text → reply + quick reply
- `test_image_sets_correct_pattern()` — image event → `__image__` pattern
- `test_sticker_stores_extras()` — sticker → extras contains packageId/stickerId
- `test_follow_does_not_generate_reply()` — follow event → no outbound call
- `test_postback_routes_to_hears()` — postback with data `/เมนู` → triggers menu
- `test_flex_message_returns_raw_array()` — flex event → sends array as-is
- `test_conversation_persists_across_requests()` — multiple requests → conversation continues
- `test_max_five_messages_per_reply()` — buffer > 5 → only 5 sent
- `test_invalid_signature_returns_200()` — bad HMAC → 200, no outbound call
- `test_unknown_command_shows_fallback()` — unmatched → fallback reply

### Smoke Tests

Smoke tests are mandatory before release and should be runnable against the ngrok URL and LINE webhook verification flow.

Minimum smoke coverage:

- Health endpoint returns a JSON success response.
- LINE webhook verification succeeds.
- A hello message returns a valid reply.
- A fallback message returns guidance and navigation.
- Logging captures a readable request lifecycle.
- Reply buffering still respects the five-message LINE limit.

### CI

Both repos have GitHub Actions with PHP 8.1/8.2/8.3/8.4 matrix:

```yaml
# .github/workflows/test.yml
strategy:
  matrix:
    php: ['8.1', '8.2', '8.3', '8.4']
steps:
  - uses: shivammathur/setup-php@v2
    with:
      php-version: ${{ matrix.php }}
  - run: composer install
  - run: vendor/bin/phpunit
```

`botman-line-bot` uses `config.test.php` (committed, uses fake credentials) for CI integration tests.

## Config Files

### `config.test.php` (committed, used by CI)

```php
<?php
return [
    'line' => [
        'channel_secret' => 'test-secret-12345678901234567890123456789012',
        'channel_access_token' => 'test-access-token',
    ],
];
```

### `config.example.php` (committed, template)

```php
<?php
return [
    'line' => [
        'channel_secret' => 'YOUR_CHANNEL_SECRET_HERE',
        'channel_access_token' => 'YOUR_CHANNEL_ACCESS_TOKEN_HERE',
    ],
];
```

### `config.php` (gitignored, real credentials)

Same structure as example but with real values from LINE Developers Console.

## Error Handling

| Scenario | Behavior |
|----------|----------|
| Bad HMAC signature | `matchesRequest()` = false → 200 OK, no processing, detailed log |
| Empty events array | Return 200 OK immediately, detailed log |
| LINE API returns non-200 | Log response and apply retry/fallback where appropriate |
| Reply buffer > 5 messages | First 5 sent, rest discarded, log dropped count |
| Exception in `$botman->listen()` | Caught by try-catch, 200 OK returned |
| LINE API media URL not HTTPS | Message sent but LINE may reject display |
| Conversation cache full | Old conversations evicted by TTL configured value |

### Retry and Fallback Policy

- Log every failure that affects reply delivery.
- Retry only when the failure is likely transient and safe to retry.
- If a user-visible response is still possible, provide a fallback reply or navigation hint.
- Failures must not break the webhook response path.

## Development Workflow

The exact commands should be written into the implementation docs and scripts, but the required flow is:

1. Install dependencies.
2. Start the showcase bot server locally.
3. Start an ngrok tunnel to the local server.
4. Register the ngrok webhook URL in LINE Developers Console.
5. Verify the webhook.
6. Send a LINE message and confirm the reply, logs, and health endpoint.

This flow must be reproducible on a single development machine without additional infrastructure.

## Key Design Decisions

| Decision | Rationale |
|----------|-----------|
| No sub-drivers | LINE sends multiple event types in a single request. Single LineDriver iterates all events, avoiding event loss from driver routing |
| Reply buffer vs direct send | LINE's replyToken is one-time use. Buffering allows multiple `$bot->reply()` calls in one listen cycle to be batched |
| Postback via hears(), not hasMatchingEvent() | Consistent routing model — postback data matches hears() patterns, not separate event handlers |
| TemplateAction class instead of additionalParameters | Cleaner API, discoverable via IDE autocomplete, validation built-in |
| Flex as raw array | Too many Flex variants to build fluent API. Raw array gives maximal flexibility |
| No Imagemap builder (Flex covers it) | YAGNI — Flex handles imagemap use cases with richer layouts |
| Real HMAC in integration tests | Tests match production behavior exactly. No `skip_signature_validation` flag needed |
| Logger callback, not hardcoded | Keeps driver agnostic; user controls log destination, format, and storage |

## Limitations

1. **BotMan's listen() processes either driver events OR messages in one cycle.** If LINE sends an event-only payload (follow) and a message payload in separate requests, both are handled correctly. If both are in the same request (rare), the event fires and the message is silently skipped. This is a BotMan core limitation, not driver-specific.
2. **Media content download** (`getMessageContent()`) fetches from LINE API during the webhook lifecycle. For large files, consider async processing in production.
3. **No push messaging** — the driver supports reply-only. Push notifications require separate LINE API calls.
4. **Rate limits** — LINE messaging API has per-second rate limits (~500 req/s per bot). Not a concern for POC but relevant for production.

## Release Checklist

- `config.php` exists locally and `config.example.php` is committed.
- The webhook verifies successfully in LINE Developers Console.
- Health endpoint returns JSON and includes version/build info.
- Hello, help, menu, tutorial, name, template, flex, media, postback, and fallback flows work.
- Logs are JSON and include request and response detail.
- Unit tests pass.
- Integration tests pass.
- Smoke test through ngrok/LINE passes.
- Reply buffering still caps at 5 messages per reply token.
- A rollback note exists for the current build.

## Implementation Notes

- Use English terms as the primary vocabulary in code and docs, with Thai explanations where helpful.
- Keep the bot and driver split into separate repositories.
- Keep the driver path-referenced from the bot during local development.
- Preserve the current design choice that postback matches through `hears()` rather than a dedicated event handler.
