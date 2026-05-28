<?php

declare(strict_types=1);

use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Enums\ConversationVisibility;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Tests\Stubs\StubUser;

beforeEach(function (): void {
    $this->alice = StubUser::create(['email' => 'alice@example.com']);
    $this->bob = StubUser::create(['email' => 'bob@example.com']);
});

it('returns the same conversation regardless of argument order', function () {
    $ab = Conversation::directBetween($this->alice, $this->bob);
    $ba = Conversation::directBetween($this->bob, $this->alice);

    expect($ab->id)->toBe($ba->id);
});

it('is idempotent — two calls with the same pair return the same row', function () {
    $first = Conversation::directBetween($this->alice, $this->bob);
    $second = Conversation::directBetween($this->alice, $this->bob);

    expect($first->id)->toBe($second->id);
    expect(Conversation::query()->where('dm_key', $first->dm_key)->count())->toBe(1);
});

it('sets both participants on a new direct conversation', function () {
    $dm = Conversation::directBetween($this->alice, $this->bob);

    expect($dm->type)->toBe(ConversationType::Direct);
    expect($dm->visibility)->toBe(ConversationVisibility::Private);
    $participantIds = $dm->participants()->pluck('user_id')->all();
    expect($participantIds)->toContain($this->alice->id);
    expect($participantIds)->toContain($this->bob->id);
});
