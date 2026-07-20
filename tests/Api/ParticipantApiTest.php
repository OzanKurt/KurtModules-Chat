<?php

declare(strict_types=1);

use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Enums\ParticipantRole;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Tests\Stubs\StubUser;

beforeEach(function (): void {
    $this->owner = StubUser::create(['email' => 'owner@example.com', 'name' => 'Owner']);
    $this->member = StubUser::create(['email' => 'member@example.com', 'name' => 'Member']);
    $this->newbie = StubUser::create(['email' => 'newbie@example.com', 'name' => 'Newbie']);

    $this->room = Conversation::query()->create([
        'type' => ConversationType::Room,
        'name' => 'general',
        'created_by' => $this->owner->id,
    ]);
    $this->room->participants()->create([
        'user_id' => $this->owner->id,
        'role' => ParticipantRole::Owner->value,
        'joined_at' => now(),
        'notifications' => 'all',
    ]);
    $this->room->participants()->create([
        'user_id' => $this->member->id,
        'role' => ParticipantRole::Member->value,
        'joined_at' => now(),
        'notifications' => 'all',
    ]);
});

it('lists participants for a participant', function (): void {
    $this->actingAs($this->member)
        ->getJson("/api/chat/conversations/{$this->room->id}/participants")
        ->assertOk()
        ->assertJsonPath('meta.pagination.total', 2);
});

it('forbids a non-participant from listing participants', function (): void {
    $this->actingAs($this->newbie)
        ->getJson("/api/chat/conversations/{$this->room->id}/participants")
        ->assertForbidden();
});

it('lets an owner add a participant', function (): void {
    $this->actingAs($this->owner)
        ->postJson("/api/chat/conversations/{$this->room->id}/participants", [
            'user_id' => $this->newbie->id,
        ])
        ->assertCreated()
        ->assertJsonPath('data.user_id', $this->newbie->id);

    expect($this->room->participants()->where('user_id', $this->newbie->id)->exists())->toBeTrue();
});

it('forbids a plain member from adding a participant', function (): void {
    $this->actingAs($this->member)
        ->postJson("/api/chat/conversations/{$this->room->id}/participants", [
            'user_id' => $this->newbie->id,
        ])
        ->assertForbidden();
});

it('lets an owner remove a participant', function (): void {
    $this->actingAs($this->owner)
        ->deleteJson("/api/chat/conversations/{$this->room->id}/participants/{$this->member->id}")
        ->assertNoContent();

    expect($this->room->participants()->where('user_id', $this->member->id)->exists())->toBeFalse();
});
