<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Models\Message;
use Kurt\Modules\Chat\Tests\Stubs\StubUser;

beforeEach(function (): void {
    $this->alice = StubUser::create(['email' => 'alice@example.com']);
    $this->bob = StubUser::create(['email' => 'bob@example.com']);

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

    /** @var Message $message */
    $message = $room->messages()->create([
        'user_id' => $this->alice->id,
        'body' => 'hi',
    ]);
    $this->message = $message;
});

it('allows the author to edit within the configured window', function () {
    config()->set('chat.edit_window_minutes', 15);

    expect(Gate::forUser($this->alice)->allows('update', $this->message))->toBeTrue();
});

it('blocks editing outside the window', function () {
    config()->set('chat.edit_window_minutes', 15);

    // Travel forward in time so the message is "old".
    Carbon::setTestNow(now()->addMinutes(60));

    /** @var Message $stale */
    $stale = $this->message->fresh();

    expect(Gate::forUser($this->alice)->allows('update', $stale))->toBeFalse();

    Carbon::setTestNow();
});

it('blocks non-author users from editing', function () {
    config()->set('chat.edit_window_minutes', 15);

    expect(Gate::forUser($this->bob)->allows('update', $this->message))->toBeFalse();
});
