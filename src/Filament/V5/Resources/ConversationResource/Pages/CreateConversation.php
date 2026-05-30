<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Filament\V5\Resources\ConversationResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kurt\Modules\Chat\Filament\V5\Resources\ConversationResource;

class CreateConversation extends CreateRecord
{
    protected static string $resource = ConversationResource::class;
}
