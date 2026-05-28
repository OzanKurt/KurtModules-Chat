<?php

declare(strict_types=1);

use Kurt\Modules\Chat\Enums\ConversationType;
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
