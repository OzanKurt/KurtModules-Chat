<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Enums\ConversationVisibility;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Support\ConversationKey;
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

it('returns the winning row when a concurrent insert wins the dm_key race', function () {
    $key = ConversationKey::forDirect($this->alice, $this->bob);

    // Simulate the race: right after directBetween's initial existence check
    // (which finds nothing) and before its own INSERT, a concurrent caller
    // persists the same dm_key. The listener fires on that SELECT, at the top
    // level (outside directBetween's transaction), so the winner survives the
    // duplicate-key rollback and the unique-violation catch path returns it.
    $insertedWinner = false;
    DB::listen(function ($query) use ($key, &$insertedWinner): void {
        if ($insertedWinner) {
            return;
        }
        if (! str_contains($query->sql, 'select') || ! str_contains($query->sql, 'dm_key')) {
            return;
        }
        $insertedWinner = true;
        Conversation::withoutEvents(function () use ($key): void {
            Conversation::query()->create([
                'type' => ConversationType::Direct,
                'dm_key' => $key,
                'created_by' => $this->alice->id,
                'visibility' => ConversationVisibility::Private,
            ]);
        });
    });

    $convo = Conversation::directBetween($this->alice, $this->bob);

    expect($insertedWinner)->toBeTrue();
    expect($convo->dm_key)->toBe($key);
    expect(Conversation::query()->where('dm_key', $key)->count())->toBe(1);
});

it('sets both participants on a new direct conversation', function () {
    $dm = Conversation::directBetween($this->alice, $this->bob);

    expect($dm->type)->toBe(ConversationType::Direct);
    expect($dm->visibility)->toBe(ConversationVisibility::Private);
    $participantIds = $dm->participants()->pluck('user_id')->all();
    expect($participantIds)->toContain($this->alice->id);
    expect($participantIds)->toContain($this->bob->id);
});
