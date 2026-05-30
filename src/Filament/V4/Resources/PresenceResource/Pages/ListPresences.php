<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Filament\V4\Resources\PresenceResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Kurt\Modules\Chat\Filament\V4\Resources\PresenceResource;

class ListPresences extends ListRecords
{
    protected static string $resource = PresenceResource::class;
}
