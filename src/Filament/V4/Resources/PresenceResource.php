<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Filament\V4\Resources;

use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Kurt\Modules\Chat\Enums\PresenceStatus;
use Kurt\Modules\Chat\Filament\V4\Resources\PresenceResource\Pages;
use Kurt\Modules\Chat\Models\Presence;

/**
 * Read-only view of the chat presence table. Presence rows are written by the
 * heartbeat pipeline and pruned by `chat:prune-presence`; the admin only
 * observes them, so the resource exposes no create/edit/delete affordances.
 */
class PresenceResource extends Resource
{
    protected static ?string $model = Presence::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSignal;

    protected static ?string $recordTitleAttribute = 'status_message';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (PresenceStatus $state): string => match ($state) {
                        PresenceStatus::Online => 'success',
                        PresenceStatus::Away => 'warning',
                        PresenceStatus::Dnd => 'danger',
                        PresenceStatus::Offline => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('status_message')
                    ->placeholder('—')
                    ->limit(50)
                    ->toggleable(),
                TextColumn::make('heartbeat_at')
                    ->dateTime()
                    ->since()
                    ->sortable(),
            ])
            ->defaultSort('heartbeat_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options(PresenceStatus::class),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPresences::route('/'),
        ];
    }
}
