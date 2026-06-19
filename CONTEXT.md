# LINE BotMan Chatbot

Two-repo project building a LINE Messenger chatbot using BotMan (PHP) as a proof-of-concept. A reusable LINE driver package and a showcase bot that demonstrates LINE Messaging API features.

## Language

**Driver**:
A BotMan `HttpDriver` that bridges BotMan to the LINE Messaging API. Single class, no sub-drivers.
_Avoid_: Adapter, connector

**Showcase Bot**:
The demo application that uses the driver. Priority is feature completeness over driver polish.

**Reply Buffer**:
A queue in the driver that accumulates outbound messages during a single webhook lifecycle. Flushed to LINE API at the end of processing, grouped by reply token, max 5 per token.
_Avoid_: Send queue, batch

**Flex Message**:
LINE's flexible layout message format. Sent as raw PHP arrays (no builder class). Validated by AI during coding via LINE's `/v2/bot/message/validate/reply` endpoint.

**Template Message**:
LINE structured message types (buttons, carousel, confirm, image carousel) — built via dedicated builder classes (`Buttons`, `Carousel`, `Confirm`, `ImageCarousel`).

**Template Action**:
A single action within a template (message, postback, uri, datetime picker, camera, camera roll, location, clipboard). Built via the fluent `TemplateAction` class.

**Webhook Event**:
An incoming event from LINE platform. Types: follow, unfollow, join, leave, postback. Postback is routed through `hears()` pattern matching, not event handlers.

**Event Class**:
A data class in the driver repo that implements `DriverEventInterface`. Separate classes for each webhook event type. Lives in the driver so consumers can type-hint against concrete event types.

**Driver Logger**:
Driver accepts a logging callback via `setLogger(callable)`. Callback receives structured data `(string $event, string $message, array $context)`. The caller (showcase bot) is responsible for rendering and output.

**Imagemap**:
A LINE message format using a single image with clickable areas. Built via `Imagemap` and `Area` builder classes in the driver.
_Avoid_: Image map, imagemap builder

**Rich Menu**:
LINE's persistent menu above the chat input. Configured through LINE Developers Console. Not managed programmatically by the driver or bot.
_Avoid_: Richmenu, rich menu API

**Local Dev Dependency**:
Showcase bot references the driver via Composer path repository (`"type": "path"`). Both repos checked out as siblings.

