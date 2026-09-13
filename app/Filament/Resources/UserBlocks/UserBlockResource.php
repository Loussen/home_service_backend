<?php

namespace App\Filament\Resources\UserBlocks;

use App\Filament\Resources\UserBlocks\Pages\ManageUserBlocks;
use App\Filament\Resources\Users\UserResource;
use App\Models\UserBlock;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class UserBlockResource extends Resource
{
    protected static ?string $model = UserBlock::class;

    protected static ?string $navigationLabel = 'Bloklar';

    protected static ?string $modelLabel = 'blok';

    protected static ?string $pluralModelLabel = 'Bloklar';

    protected static string|UnitEnum|null $navigationGroup = 'Chat';

    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;

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
                TextColumn::make('blocker.name')
                    ->label('Kim bloklayıb')
                    ->description(fn (UserBlock $record): string => trim(implode(' · ', array_filter([
                        $record->blocker?->phone,
                        self::roleLabel($record->blocker?->active_role),
                        $record->blocker_id ? '#'.$record->blocker_id : null,
                    ]))) ?: '—')
                    ->searchable()
                    ->url(fn (UserBlock $record): ?string => $record->blocker_id
                        ? UserResource::getUrl('edit', ['record' => $record->blocker_id])
                        : null),
                TextColumn::make('blocked.name')
                    ->label('Kim bloklanıb')
                    ->description(fn (UserBlock $record): string => trim(implode(' · ', array_filter([
                        $record->blocked?->phone,
                        self::roleLabel($record->blocked?->active_role),
                        $record->blocked_id ? '#'.$record->blocked_id : null,
                    ]))) ?: '—')
                    ->searchable()
                    ->url(fn (UserBlock $record): ?string => $record->blocked_id
                        ? UserResource::getUrl('edit', ['record' => $record->blocked_id])
                        : null),
                TextColumn::make('blocker.active_role')
                    ->label('Bloklayan rol')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::roleLabel($state) ?? '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('blocked.active_role')
                    ->label('Bloklanan rol')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::roleLabel($state) ?? '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Tarix')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('blocker_role')
                    ->label('Bloklayan rol')
                    ->options([
                        'client' => 'Ailə',
                        'provider' => 'İcraçı',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('blocker', fn (Builder $q) => $q->where('active_role', $data['value']))
                        : $query),
                SelectFilter::make('blocked_role')
                    ->label('Bloklanan rol')
                    ->options([
                        'client' => 'Ailə',
                        'provider' => 'İcraçı',
                    ])
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->whereHas('blocked', fn (Builder $q) => $q->where('active_role', $data['value']))
                        : $query),
            ])
            ->recordActions([
                Action::make('unblock')
                    ->label('Bloku götür')
                    ->icon(Heroicon::OutlinedLockOpen)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('İstifadəçilər arasındakı bloku götür?')
                    ->modalDescription(fn (UserBlock $record): string => sprintf(
                        '%s (#%d) → %s (#%d) blokunu silmək istəyirsiniz? Tərəflər yenidən CONNECT / mesajlaşa biləcək.',
                        $record->blocker?->name ?: 'İstifadəçi',
                        (int) $record->blocker_id,
                        $record->blocked?->name ?: 'İstifadəçi',
                        (int) $record->blocked_id,
                    ))
                    ->action(function (UserBlock $record): void {
                        $record->delete();
                        Notification::make()
                            ->title('Blok götürüldü')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Seçilənlərin blokunu götür'),
                ]),
            ])
            ->emptyStateHeading('Blok yoxdur')
            ->emptyStateDescription('İstifadəçilər chat-dən bir-birini bloklayanda burada görünəcək.');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['blocker:id,name,phone,active_role', 'blocked:id,name,phone,active_role']);
    }

    private static function roleLabel(?string $role): ?string
    {
        return match ($role) {
            'client' => 'Ailə',
            'provider' => 'İcraçı',
            default => $role,
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUserBlocks::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
