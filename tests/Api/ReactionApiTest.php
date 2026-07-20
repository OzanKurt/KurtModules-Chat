<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Enums\ParticipantRole;
use Kurt\Modules\Chat\Events\ReactionAdded;
use Kurt\Modules\Chat\Events\ReactionRemoved;
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

    $this->message = $this->room->send($this->alice, 'react to me');
});

it('adds a reaction and dispatches ReactionAdded', function (): void {
    Event::fake([ReactionAdded::class]);

    $this->actingAs($this->bob)
        ->postJson("/api/chat/messages/{$this->message->id}/reactions", ['emoji' => '🎉'])
        ->assertCreated()
        ->assertJsonPath('data.emoji', '🎉')
        ->assertJsonPath('data.user_id', $this->bob->id);

    expect($this->message->reactions()->where('user_id', $this->bob->id)->where('emoji', '🎉')->exists())->toBeTrue();
    Event::assertDispatched(ReactionAdded::class);
});

it('removes a reaction and dispatches ReactionRemoved', function (): void {
    $this->message->reactWith($this->bob, '🎉');

    Event::fake([ReactionRemoved::class]);

    $this->actingAs($this->bob)
        ->deleteJson("/api/chat/messages/{$this->message->id}/reactions", ['emoji' => '🎉'])
        ->assertNoContent();

    expect($this->message->reactions()->where('user_id', $this->bob->id)->exists())->toBeFalse();
    Event::assertDispatched(ReactionRemoved::class);
});

it('forbids a non-participant from reacting', function (): void {
    $this->actingAs($this->carol)
        ->postJson("/api/chat/messages/{$this->message->id}/reactions", ['emoji' => '👍'])
        ->assertForbidden();
});

it('blocks guests from reacting with 401', function (): void {
    $this->postJson("/api/chat/messages/{$this->message->id}/reactions", ['emoji' => '👍'])
        ->assertUnauthorized();
});
