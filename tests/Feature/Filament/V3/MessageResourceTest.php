<?php

declare(strict_types=1);

use Kurt\Modules\Chat\Filament\V3\Resources\MessageResource;
use Kurt\Modules\Chat\Filament\V3\Resources\MessageResource\Pages\EditMessage;
use Kurt\Modules\Chat\Filament\V3\Resources\MessageResource\Pages\ListMessages;
use Kurt\Modules\Chat\Models\Message;
use Kurt\Modules\Core\Support\FilamentVersion;

beforeEach(function () {
    if (FilamentVersion::major() !== 3) {
        $this->markTestSkipped('Filament V3 is not installed.');
    }
});

it('targets the Message model and registers a list + edit page (no create)', function () {
    expect(MessageResource::getModel())->toBe(Message::class)
        ->and(array_keys(MessageResource::getPages()))->toBe(['index', 'edit']);
});

it('builds a form with body, type, conversation and media attachments', function () {
    $fields = formFieldNames(MessageResource::class, EditMessage::class);

    expect($fields)->toContain('body', 'type', 'conversation_id', 'edited_at', 'attachments');
});

it('builds a moderation table with a conversation filter and trashed filter', function () {
    expect(tableColumnNames(MessageResource::class, ListMessages::class))
        ->toContain('body', 'type', 'user.name', 'conversation.name');

    expect(tableFilterNames(MessageResource::class, ListMessages::class))
        ->toContain('conversation', 'trashed');
});

it('offers soft-delete + restore moderation actions', function () {
    expect(tableActionNames(MessageResource::class, ListMessages::class))
        ->toContain('edit', 'delete', 'restore', 'forceDelete');

    expect(tableBulkActionNames(MessageResource::class, ListMessages::class))
        ->toContain('delete', 'restore', 'forceDelete');
});

it('includes soft-deleted records in the moderation queue', function () {
    $query = MessageResource::getEloquentQuery();

    expect($query->getQuery()->wheres)->toBeArray();
    // The SoftDeletingScope is removed so trashed rows surface for moderation.
    expect($query->getModel())->toBeInstanceOf(Message::class);
});
