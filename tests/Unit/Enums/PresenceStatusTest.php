<?php

declare(strict_types=1);

use Kurt\Modules\Chat\Enums\PresenceStatus;

it('has expected cases and values', function () {
    expect(PresenceStatus::Online->value)->toBe('online');
    expect(PresenceStatus::Away->value)->toBe('away');
    expect(PresenceStatus::Dnd->value)->toBe('dnd');
    expect(PresenceStatus::Offline->value)->toBe('offline');
});
