<?php

declare(strict_types=1);

use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Tests\Stubs\StubUser;

beforeEach(function (): void {
    $this->alice = StubUser::create(['email' => 'alice@example.com']);
    $this->bob = StubUser::create(['email' => 'bob@example.com']);

    $this->room = Conversation::query()->create([
        'type' => ConversationType::Room,
        'name' => 'general',
        'created_by' => $this->alice->id,
    ]);
    $this->room->participants()->create([
        'user_id' => $this->alice->id,
        'role' => 'member',
        'joined_at' => now(),
        'notifications' => 'all',
    ]);
});

it('authorizes a participant on the conversation broadcast channels', function () {
    // hasParticipant() is the authorization primitive behind the private
    // chat.room/chat.dm channels and the presence chat.conversation channel.
    expect($this->room->hasParticipant($this->alice))->toBeTrue();
});

it('rejects a non-participant on the conversation broadcast channels', function () {
    expect($this->room->hasParticipant($this->bob))->toBeFalse();
});
