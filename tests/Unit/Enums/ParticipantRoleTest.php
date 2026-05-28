<?php

declare(strict_types=1);

use Kurt\Modules\Chat\Enums\ParticipantRole;

it('has expected cases and values', function () {
    expect(ParticipantRole::Owner->value)->toBe('owner');
    expect(ParticipantRole::Admin->value)->toBe('admin');
    expect(ParticipantRole::Member->value)->toBe('member');
});
