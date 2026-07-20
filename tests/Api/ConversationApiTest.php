<?php

declare(strict_types=1);

use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Enums\ParticipantRole;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Tests\Stubs\StubUser;

beforeEach(function (): void {
    $this->alice = StubUser::create(['email' => 'alice@example.com', 'name' => 'Alice']);
    $this->bob = StubUser::create(['email' => 'bob@example.com', 'name' => 'Bob']);

    $this->room = Conversation::query()->create([
        'type' => ConversationType::Room,
        'name' => 'general',
        'created_by' => $this->alice->id,
    ]);
    $this->room->participants()->create([
        'user_id' => $this->alice->id,
        'role' => ParticipantRole::Owner->value,
        'joined_at' => now(),
        'notifications' => 'all',
    ]);
});

it('blocks guests with 401', function (): void {
    $this->getJson('/api/chat/conversations')->assertUnauthorized();
});

it('lists only the authenticated user\'s conversations', function (): void {
    // A conversation Alice is not part of must not appear in her list.
    Conversation::query()->create([
        'type' => ConversationType::Room,
        'name' => 'secret',
        'created_by' => $this->bob->id,
    ])->participants()->create([
        'user_id' => $this->bob->id,
        'role' => ParticipantRole::Owner->value,
        'joined_at' => now(),
        'notifications' => 'all',
    ]);

    $response = $this->actingAs($this->alice)
        ->getJson('/api/chat/conversations')
        ->assertOk()
        ->assertJsonPath('meta.pagination.total', 1);

    expect($response->json('data.0.id'))->toBe($this->room->id);
});

it('shows a conversation to a participant', function (): void {
    $this->actingAs($this->alice)
        ->getJson("/api/chat/conversations/{$this->room->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $this->room->id);
});

it('forbids a non-participant from viewing a conversation', function (): void {
    $this->actingAs($this->bob)
        ->getJson("/api/chat/conversations/{$this->room->id}")
        ->assertForbidden();
});

it('creates a room and adds the creator as owner', function (): void {
    $response = $this->actingAs($this->alice)
        ->postJson('/api/chat/conversations', [
            'type' => 'room',
            'name' => 'random',
        ])
        ->assertCreated()
        ->assertJsonPath('data.type', 'room')
        ->assertJsonPath('data.name', 'random');

    $conversation = Conversation::query()->findOrFail($response->json('data.id'));
    expect($conversation->participants()->where('user_id', $this->alice->id)->exists())->toBeTrue();
});

it('creates a direct conversation between two users', function (): void {
    $response = $this->actingAs($this->alice)
        ->postJson('/api/chat/conversations', [
            'type' => 'direct',
            'user_id' => $this->bob->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.type', 'direct');

    $conversation = Conversation::query()->findOrFail($response->json('data.id'));
    expect($conversation->participants()->count())->toBe(2);
});

it('renames a room for an authorized manager', function (): void {
    $this->actingAs($this->alice)
        ->patchJson("/api/chat/conversations/{$this->room->id}", ['name' => 'renamed'])
        ->assertOk()
        ->assertJsonPath('data.name', 'renamed');
});

it('forbids a non-manager from renaming a room', function (): void {
    $this->room->participants()->create([
        'user_id' => $this->bob->id,
        'role' => ParticipantRole::Member->value,
        'joined_at' => now(),
        'notifications' => 'all',
    ]);

    $this->actingAs($this->bob)
        ->patchJson("/api/chat/conversations/{$this->room->id}", ['name' => 'nope'])
        ->assertForbidden();
});

it('lets a participant leave a conversation', function (): void {
    $this->room->participants()->create([
        'user_id' => $this->bob->id,
        'role' => ParticipantRole::Member->value,
        'joined_at' => now(),
        'notifications' => 'all',
    ]);

    $this->actingAs($this->bob)
        ->deleteJson("/api/chat/conversations/{$this->room->id}")
        ->assertNoContent();

    expect($this->room->participants()->where('user_id', $this->bob->id)->exists())->toBeFalse();
});
