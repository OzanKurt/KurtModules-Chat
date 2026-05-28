<?php

declare(strict_types=1);

use Kurt\Modules\Chat\Enums\ConversationVisibility;

it('has expected cases and values', function () {
    expect(ConversationVisibility::Public->value)->toBe('public');
    expect(ConversationVisibility::Unlisted->value)->toBe('unlisted');
    expect(ConversationVisibility::Private->value)->toBe('private');
});
