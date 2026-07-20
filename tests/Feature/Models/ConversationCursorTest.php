<?php

declare(strict_types=1);

use Illuminate\Contracts\Pagination\CursorPaginator;
use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Chat\Tests\Stubs\StubUser;

it('returns a cursor paginator for messages', function () {
    $alice = StubUser::create(['email' => 'alice@example.com']);
    $room = Conversation::query()->create([
        'type' => ConversationType::Room,
        'name' => 'general',
        'created_by' => $alice->id,
    ]);

    for ($i = 0; $i < 12; $i++) {
        $room->messages()->create([
            'user_id' => $alice->id,
            'body' => "msg {$i}",
        ]);
    }

    $page = $room->messagesCursor(5);

    expect($page)->toBeInstanceOf(CursorPaginator::class);
    expect($page->count())->toBe(5);
    expect($page->hasMorePages())->toBeTrue();
});

it('orders by id as a unique tiebreaker so same-timestamp rows paginate stably', function () {
    $alice = StubUser::create(['email' => 'tiebreak@example.com']);
    $room = Conversation::query()->create([
        'type' => ConversationType::Room,
        'name' => 'general',
        'created_by' => $alice->id,
    ]);

    // Freeze time so every message shares an identical created_at; without an
    // id tiebreaker the cursor order would be undefined and pages could repeat
    // or skip rows.
    $ids = [];
    $this->freezeTime(function () use ($room, $alice, &$ids): void {
        for ($i = 0; $i < 6; $i++) {
            $ids[] = $room->messages()->create([
                'user_id' => $alice->id,
                'body' => "msg {$i}",
            ])->id;
        }
    });

    $first = $room->messagesCursor(3);
    // Page 2 reproduces messagesCursor's ordering and follows page 1's cursor.
    $second = $room->messages()
        ->latest('created_at')
        ->orderBy('id', 'desc')
        ->cursorPaginate(3, ['*'], 'cursor', $first->nextCursor());

    $firstIds = collect($first->items())->pluck('id')->all();
    $secondIds = collect($second->items())->pluck('id')->all();

    // Newest-first by id: [5,4,3] then [2,1,0] (using positions in $ids).
    expect($firstIds)->toBe([$ids[5], $ids[4], $ids[3]]);
    expect($secondIds)->toBe([$ids[2], $ids[1], $ids[0]]);
    // No overlap between the two pages proves the ordering is stable.
    expect(array_intersect($firstIds, $secondIds))->toBe([]);
});
