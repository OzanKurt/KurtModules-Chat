<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Enums\ParticipantRole;
use Kurt\Modules\Chat\Events\MessageSent;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Models\Message;
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

it('blocks guests from message history with 401', function (): void {
    $this->getJson("/api/chat/conversations/{$this->room->id}/messages")->assertUnauthorized();
});

it('returns paginated newest-first history for a participant', function (): void {
    foreach (range(1, 5) as $i) {
        $this->room->send($this->alice, "message {$i}");
    }

    $response = $this->actingAs($this->bob)
        ->getJson("/api/chat/conversations/{$this->room->id}/messages?per_page=3")
        ->assertOk()
        ->assertJsonPath('meta.pagination.total', 5)
        ->assertJsonPath('meta.pagination.per_page', 3);

    // Newest-first: the first item is the last message sent.
    expect($response->json('data.0.body'))->toBe('message 5');
    expect($response->json('data'))->toHaveCount(3);
});

it('forbids a non-participant from reading history', function (): void {
    $this->actingAs($this->carol)
        ->getJson("/api/chat/conversations/{$this->room->id}/messages")
        ->assertForbidden();
});

it('sends a message through the domain service and dispatches MessageSent', function (): void {
    Event::fake([MessageSent::class]);

    $response = $this->actingAs($this->bob)
        ->postJson("/api/chat/conversations/{$this->room->id}/messages", ['body' => 'hi there'])
        ->assertCreated()
        ->assertJsonPath('data.body', 'hi there')
        ->assertJsonPath('data.user_id', $this->bob->id);

    $messageId = $response->json('data.id');

    Event::assertDispatched(MessageSent::class, fn (MessageSent $event) => $event->message->id === $messageId);
});

it('forbids a non-participant from sending a message', function (): void {
    $this->actingAs($this->carol)
        ->postJson("/api/chat/conversations/{$this->room->id}/messages", ['body' => 'intruder'])
        ->assertForbidden();
});

it('edits an own message within the edit window', function (): void {
    $message = $this->room->send($this->bob, 'typo');

    $this->actingAs($this->bob)
        ->patchJson("/api/chat/messages/{$message->id}", ['body' => 'fixed'])
        ->assertOk()
        ->assertJsonPath('data.body', 'fixed');

    expect($message->fresh()->edited_at)->not->toBeNull();
});

it('forbids editing another user\'s message', function (): void {
    $message = $this->room->send($this->alice, 'hers');

    $this->actingAs($this->bob)
        ->patchJson("/api/chat/messages/{$message->id}", ['body' => 'hijack'])
        ->assertForbidden();
});

it('deletes an own message', function (): void {
    $message = $this->room->send($this->bob, 'delete me');

    $this->actingAs($this->bob)
        ->deleteJson("/api/chat/messages/{$message->id}")
        ->assertNoContent();

    // Messages are soft-deleted: gone from default queries, still trashed.
    expect(Message::query()->whereKey($message->id)->exists())->toBeFalse();
    expect($message->fresh()->trashed())->toBeTrue();
});
