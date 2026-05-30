<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Filament;

use Filament\Contracts\Plugin;
use Kurt\Modules\Core\Support\FilamentVersion;

/**
 * Version-dispatching facade for the Chat Filament plugin.
 *
 * Register on a panel with `->plugin(\Kurt\Modules\Chat\Filament\ChatPlugin::make())`.
 * The correct V{n} plugin is resolved from the installed Filament major, so the
 * same call works whether the consumer runs Filament 3, 4, or 5.
 */
final class ChatPlugin
{
    public static function make(): Plugin
    {
        return match (FilamentVersion::major()) {
            5 => new V5\ChatPlugin,
            4 => new V4\ChatPlugin,
            3 => new V3\ChatPlugin,
            default => throw new \RuntimeException('Filament is not installed; cannot register the Chat plugin.'),
        };
    }
}
