<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Filament\V5;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Kurt\Modules\Chat\Filament\V5\Resources\ConversationResource;
use Kurt\Modules\Chat\Filament\V5\Resources\MessageResource;
use Kurt\Modules\Chat\Filament\V5\Resources\PresenceResource;

final class ChatPlugin implements Plugin
{
    public function getId(): string
    {
        return 'kurtmodules-chat';
    }

    public function register(Panel $panel): void
    {
        $panel->resources([
            ConversationResource::class,
            MessageResource::class,
            PresenceResource::class,
        ]);
    }

    public function boot(Panel $panel): void {}

    public static function make(): static
    {
        /** @var static */
        return app(self::class);
    }
}
