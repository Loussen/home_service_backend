<?php

namespace App\Filament\Resources\Conversations;

use App\Filament\Resources\Conversations\Pages\ListConversations;
use App\Filament\Resources\Conversations\Pages\ViewConversation;
use App\Filament\Resources\Conversations\RelationManagers\MessagesRelationManager;
use App\Filament\Resources\Conversations\RelationManagers\OffersRelationManager;
use App\Filament\Resources\Conversations\RelationManagers\ReviewsRelationManager;
use App\Filament\Resources\Conversations\Schemas\ConversationInfolist;
use App\Filament\Resources\Conversations\Tables\ConversationsTable;
use App\Models\Conversation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ConversationResource extends Resource
{
    protected static ?string $model = Conversation::class;

    protected static ?string $navigationLabel = 'Söhbətlər';

    protected static ?string $modelLabel = 'söhbət';

    protected static ?string $pluralModelLabel = 'Söhbətlər';

    protected static string|UnitEnum|null $navigationGroup = 'Chat';

    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ConversationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConversationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            MessagesRelationManager::class,
            OffersRelationManager::class,
            ReviewsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['client', 'provider', 'providerProfile', 'lastMessage.sender'])
            ->withCount(['messages', 'offers', 'reviews']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConversations::route('/'),
            'view' => ViewConversation::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
