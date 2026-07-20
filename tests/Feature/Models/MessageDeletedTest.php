<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Events\MessageDeleted;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Models\Message;
use Kurt\Modules\Chat\Tests\Stubs\StubUser;

beforeEach(function (): void {
    $this->alice = StubUser::create(['email' => 'alice@example.com']);
});

it('broadcasts MessageDeleted on the chat.dm channel for direct conversations', function () {
    $dm = Conversation::query()->create([
        'type' => ConversationType::Direct,
        'dm_key' => 'dm-key-deleted',
        'created_by' => $this->alice->id,
    ]);
    /** @var Message $message */
    $message = $dm->messages()->create([
        'user_id' => $this->alice->id,
        'body' => 'delete me',
    ]);

    Event::fake([MessageDeleted::class]);

    $message->delete();

    Event::assertDispatched(MessageDeleted::class, function (MessageDeleted $event) use ($dm, $message): bool {
        $channels = array_map(
            static fn ($channel): string => $channel->name,
            $event->broadcastOn(),
        );

        return $event->messageId === (int) $message->getKey()
            && $event->conversationId === (int) $dm->id
            && $channels === ['private-chat.dm.'.$dm->id];
    });
});

it('broadcasts MessageDeleted on the chat.room channel for room conversations', function () {
    $room = Conversation::query()->create([
        'type' => ConversationType::Room,
        'name' => 'general',
        'created_by' => $this->alice->id,
    ]);
    /** @var Message $message */
    $message = $room->messages()->create([
        'user_id' => $this->alice->id,
        'body' => 'delete me',
    ]);

    Event::fake([MessageDeleted::class]);

    $message->delete();

    Event::assertDispatched(MessageDeleted::class, function (MessageDeleted $event) use ($room): bool {
        $channels = array_map(
            static fn ($channel): string => $channel->name,
            $event->broadcastOn(),
        );

        return $channels === ['private-chat.room.'.$room->id];
    });
});
