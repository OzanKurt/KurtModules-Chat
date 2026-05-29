<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Enums\MessageType;
use Kurt\Modules\Chat\Events\MessageSent;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Tests\Stubs\StubUser;

beforeEach(function (): void {
    $this->alice = StubUser::create(['email' => 'alice@example.com']);
    $this->room = Conversation::query()->create([
        'type' => ConversationType::Room,
        'name' => 'general',
        'created_by' => $this->alice->id,
    ]);
});

it('creates a system message with no author and System type', function () {
    $message = $this->room->systemMessage('alice joined the room', ['event' => 'joined']);

    expect($message->user_id)->toBeNull();
    expect($message->type)->toBe(MessageType::System);
    expect($message->body)->toBe('alice joined the room');
    expect($message->data)->toBe(['event' => 'joined']);
    expect($message->isSystem())->toBeTrue();
});

it('bumps last_message_at and dispatches MessageSent for system messages', function () {
    Event::fake([MessageSent::class]);

    $message = $this->room->systemMessage('topic changed');

    expect($this->room->fresh()->last_message_at)->not->toBeNull();
    Event::assertDispatched(MessageSent::class, fn (MessageSent $event) => $event->message->id === $message->id);
});
