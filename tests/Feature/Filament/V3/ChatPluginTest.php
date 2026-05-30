<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use Kurt\Modules\Chat\Filament\ChatPlugin;
use Kurt\Modules\Chat\Filament\V3\Resources\ConversationResource;
use Kurt\Modules\Chat\Filament\V3\Resources\MessageResource;
use Kurt\Modules\Chat\Filament\V3\Resources\PresenceResource;
use Kurt\Modules\Core\Support\FilamentVersion;

beforeEach(function () {
    if (FilamentVersion::major() !== 3) {
        $this->markTestSkipped('Filament V3 is not installed.');
    }
});

it('dispatches the facade to the v3 plugin', function () {
    expect(ChatPlugin::make())->toBeInstanceOf(Kurt\Modules\Chat\Filament\V3\ChatPlugin::class)
        ->and(ChatPlugin::make()->getId())->toBe('kurtmodules-chat');
});

it('registers all three chat resources on the panel', function () {
    $resources = Filament::getPanel('admin')->getResources();

    expect($resources)
        ->toContain(ConversationResource::class)
        ->toContain(MessageResource::class)
        ->toContain(PresenceResource::class);
});

it('registers routes for every resource', function () {
    $uris = collect(app('router')->getRoutes()->getRoutes())
        ->map(fn ($route) => $route->uri())
        ->all();

    expect($uris)
        ->toContain('admin/conversations', 'admin/conversations/create', 'admin/conversations/{record}/edit')
        ->toContain('admin/messages', 'admin/messages/{record}/edit')
        ->toContain('admin/presences');
});
