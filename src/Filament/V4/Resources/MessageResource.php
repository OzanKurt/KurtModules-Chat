<?php

declare(strict_types=1);

namespace Kurt\Modules\Chat\Filament\V4\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\PageRegistration;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Kurt\Modules\Chat\Enums\MessageType;
use Kurt\Modules\Chat\Filament\V4\Resources\MessageResource\Pages;
use Kurt\Modules\Chat\Models\Message;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedInbox;

    protected static ?string $recordTitleAttribute = 'body';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('conversation_id')
                            ->relationship('conversation', 'name')
                            ->searchable()
                            ->preload()
                            ->label('Conversation'),
                        Select::make('type')
                            ->options(MessageType::class)
                            ->default(MessageType::User)
                            ->required(),
                        Textarea::make('body')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                        DateTimePicker::make('edited_at')
                            ->seconds(false),
                        SpatieMediaLibraryFileUpload::make('attachments')
                            ->collection('chat-attachments')
                            ->multiple()
                            ->visibility('public')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('body')
                    ->limit(60)
                    ->searchable()
                    ->wrap(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn (MessageType $state): string => match ($state) {
                        MessageType::User => 'info',
                        MessageType::System => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Author')
                    ->placeholder('System')
                    ->toggleable(),
                TextColumn::make('conversation.name')
                    ->label('Conversation')
                    ->limit(30)
                    ->placeholder('—')
                    ->toggleable(),
                IconColumn::make('flags_count')
                    ->counts('flags')
                    ->label('Flagged')
                    ->boolean()
                    ->state(fn (Message $record): bool => $record->flags_count > 0),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('deleted_at')
                    ->dateTime()
                    ->label('Deleted')
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('conversation')
                    ->relationship('conversation', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('type')
                    ->options(MessageType::class),
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                ]),
            ]);
    }

    /**
     * Show soft-deleted messages in the moderation queue so they can be
     * reviewed and restored.
     *
     * @return Builder<Message>
     */
    public static function getEloquentQuery(): Builder
    {
        return Message::query()
            ->withoutGlobalScope(SoftDeletingScope::class);
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
            'index' => Pages\ListMessages::route('/'),
            'edit' => Pages\EditMessage::route('/{record}/edit'),
        ];
    }
}
