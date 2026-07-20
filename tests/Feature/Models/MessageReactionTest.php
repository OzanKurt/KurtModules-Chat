<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Events\ReactionAdded;
use Kurt\Modules\Chat\Events\ReactionRemoved;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Models\Message;
use Kurt\Modules\Chat\Tests\Stubs\StubUser;

beforeEach(function (): void {
    $this->alice = StubUser::create(['email' => 'alice@example.com']);

    $convo = Conversation::query()->create([
        'type' => ConversationType::Room,
        'name' => 'general',
        'created_by' => $this->alice->id,
    ]);
    /** @var Message $message */
    $message = $convo->messages()->create([
        'user_id' => $this->alice->id,
        'body' => 'hi',
    ]);
    $this->message = $message;
});

it('is idempotent: reacting twice with the same emoji returns the same row', function () {
    $first = $this->message->reactWith($this->alice, '👍');
    $second = $this->message->reactWith($this->alice, '👍');

    expect($first->id)->toBe($second->id);
    expect($this->message->reactions()->count())->toBe(1);
});

it('removes the reaction via unreactWith', function () {
    $this->message->reactWith($this->alice, '👍');
    expect($this->message->reactions()->count())->toBe(1);

    $this->message->unreactWith($this->alice, '👍');
    expect($this->message->reactions()->count())->toBe(0);
});

it('keeps independent rows for different emojis from the same user', function () {
    $this->message->reactWith($this->alice, '👍');
    $this->message->reactWith($this->alice, '🎉');

    expect($this->message->reactions()->count())->toBe(2);
});

it('broadcasts ReactionAdded when a reaction is actually added', function () {
    Event::fake([ReactionAdded::class, ReactionRemoved::class]);

    $this->message->reactWith($this->alice, '👍');

    Event::assertDispatched(ReactionAdded::class);
    Event::assertNotDispatched(ReactionRemoved::class);
});

it('does not broadcast ReactionAdded again on an idempotent repeat react', function () {
    Event::fake([ReactionAdded::class]);

    $this->message->reactWith($this->alice, '👍');
    $this->message->reactWith($this->alice, '👍');

    Event::assertDispatchedTimes(ReactionAdded::class, 1);
});

it('broadcasts ReactionRemoved when a reaction is actually removed', function () {
    $this->message->reactWith($this->alice, '👍');

    Event::fake([ReactionAdded::class, ReactionRemoved::class]);

    $this->message->unreactWith($this->alice, '👍');

    Event::assertDispatched(ReactionRemoved::class, function (ReactionRemoved $event): bool {
        return $event->messageId === (int) $this->message->getKey()
            && $event->userId === (int) $this->alice->id
            && $event->emoji === '👍';
    });
    Event::assertNotDispatched(ReactionAdded::class);
});

it('does not broadcast ReactionRemoved when there is nothing to remove', function () {
    Event::fake([ReactionRemoved::class]);

    $this->message->unreactWith($this->alice, '👍');

    Event::assertNotDispatched(ReactionRemoved::class);
});

it('broadcasts ReactionRemoved on the room conversation channel, not chat.message', function () {
    $this->message->reactWith($this->alice, '👍');

    Event::fake([ReactionAdded::class, ReactionRemoved::class]);

    $this->message->unreactWith($this->alice, '👍');

    Event::assertDispatched(ReactionRemoved::class, function (ReactionRemoved $event): bool {
        $channels = array_map(
            static fn ($channel): string => $channel->name,
            $event->broadcastOn(),
        );

        return $channels === ['private-chat.room.'.$this->message->conversation_id];
    });
});

it('broadcasts ReactionRemoved on the chat.dm channel for direct conversations', function () {
    $bob = StubUser::create(['email' => 'bob@example.com']);
    $dm = Conversation::query()->create([
        'type' => ConversationType::Direct,
        'dm_key' => 'dm-key-reaction',
        'created_by' => $this->alice->id,
    ]);
    /** @var Message $message */
    $message = $dm->messages()->create([
        'user_id' => $this->alice->id,
        'body' => 'hi bob',
    ]);
    $message->reactWith($bob, '👍');

    Event::fake([ReactionAdded::class, ReactionRemoved::class]);

    $message->unreactWith($bob, '👍');

    Event::assertDispatched(ReactionRemoved::class, function (ReactionRemoved $event) use ($dm): bool {
        $channels = array_map(
            static fn ($channel): string => $channel->name,
            $event->broadcastOn(),
        );

        return $channels === ['private-chat.dm.'.$dm->id];
    });
});
