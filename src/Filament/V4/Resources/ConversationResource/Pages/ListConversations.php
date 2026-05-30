<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Filament\V4\Resources\ConversationResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Kurt\Modules\Chat\Filament\V4\Resources\ConversationResource;

class ListConversations extends ListRecords
{
    protected static string $resource = ConversationResource::class;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
