<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Events\UserStartedTyping;
use Kurt\Modules\Chat\Events\UserStoppedTyping;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Tests\Stubs\StubUser;

beforeEach(function (): void {
    $this->alice = StubUser::create(['email' => 'alice@example.com']);

    /** @var Conversation $conversation */
    $conversation = Conversation::query()->create([
        'type' => ConversationType::Room,
        'name' => 'general',
        'created_by' => $this->alice->id,
    ]);
    $this->conversation = $conversation;
});

it('broadcasts UserStartedTyping when a user starts typing', function () {
    Event::fake([UserStartedTyping::class]);

    $this->conversation->startTyping($this->alice);

    Event::assertDispatched(UserStartedTyping::class, function (UserStartedTyping $event): bool {
        return $event->conversationId === (int) $this->conversation->getKey()
            && $event->user->is($this->alice);
    });
});

it('broadcasts UserStoppedTyping when a user stops typing', function () {
    Event::fake([UserStoppedTyping::class]);

    $this->conversation->stopTyping($this->alice);

    Event::assertDispatched(UserStoppedTyping::class, function (UserStoppedTyping $event): bool {
        return $event->conversationId === (int) $this->conversation->getKey()
            && $event->user->is($this->alice);
    });
});
