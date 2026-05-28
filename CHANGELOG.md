# Changelog

All notable changes to this project will be documented in this file. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-05-28

Initial release of the `ozankurt/laravel-modules-chat` package.

### Added

- Models: `Conversation` (soft-deletes, `directBetween` get-or-create via `dm_key`), `Participant` (role + notifications + `last_read_at`), `Message` (soft-deletes, `HasMedia` via Spatie medialibrary on the `chat-attachments` collection, one-level threaded replies via `parent_id`), `Reaction` (unique `(message, user, emoji)`), `Mention` (unique `(message, user)`), `Presence` (`user_id` primary key).
- Enums: `ConversationType` (Room, Direct), `ConversationVisibility` (Public, Unlisted, Private), `ParticipantRole` (Owner, Admin, Member), `ParticipantNotifications` (All, Mentions, None), `PresenceStatus` (Online, Away, Dnd, Offline).
- `Conversation::directBetween(Model $a, Model $b): Conversation` — deterministic DM get-or-create via `Kurt\Modules\Chat\Support\ConversationKey::forDirect($a, $b)` (`min:max` of the two user keys, naturally sorted) and a unique `dm_key` column, so two callers racing on the same pair end up with one row.
- `Conversation::send(Model $author, string $body, ?Message $parent = null): Message` — creates the message inside the conversation, bumps `last_message_at`, dispatches `MessageSent` (with `mentions` eager-loaded).
- `Conversation::markRead(Model $user)` / `unreadCountFor(Model $user)`.
- `Message::reactWith(Model $user, string $emoji): Reaction` — idempotent via `firstOrCreate` on the unique `(message, user, emoji)` index.
- `Message::unreactWith(Model $user, string $emoji): void` — removes the row.
- Scopes: `Conversation::scopeRooms`, `scopeDirect`, `scopeVisibleTo($user)`; `Message::scopeRoots`, `scopeInThreadOf($root)`.
- Mention extraction: `MentionResolver` contract + default `UsernameMentionResolver` (regex on `chat.mentions.pattern` + DB lookup by `chat.mentions.username_column` via Core's `UserResolver`). Swap with a custom resolver via `config('chat.mentions.resolver')`.
- `MessageObserver` enforces `chat.message_max_length` on `creating`, extracts mentions on `saving`, persists `chat_mentions` rows + dispatches `MentionFired` per mention on `created`, and dispatches `MessageEdited` / `MessageDeleted` on `updated` / `deleted`.
- Events (all `ShouldBroadcastNow`): `MessageSent`, `MessageEdited`, `MessageDeleted(int $messageId, int $conversationId)`, `ReactionAdded`, `ReactionRemoved(int $messageId, int $userId, string $emoji)`, `UserStartedTyping(Model $user, int $conversationId)`, `UserStoppedTyping`, `MentionFired`.
- Broadcast channel callbacks in `routes/channels.php`: `chat.room.{id}`, `chat.dm.{id}`, `chat.user.{id}`, and presence `chat.conversation.{id}` — all checking `Participant` membership at authorize time. Auto-registered via `Broadcast::routes()` when `chat.broadcasting.enabled = true`.
- Policies: `ConversationPolicy` (`view`, `sendMessage`, `react`, `manageRoom`), `MessagePolicy` (`update` / `delete` enforce `chat.edit_window_minutes` for the author), `ReactionPolicy` (`delete` by the user who created it). Global `canModerateChat` gate bypasses all three.
- Console commands: `chat:prune-presence` (marks `chat_presence` rows older than `chat.presence.offline_after_seconds` as `offline`; scheduled every minute via the provider), `chat:demo` (seeds a room + DM with sample messages).
- Migrations: `chat_conversations`, `chat_participants`, `chat_messages`, `chat_reactions`, `chat_mentions`, `chat_presence`.
- Pest 3 test suite covering: enum cases, `ConversationKey` deterministic key, DM `directBetween` idempotency + participant creation, `Conversation::send` (last_message_at + `MessageSent` via `Event::fake`), reaction idempotency + toggle-off + independence per emoji, mention extraction with default and custom resolver + `MentionFired` count, edit window enforcement via the message policy, and `chat:prune-presence` only touching stale heartbeats.
- GitHub Actions CI (Laravel 12, PHP 8.4) running Pint, PHPStan level 8, and Pest.

### Deferred

- Filament v3/v4/v5 admin resources (`ConversationResource`, `MessageResource` moderation queue, `PresenceResource` widget) will land in v2.1. v2.0 is headless.
- Tagging support is deferred — conversations do not expose a tag pivot yet.
