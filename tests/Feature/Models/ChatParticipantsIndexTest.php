<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;

it('has a standalone user_id index on chat_participants', function () {
    $indexes = collect(Schema::getIndexes('chat_participants'));

    $hasStandaloneUserIdIndex = $indexes->contains(
        fn (array $index): bool => $index['columns'] === ['user_id'],
    );

    expect($hasStandaloneUserIdIndex)->toBeTrue(
        'expected a single-column user_id index on chat_participants',
    );
});
