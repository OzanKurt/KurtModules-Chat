<?php

declare(strict_types=1);

use Kurt\Modules\Chat\Enums\MessageType;

it('has expected cases and values', function () {
    expect(MessageType::User->value)->toBe('user');
    expect(MessageType::System->value)->toBe('system');
});
