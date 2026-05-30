<?php

declare(strict_types=1);

use Kurt\Modules\Chat\Filament\V3\Resources\PresenceResource;
use Kurt\Modules\Chat\Filament\V3\Resources\PresenceResource\Pages\ListPresences;
use Kurt\Modules\Chat\Models\Presence;
use Kurt\Modules\Core\Support\FilamentVersion;

beforeEach(function () {
    if (FilamentVersion::major() !== 3) {
        $this->markTestSkipped('Filament V3 is not installed.');
    }
});

it('targets the Presence model and registers a single read-only list page', function () {
    expect(PresenceResource::getModel())->toBe(Presence::class)
        ->and(array_keys(PresenceResource::getPages()))->toBe(['index']);
});

it('is read-only: no create, edit or delete', function () {
    expect(PresenceResource::canCreate())->toBeFalse();

    $presence = new Presence;

    expect(PresenceResource::canEdit($presence))->toBeFalse()
        ->and(PresenceResource::canDelete($presence))->toBeFalse();
});

it('builds a table with user, status, status_message and heartbeat columns', function () {
    expect(tableColumnNames(PresenceResource::class, ListPresences::class))
        ->toContain('user.name', 'status', 'status_message', 'heartbeat_at');

    expect(tableFilterNames(PresenceResource::class, ListPresences::class))
        ->toContain('status');
});
