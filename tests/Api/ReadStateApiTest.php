<?php

declare(strict_types=1);

use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Enums\ParticipantRole;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Tests\Stubs\StubUser;

beforeEach(function (): void {
    $this->alice = StubUser::create(['email' => 'alice@example.com', 'name' => 'Alice']);
    $this->bob = StubUser::create(['email' => 'bob@example.com', 'name' => 'Bob']);
    $this->carol = StubUser::create(['email' => 'carol@example.com', 'name' => 'Carol']);

    $this->room = Conversation::query()->create([
        'type' => ConversationType::Room,
        'name' => 'general',
        'created_by' => $this->alice->id,
    ]);

    foreach ([$this->alice, $this->bob] as $member) {
        $this->room->participants()->create([
            'user_id' => $member->id,
            'role' => ParticipantRole::Member->value,
            'joined_at' => now(),
            'notifications' => 'all',
        ]);
    }
});

it('reports the unread count for a participant', function (): void {
    $this->room->send($this->alice, 'one');
    $this->room->send($this->alice, 'two');

    $this->actingAs($this->bob)
        ->getJson("/api/chat/conversations/{$this->room->id}/unread-count")
        ->assertOk()
        ->assertJsonPath('data.unread_count', 2);
});

it('marks the conversation read and resets the unread count', function (): void {
    $this->room->send($this->alice, 'one');
    $this->room->send($this->alice, 'two');

    $this->actingAs($this->bob)
        ->postJson("/api/chat/conversations/{$this->room->id}/read")
        ->assertOk()
        ->assertJsonPath('data.unread_count', 0);

    expect($this->room->unreadCountFor($this->bob))->toBe(0);
});

it('forbids a non-participant from reading unread count', function (): void {
    $this->actingAs($this->carol)
        ->getJson("/api/chat/conversations/{$this->room->id}/unread-count")
        ->assertForbidden();
});

it('blocks guests from unread count with 401', function (): void {
    $this->getJson("/api/chat/conversations/{$this->room->id}/unread-count")
        ->assertUnauthorized();
});
