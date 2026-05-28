<?php

declare(strict_types=1);

use Kurt\Modules\Chat\Enums\ParticipantNotifications;

it('has expected cases and values', function () {
    expect(ParticipantNotifications::All->value)->toBe('all');
    expect(ParticipantNotifications::Mentions->value)->toBe('mentions');
    expect(ParticipantNotifications::None->value)->toBe('none');
});
