# laravel-modules-chat

Real-time **chat** module for Laravel: rooms, direct messages, threaded replies, presence, reactions, attachments, and `@mentions`. Driver-agnostic broadcasting (Laravel Reverb recommended).

## Requirements

- PHP 8.4+
- Laravel 12.x or 13.x
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

## Filament

Filament v3/v4/v5 admin resources are planned for v2.1. v2.0 is headless.

## License

MIT (c) Ozan Kurt
