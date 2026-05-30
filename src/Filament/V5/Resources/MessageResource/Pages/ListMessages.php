<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Filament\V5\Resources\MessageResource\Pages;

use Filament\Resources\Pages\ListRecords;
use Kurt\Modules\Chat\Filament\V5\Resources\MessageResource;

class ListMessages extends ListRecords
{
    protected static string $resource = MessageResource::class;
}
