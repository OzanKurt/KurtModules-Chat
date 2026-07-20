<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Enums\ParticipantNotifications;
use Kurt\Modules\Chat\Enums\ParticipantRole;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Tests\Stubs\ChatParticipantUser;

beforeEach(function (): void {
    $this->alice = ChatParticipantUser::create([
        'email' => 'alice@example.com',
        'name' => 'Alice',
    ]);
    $this->bob = ChatParticipantUser::create([
        'email' => 'bob@example.com',
        'name' => 'Bob',
    ]);

    $this->room = Conversation::query()->create([
        'type' => ConversationType::Room,
        'name' => 'general',
        'created_by' => $this->alice->id,
    ]);
    foreach ([$this->alice, $this->bob] as $user) {
        $this->room->participants()->create([
            'user_id' => $user->id,
            'role' => ParticipantRole::Member->value,
            'joined_at' => now(),
            'notifications' => ParticipantNotifications::All->value,
        ]);
    }
});

it('exposes chatParticipants and chatConversations relations', function () {
    expect($this->alice->chatParticipants()->count())->toBe(1);
    expect($this->alice->chatConversations()->count())->toBe(1);
    expect($this->alice->chatConversations()->first()->id)->toBe($this->room->id);
});

it('counts unread messages excluding the user own messages', function () {
    expect($this->alice->unreadChatMessagesCount())->toBe(0);

    $this->room->send($this->bob, 'hi alice');
    $this->room->send($this->bob, 'still there?');

    // Alice should see 2 unread from Bob, Bob should see 0 (his own messages).
    expect($this->alice->unreadChatMessagesCount())->toBe(2);
    expect($this->bob->unreadChatMessagesCount())->toBe(0);
});

it('counts unread across many conversations with a bounded number of queries', function () {
    // Fan Alice out across several conversations, each with unread messages
    // from another author. The count must not scale a COUNT query per
    // conversation (the previous N+1); a single aggregate should suffice.
    for ($i = 0; $i < 5; $i++) {
        $room = Conversation::query()->create([
            'type' => ConversationType::Room,
            'name' => "room {$i}",
            'created_by' => $this->alice->id,
        ]);
        foreach ([$this->alice, $this->bob] as $user) {
            $room->participants()->create([
                'user_id' => $user->id,
                'role' => ParticipantRole::Member->value,
                'joined_at' => now(),
                'notifications' => ParticipantNotifications::All->value,
            ]);
        }
        $room->messages()->create(['user_id' => $this->bob->id, 'body' => "hi {$i}"]);
    }

    DB::enableQueryLog();
    $count = $this->alice->unreadChatMessagesCount();
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // 5 conversations, each with one unread message from Bob.
    expect($count)->toBe(5);
    // A single aggregate query regardless of conversation count.
    expect($queries)->toHaveCount(1);
});

it('excludes author-less system messages from the unread fan-out', function () {
    $this->room->systemMessage('topic changed');

    expect($this->alice->unreadChatMessagesCount())->toBe(0);
    expect($this->bob->unreadChatMessagesCount())->toBe(0);
});

it('respects last_read_at when counting unread', function () {
    $this->room->send($this->bob, 'hi 1');

    // Travel forward so markRead lands strictly between the two messages.
    $this->travel(2)->seconds();
    $this->room->markRead($this->alice);
    $this->travel(2)->seconds();
    $this->room->send($this->bob, 'hi 2');

    expect($this->alice->unreadChatMessagesCount())->toBe(1);
});

it('returns a display name with fallback to email', function () {
    expect($this->alice->getChatDisplayName())->toBe('Alice');

    $noNameUser = ChatParticipantUser::create([
        'email' => 'noname@example.com',
        'name' => null,
    ]);
    expect($noNameUser->getChatDisplayName())->toBe('noname@example.com');
});

it('returns null avatar when none configured', function () {
    expect($this->alice->getChatAvatarUrl())->toBeNull();
});
