# laravel-modules-chat

[![tests](https://github.com/OzanKurt/laravel-modules-chat/actions/workflows/tests.yml/badge.svg)](https://github.com/OzanKurt/laravel-modules-chat/actions/workflows/tests.yml)

Real-time **chat** module for Laravel: rooms, direct messages, threaded replies, presence, reactions, attachments, and `@mentions`. Driver-agnostic broadcasting (Laravel Reverb recommended).

## Requirements

- PHP `^8.4`
- Laravel `^13.0`
- `ozankurt/laravel-modules-core` v2.x

## Installation

```bash
composer require ozankurt/laravel-modules-chat
```

Publish config and migrations:

```bash
php artisan vendor:publish --tag=chat-config
php artisan vendor:publish --tag=chat-migrations
php artisan migrate
```

For real-time delivery, install a broadcaster (Reverb is the recommended one):

```bash
composer require laravel/reverb
php artisan reverb:install
```

## Concepts

- **Conversation** — abstract container. Two concrete kinds via `type`:
  - `Room` (`type=room`) — named, multi-participant, joinable.
  - `Direct` (`type=direct`) — exactly two participants. A deterministic `dm_key` (sorted pair of user keys, e.g. `7:12`) makes `Conversation::directBetween($a, $b)` idempotent regardless of argument order.
- **Message** — belongs to a conversation; optional `parent_id` for one-level **threaded** replies.
- **Participant** — pivot between a user and a conversation, with `role` (`owner`, `admin`, `member`) and per-user state (`last_read_at`, `muted_until`, `notifications`).
- **Reaction** — `emoji` (unicode or shortcode) by a user on a message; unique per `(message, user, emoji)`. `reactWith` is idempotent; `unreactWith` removes the row.
- **Attachment** — file uploaded via Spatie medialibrary on the `chat-attachments` collection.
- **Mention** — extracted at `Message::saving` and persisted as rows on `chat_mentions` for fan-out + audit. Default resolver matches `@username` and looks up by `username` column; swap via `chat.mentions.resolver`.
- **Presence** — optional `chat_presence` table with a heartbeat. `chat:prune-presence` marks stale rows as `offline`.

## What it provides

- Models: `Conversation`, `Participant`, `Message` (`HasMedia`), `Reaction`, `Mention`, `Presence`.
- Enums: `ConversationType`, `ConversationVisibility`, `ParticipantRole`, `ParticipantNotifications`, `PresenceStatus`.
- `Conversation::directBetween($a, $b)` — deterministic DM get-or-create via `dm_key` unique constraint.
- `Conversation::send($author, $body, ?$parent)` — creates the message, bumps `last_message_at`, dispatches `MessageSent` (which broadcasts on `chat.room.{id}` or `chat.dm.{id}`).
- `Message::reactWith($user, $emoji)` / `unreactWith($user, $emoji)` — idempotent toggle pair.
- `Message::scopeRoots()` and `Message::scopeInThreadOf($root)` for thread queries.
- Domain events: `MessageSent`, `MessageEdited`, `MessageDeleted($messageId, $conversationId)`, `ReactionAdded($reaction)`, `ReactionRemoved($messageId, $userId, $emoji)`, `UserStartedTyping` / `UserStoppedTyping`, `MentionFired($mention)`. All implement `ShouldBroadcastNow`.
- Broadcast channels (`routes/channels.php`): `chat.room.{id}`, `chat.dm.{id}`, `chat.user.{id}`, `chat.conversation.{id}` (presence). Auto-registered via `Broadcast::routes()` when `chat.broadcasting.enabled = true` (default).
- Mention extraction with default `UsernameMentionResolver` (regex + DB lookup) and a pluggable `MentionResolver` contract for custom strategies.
- Observer `MessageObserver` enforces `chat.message_max_length`, extracts mentions, persists `chat_mentions` rows, and dispatches `MentionFired` per mention plus `MessageEdited` / `MessageDeleted` on update/delete.
- Policies: `ConversationPolicy`, `MessagePolicy` (edit window enforced from `chat.edit_window_minutes`), `ReactionPolicy`. A global `canModerateChat` gate bypasses all three.
- Console commands: `chat:prune-presence` (scheduled every minute) and `chat:demo` (seeds a room + DM).

## Driver-agnostic broadcasting

The package only depends on `illuminate/broadcasting`. It works with any driver Laravel supports — Reverb, Pusher, Ably, etc. Configure `BROADCAST_CONNECTION` in your app; the channel callbacks check `Participant` membership at authorize time.

For tests, broadcasting is **never** booted — events are asserted via `Event::fake([MessageSent::class, ...])` and `Event::assertDispatched(...)`.

## REST API

An out-of-the-box JSON REST API built on the Core [API kit](https://github.com/OzanKurt/laravel-modules-core#api-kit). It is the **history + send + management** surface that complements real-time broadcasting - it does **not** replace it: sending a message over HTTP still dispatches `MessageSent`, so websocket clients update exactly as they do for a message sent through the domain service directly.

**Safe by default.** Nothing is registered until you opt in. Set the mode in your app's `.env`:

```dotenv
CHAT_HTTP_MODE=api
```

Chat has no public surface, so **every** endpoint requires an authenticated, participating user: guests get `401`, and non-participants get `403`. Reads are scoped to the caller's conversations via the chat Policies; all writes are additionally Policy-checked (edit/delete honour `chat.edit_window_minutes`; add/remove participant and rename require room owner/admin).

Configure the surface under `chat.http` (published `config/chat.php`):

| Key | Default | Purpose |
|---|---|---|
| `mode` | `env('CHAT_HTTP_MODE', 'headless')` | `headless` \| `api` \| `ui`. Routes register only for `api`/`ui`. |
| `prefix` | `api/chat` | URL prefix for every route. |
| `middleware` | `['api']` | Base middleware for the route group. |
| `auth_middleware` | `['auth']` | Applied to the whole group (chat is never public). Set to e.g. `['auth:sanctum']` for token auth. |
| `rate_limit` | `'60,1'` | `maxAttempts,decayMinutes` for the `chat-api` throttle (keyed by user id). |

Responses use the Core envelope: `{ "data": ..., "meta": ... }` for success (paginated lists add `meta.pagination`), `{ "message": ..., "errors": ... }` for errors.

### Endpoints

All paths are relative to the `api/chat` prefix. Named routes use the `chat.api.` prefix.

| Method | Path | Action |
|---|---|---|
| `GET` | `conversations` | The authenticated user's conversations (paginated, most-recent-active first; `?filter[type]`, `?filter[visibility]`, `?sort`). |
| `POST` | `conversations` | Create a room (`type=room`, `name`, optional `description`, `participant_ids[]`) or resolve/create a direct conversation (`type=direct`, `user_id`). |
| `GET` | `conversations/{conversation}` | Show a conversation. |
| `PATCH` | `conversations/{conversation}` | Rename / update a room (owner/admin). |
| `DELETE` | `conversations/{conversation}` | Leave the conversation (removes the caller's participant row). |
| `GET` | `conversations/{conversation}/messages` | Message history, paginated newest-first (`?per_page`, `?page`). |
| `POST` | `conversations/{conversation}/messages` | Send a message (`body`, optional `parent_id`) via `Conversation::send()` - dispatches `MessageSent`. |
| `PATCH` | `messages/{message}` | Edit own message within the edit window (dispatches `MessageEdited`). |
| `DELETE` | `messages/{message}` | Soft-delete own message within the edit window (dispatches `MessageDeleted`). |
| `POST` | `messages/{message}/reactions` | Add a reaction (`emoji`) - idempotent, dispatches `ReactionAdded`. |
| `DELETE` | `messages/{message}/reactions` | Remove own reaction (`emoji`) - dispatches `ReactionRemoved`. |
| `POST` | `conversations/{conversation}/read` | Mark read up to now, or up to a message via `message_id`. |
| `GET` | `conversations/{conversation}/unread-count` | Unread message count for the caller. |
| `GET` | `conversations/{conversation}/participants` | List participants (paginated). |
| `POST` | `conversations/{conversation}/participants` | Add a participant (`user_id`, optional `role`; owner/admin). |
| `DELETE` | `conversations/{conversation}/participants/{user}` | Remove a participant (owner/admin). |

Direct-message creation and participant add/remove resolve users through the Core `UserResolver` (`kurtmodules.user_model` or `auth.providers.users.model`).

## Filament admin

The package ships parallel admin resource sets for Filament **v3, v4, and v5** —
`ConversationResource`, `MessageResource`, and `PresenceResource`. The correct
set is chosen at runtime from the installed Filament major, so you register a
single version-dispatching plugin on your panel:

```php
use Filament\Panel;
use Kurt\Modules\Chat\Filament\ChatPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        // ...
        ->plugin(ChatPlugin::make());
}
```

`ChatPlugin::make()` resolves to the matching `V3`/`V4`/`V5` plugin via
`Kurt\Modules\Core\Support\FilamentVersion`. Install whichever Filament major
your app uses, plus the Spatie media-library plugin (message attachments are
edited through it):

```bash
# whichever your app runs
composer require filament/filament:"^3.0|^4.0|^5.0"
composer require filament/spatie-laravel-media-library-plugin:"^3.0|^4.0|^5.0"
```

What the resources give you:

- **Conversations** — type (room/direct) and visibility (public/unlisted/private)
  enum selects plus name and description; a table with type and visibility
  badges, participant count, last-message timestamp, and type/visibility filters.
- **Messages** — a moderation queue: body, type (user/system), conversation
  select, an `edited_at` picker and a Spatie media-library upload for the
  `chat-attachments` collection. The table surfaces soft-deleted messages (the
  `SoftDeletingScope` is dropped) with a trashed filter, a flagged indicator, and
  delete / restore / force-delete row and bulk actions for moderation.
- **Presence** — a read-only view of the heartbeat table (user, status badge,
  status message, last heartbeat) with a status filter; no create/edit/delete.

## Testing

```bash
composer install
vendor/bin/pint --test
vendor/bin/phpstan analyse --memory-limit=2G
vendor/bin/pest
```

CI runs the same checks on every push and pull request
(`.github/workflows/tests.yml`), against PHP 8.4 / Laravel 13. Static analysis
is held at **PHPStan level 8**; the suite runs on **Pest 5**.

## License

MIT (c) Ozan Kurt
