<?php

declare(strict_types=1);

use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Models\Message;
use Kurt\Modules\Chat\Tests\Stubs\StubUser;

beforeEach(function (): void {
    $this->alice = StubUser::create(['email' => 'alice@example.com']);
    $this->bob = StubUser::create(['email' => 'bob@example.com']);

    $convo = Conversation::query()->create([
        'type' => ConversationType::Room,
        'name' => 'general',
        'created_by' => $this->alice->id,
    ]);
    /** @var Message $message */
    $message = $convo->messages()->create([
        'user_id' => $this->alice->id,
        'body' => 'something controversial',
    ]);
    $this->message = $message;
});

it('is idempotent: flagging twice returns the same row', function () {
    $first = $this->message->flag($this->bob);
    $second = $this->message->flag($this->bob);

    expect($first->id)->toBe($second->id);
    expect($this->message->flags()->count())->toBe(1);
});

it('removes the flag via unflag', function () {
    $this->message->flag($this->bob);
    expect($this->message->flags()->count())->toBe(1);

    $this->message->unflag($this->bob);
    expect($this->message->flags()->count())->toBe(0);
});

it('reports isFlaggedBy correctly', function () {
    expect($this->message->isFlaggedBy($this->bob))->toBeFalse();

    $this->message->flag($this->bob);

    expect($this->message->isFlaggedBy($this->bob))->toBeTrue();
    expect($this->message->isFlaggedBy($this->alice))->toBeFalse();
});
