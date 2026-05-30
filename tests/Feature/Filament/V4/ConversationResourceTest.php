<?php

declare(strict_types=1);

use Kurt\Modules\Chat\Filament\V4\Resources\ConversationResource;
use Kurt\Modules\Chat\Filament\V4\Resources\ConversationResource\Pages\CreateConversation;
use Kurt\Modules\Chat\Filament\V4\Resources\ConversationResource\Pages\ListConversations;
use Kurt\Modules\Chat\Models\Conversation;
use Kurt\Modules\Core\Support\FilamentVersion;

beforeEach(function () {
    if (FilamentVersion::major() !== 4) {
        $this->markTestSkipped('Filament V4 is not installed.');
    }
});

it('targets the Conversation model and registers its pages', function () {
    expect(ConversationResource::getModel())->toBe(Conversation::class)
        ->and(array_keys(ConversationResource::getPages()))->toBe(['index', 'create', 'edit']);
});

it('builds a form with type/visibility selects and name/description', function () {
    $fields = formFieldNames(ConversationResource::class, CreateConversation::class);

    expect($fields)->toContain('type', 'visibility', 'name', 'description');
});

it('builds a table with type/name/visibility columns and a type filter', function () {
    expect(tableColumnNames(ConversationResource::class, ListConversations::class))
        ->toContain('type', 'name', 'visibility', 'last_message_at');

    expect(tableFilterNames(ConversationResource::class, ListConversations::class))
        ->toContain('type', 'visibility');
});

it('exposes edit, delete and bulk delete actions', function () {
    expect(tableActionNames(ConversationResource::class, ListConversations::class))
        ->toContain('edit', 'delete');

    expect(tableBulkActionNames(ConversationResource::class, ListConversations::class))
        ->toContain('delete');
});
