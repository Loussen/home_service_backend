<?php

namespace App\Filament\Resources\Offers;

use App\Filament\Resources\Conversations\ConversationResource;
use App\Filament\Resources\Offers\Pages\ManageOffers;
use App\Models\Offer;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class OfferResource extends Resource
{
    protected static ?string $model = Offer::class;

    protected static ?string $navigationLabel = 'Təkliflər';

    protected static ?string $modelLabel = 'təklif';

    protected static ?string $pluralModelLabel = 'Təkliflər';

    protected static string|UnitEnum|null $navigationGroup = 'Chat';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('conversation_id')
                    ->label('Chat #')
                    ->url(fn (Offer $record): string => ConversationResource::getUrl('view', ['record' => $record->conversation_id]))
                    ->sortable(),
                TextColumn::make('conversation.client.name')
                    ->label('Ailə')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('conversation.provider.name')
                    ->label('İcraçı')
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('proposer.name')
                    ->label('Təklif edən')
                    ->placeholder('—'),
                TextColumn::make('scheduled_at')
                    ->label('Vaxt')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('duration_hours')
                    ->label('Müddət')
                    ->suffix(' saat')
                    ->placeholder('—'),
                TextColumn::make('price_azn')
                    ->label('Qiymət')
                    ->suffix(' AZN')
                    ->sortable(),
                TextColumn::make('note')
                    ->label('Qeyd')
                    ->limit(36)
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'accepted', 'completed' => 'success',
                        'declined', 'cancelled' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Gözləyir',
                        'accepted' => 'Qəbul olunub',
                        'declined' => 'Rədd edilib',
                        'completed' => 'Tamamlanıb',
                        'cancelled' => 'Ləğv edilib',
                        default => $state,
                    }),
                TextColumn::make('created_at')
                    ->label('Göndərilib')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Gözləyir',
                        'accepted' => 'Qəbul olunub',
                        'declined' => 'Rədd edilib',
                        'completed' => 'Tamamlanıb',
                        'cancelled' => 'Ləğv edilib',
                    ]),
            ])
            ->recordActions([
                Action::make('open_chat')
                    ->label('Söhbətə bax')
                    ->url(fn (Offer $record): string => ConversationResource::getUrl('view', ['record' => $record->conversation_id])),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['conversation.client', 'conversation.provider', 'proposer']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageOffers::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
