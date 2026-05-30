<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Tests\Fixtures;

use Filament\Panel;
use Filament\PanelProvider;
use Kurt\Modules\Chat\Filament\ChatPlugin;

/**
 * Minimal Filament panel used by the resource smoke tests. It registers the
 * version-dispatching Chat plugin so the correct V{n} resource set is wired
 * up for whichever Filament major is installed in the current CI matrix cell.
 */
final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->default()
            ->plugin(ChatPlugin::make());
    }
}
