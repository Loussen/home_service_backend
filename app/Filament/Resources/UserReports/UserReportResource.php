<?php

namespace App\Filament\Resources\UserReports;

use App\Filament\Resources\UserReports\Pages\ManageUserReports;
use App\Models\User;
use App\Models\UserReport;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserReportResource extends Resource
{
    protected static ?string $model = UserReport::class;

    protected static ?string $navigationLabel = 'Şikayətlər';

    protected static ?string $modelLabel = 'şikayət';

    protected static ?string $pluralModelLabel = 'Şikayətlər';

    protected static ?int $navigationSort = 7;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reporter.name')
                    ->label('Şikayət edən')
                    ->searchable(),
                TextColumn::make('reportedUser.name')
                    ->label('Hədəf')
                    ->searchable(),
                TextColumn::make('reason')
                    ->label('Səbəb')
                    ->badge(),
                TextColumn::make('details')
                    ->label('Qeyd')
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('conversation_id')
                    ->label('Chat ID')
                    ->placeholder('—'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'reviewed' => 'Baxılıb',
                        'dismissed' => 'Rədd edilib',
                        default => 'Gözləyir',
                    }),
                TextColumn::make('created_at')
                    ->label('Tarix')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('review')
                    ->label('Baxıldı')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === UserReport::PENDING)
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update([
                        'status' => UserReport::REVIEWED,
                        'reviewed_at' => now(),
                    ])),
                Action::make('dismiss')
                    ->label('Rədd et')
                    ->color('gray')
                    ->visible(fn ($record) => $record->status === UserReport::PENDING)
                    ->requiresConfirmation()
                    ->action(fn ($record) => $record->update([
                        'status' => UserReport::DISMISSED,
                        'reviewed_at' => now(),
                    ])),
                Action::make('block_user')
                    ->label('İstifadəçini blokla')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Hədəf istifadəçini bloklamaq?')
                    ->action(function ($record): void {
                        User::query()
                            ->whereKey($record->reported_user_id)
                            ->update(['status' => 'blocked']);
                        $record->update([
                            'status' => UserReport::REVIEWED,
                            'reviewed_at' => now(),
                            'admin_note' => 'Admin tərəfindən bloklandı',
                        ]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUserReports::route('/'),
        ];
    }
}
