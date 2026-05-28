<?php

declare(strict_types=1);

use Kurt\Modules\Chat\Enums\ConversationType;

it('has expected cases and values', function () {
    expect(ConversationType::Room->value)->toBe('room');
    expect(ConversationType::Direct->value)->toBe('direct');
});
