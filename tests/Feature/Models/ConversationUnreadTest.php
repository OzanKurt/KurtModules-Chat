<?php

declare(strict_types=1);

use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Enums\ParticipantNotifications;
use Kurt\Modules\Chat\Enums\ParticipantRole;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Tests\Stubs\StubUser;

beforeEach(function (): void {
    $this->alice = StubUser::create(['email' => 'alice@example.com']);
    $this->bob = StubUser::create(['email' => 'bob@example.com']);

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

it('does not inflate unread count with the reader own messages', function () {
    // Alice sends two messages; they must never count as unread for herself.
    $this->room->send($this->alice, 'hello');
    $this->room->send($this->alice, 'anyone here?');

    expect($this->room->unreadCountFor($this->alice))->toBe(0);

    // Bob, on the other hand, has two unread from Alice.
    expect($this->room->unreadCountFor($this->bob))->toBe(2);
});

it('excludes author-less system messages from the unread count', function () {
    $this->room->systemMessage('alice changed the topic');

    expect($this->room->unreadCountFor($this->bob))->toBe(0);
});

it('returns zero for a non-participant', function () {
    $carol = StubUser::create(['email' => 'carol@example.com']);
    $this->room->send($this->alice, 'hi');

    expect($this->room->unreadCountFor($carol))->toBe(0);
});
