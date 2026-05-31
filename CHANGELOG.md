# Changelog

All notable changes to this project will be documented in this file. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.3.1] - 2026-05-31

### Removed
- The legacy `chat_reactions` / `chat_mentions` tables and their data
  migrations. Reactions and mentions live in the shared `interactions_*` store
  from a fresh install — nothing creates or reads the Chat-local tables anymore.

## [2.3.0] - 2026-05-31

### Changed

- **Reactions and mentions now persist through the [Interactions](https://github.com/OzanKurt/KurtModules-Interactions) module** instead of Chat-local tables. `Message` uses Interactions' `Reactable` and `Mentionable` traits, so `reactWith()` / `unreactWith()` and the mention pipeline write to the shared `interactions_reactions` / `interactions_mentions` store (reactable/mentionable = `Message`). Behaviour is unchanged: idempotent reactions, username mention extraction, `MentionFired`, the `ReactionAdded` / `ReactionRemoved` broadcast events, and the `seen_at` read receipt all still work.
- `Message::reactions()` / `Message::mentions()` are now polymorphic `MorphMany` relations returning `Kurt\Modules\Interactions\Engagement\Models\Reaction` / `Kurt\Modules\Interactions\Mentions\Models\Mention`. The Chat-local `Reaction` / `Mention` models and their factories were removed, and mention rows now key on `mentioned_user_id` (the Interactions column) rather than `user_id`.

### Added

- `ozankurt/laravel-modules-interactions` (`^1.3`) dependency.

## [2.2.0] - 2026-05-30

### Added

- Filament admin resources for **v3, v4, and v5** in parallel, registered through a single version-dispatching `Kurt\Modules\Chat\Filament\ChatPlugin::make()` facade that resolves the matching `V{n}` plugin from the installed Filament major via Core's `FilamentVersion`.
  - `ConversationResource` — type/visibility enum selects + name/description; table with type & visibility badges, participant count, `last_message_at`, and type/visibility filters.
  - `MessageResource` — moderation queue: body/type/conversation/`edited_at` form fields plus a `SpatieMediaLibraryFileUpload` on the `chat-attachments` collection. The table drops the `SoftDeletingScope` so soft-deleted messages surface, with a trashed filter, a flagged indicator, a conversation filter, and delete/restore/force-delete row + bulk actions.
  - `PresenceResource` — read-only view of the `chat_presence` heartbeat table (user, status badge, status message, last heartbeat) with a status filter; no create/edit/delete.
- `filament/spatie-laravel-media-library-plugin` dev dependency (`^3.0 || ^4.0 || ^5.0`) for the message-attachment upload field.
- Per-Filament-version PHPStan configs (`phpstan-filament-v{3,4,5}.neon`); the base `phpstan.neon` excludes all three `src/Filament/V{3,4,5}` dirs and the `ChatPlugin` facade so the default analysis stays version-agnostic.
- CI matrix Filament axis (`3.*`, `4.*`, `5.*`); each cell installs the matching Filament + media-library plugin and runs the per-major PHPStan config. Version-guarded Pest smoke tests assert each resource's structure (form fields, table columns, filters, actions) for the installed major.

## [2.1.1] - 2026-05-30

### Fixed
- Migrations now publish correctly via `vendor:publish --tag=modules-chat-migrations`. The previous bare-name `hasMigrations()` list pointed at non-existent source paths (real files are timestamp-prefixed). Switched to `discoversMigrations()`.

## [2.1.0] - 2026-05-28

### Added

- `MessageType` enum with `User` + `System` cases.
- `chat_messages.type` and `chat_messages.data` columns.
- `chat_conversations.data` JSON column for arbitrary metadata.
- `chat_participants.archived_at` and `chat_participants.settings` columns.
- `Conversation::systemMessage(body, data)` for first-class system events.
- `Conversation::messagesCursor()` for cursor-based pagination.
- `Participant::archive()/unarchive()/isArchived()` + `archived()/notArchived()` scopes.
- `chat_message_flags` table + `Message::flag(user)/unflag(user)/isFlaggedBy(user)`.
- `ChatParticipant` contract + `IsChatParticipant` trait with relations and unread count helper.
- Optional `body` encryption via `chat.encrypt_messages` config + `CHAT_ENCRYPT_MESSAGES` env.
- Auto-unarchive recipient participants on new message via `chat.auto_unarchive_on_new_message`.

### Inspirations

- musonza/chat (notification table pattern, archived state, data columns).
- cmgmyr/laravel-messenger (participant helper API on User model).

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
