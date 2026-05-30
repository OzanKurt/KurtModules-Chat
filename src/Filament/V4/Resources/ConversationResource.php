<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Filament\V4\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Kurt\Modules\Chat\Enums\ConversationType;
use Kurt\Modules\Chat\Enums\ConversationVisibility;
use Kurt\Modules\Chat\Filament\V4\Resources\ConversationResource\Pages;
use Kurt\Modules\Chat\Models\Conversation;

class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('type')
                            ->options(ConversationType::class)
                            ->default(ConversationType::Room)
                            ->required(),
                        Select::make('visibility')
                            ->options(ConversationVisibility::class)
                            ->default(ConversationVisibility::Public)
                            ->required(),
                        TextInput::make('name')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (ConversationType $state): string => match ($state) {
                        ConversationType::Room => 'info',
                        ConversationType::Direct => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('visibility')
                    ->badge()
                    ->color(fn (ConversationVisibility $state): string => match ($state) {
                        ConversationVisibility::Public => 'success',
                        ConversationVisibility::Unlisted => 'warning',
                        ConversationVisibility::Private => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('participants_count')
                    ->counts('participants')
                    ->label('Participants'),
                TextColumn::make('last_message_at')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('last_message_at', 'desc')
            ->filters([
                SelectFilter::make('type')
                    ->options(ConversationType::class),
                SelectFilter::make('visibility')
                    ->options(ConversationVisibility::class),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * @return array<class-string, mixed>
     */
    public static function getRelations(): array
    {
        return [];
    }

    /**
     * @return array<string, PageRegistration>
     */
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListConversations::route('/'),
            'create' => Pages\CreateConversation::route('/create'),
            'edit' => Pages\EditConversation::route('/{record}/edit'),
        ];
    }
}
