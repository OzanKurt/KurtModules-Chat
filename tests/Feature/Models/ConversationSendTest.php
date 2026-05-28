<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Events\MessageSent;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Tests\Stubs\StubUser;

beforeEach(function (): void {
    $this->alice = StubUser::create(['email' => 'alice@example.com']);
    $this->bob = StubUser::create(['email' => 'bob@example.com']);
});

it('creates a message and updates last_message_at', function () {
    $room = Conversation::query()->create([
        'type' => ConversationType::Room,
        'name' => 'general',
        'created_by' => $this->alice->id,
    ]);
    $room->participants()->create([
        'user_id' => $this->alice->id,
        'role' => 'member',
        'joined_at' => now(),
        'notifications' => 'all',
    ]);

    expect($room->last_message_at)->toBeNull();

    $message = $room->send($this->alice, 'hello world');

    expect($message->exists)->toBeTrue();
    expect($message->body)->toBe('hello world');
    expect($message->user_id)->toBe($this->alice->id);
    expect($room->fresh()->last_message_at)->not->toBeNull();
});

it('dispatches MessageSent', function () {
    Event::fake([MessageSent::class]);

    $room = Conversation::query()->create([
        'type' => ConversationType::Room,
        'name' => 'general',
        'created_by' => $this->alice->id,
    ]);
    $room->participants()->create([
        'user_id' => $this->alice->id,
        'role' => 'member',
        'joined_at' => now(),
        'notifications' => 'all',
    ]);

    $message = $room->send($this->alice, 'hi');

    Event::assertDispatched(MessageSent::class, fn (MessageSent $event) => $event->message->id === $message->id);
});
