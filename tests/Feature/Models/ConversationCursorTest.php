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
