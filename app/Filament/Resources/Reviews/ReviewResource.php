<?php

namespace App\Filament\Resources\Reviews;

use App\Filament\Resources\Conversations\ConversationResource;
use App\Filament\Resources\Reviews\Pages\ManageReviews;
use App\Models\Review;
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

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationLabel = 'Rəylər';

    protected static ?string $modelLabel = 'rəy';

    protected static ?string $pluralModelLabel = 'Rəylər';

    protected static string|UnitEnum|null $navigationGroup = 'Chat';

    protected static ?int $navigationSort = 3;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

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
                TextColumn::make('offer.conversation_id')
                    ->label('Chat #')
                    ->url(fn (Review $record): ?string => $record->offer?->conversation_id
                        ? ConversationResource::getUrl('view', ['record' => $record->offer->conversation_id])
                        : null)
                    ->placeholder('—'),
                TextColumn::make('reviewer.name')
                    ->label('Kim yazıb')
                    ->description(fn (Review $record) => $record->reviewer?->phone)
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('reviewee.name')
                    ->label('Kimə')
                    ->description(fn (Review $record) => $record->reviewee?->phone)
                    ->searchable()
                    ->placeholder('—'),
                TextColumn::make('rating')
                    ->label('Ulduz')
                    ->formatStateUsing(fn ($state): string => str_repeat('★', (int) $state).' ('.(int) $state.')')
                    ->sortable(),
                TextColumn::make('comment')
                    ->label('Rəy')
                    ->wrap()
                    ->limit(80)
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('offer.price_azn')
                    ->label('Təklif qiyməti')
                    ->suffix(' AZN')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Tarix')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('rating')
                    ->label('Ulduz')
                    ->options([
                        5 => '5 ★',
                        4 => '4 ★',
                        3 => '3 ★',
                        2 => '2 ★',
                        1 => '1 ★',
                    ]),
            ])
            ->recordActions([
                Action::make('open_chat')
                    ->label('Söhbətə bax')
                    ->url(fn (Review $record): ?string => $record->offer?->conversation_id
                        ? ConversationResource::getUrl('view', ['record' => $record->offer->conversation_id])
                        : null)
                    ->visible(fn (Review $record): bool => (bool) $record->offer?->conversation_id),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['reviewer', 'reviewee', 'offer.conversation']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageReviews::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
