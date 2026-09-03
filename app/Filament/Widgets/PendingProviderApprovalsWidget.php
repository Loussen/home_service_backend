<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendingProviderApprovalsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $pending = User::query()
            ->where('active_role', 'provider')
            ->where('provider_approval_status', 'pending')
            ->count();

        return [
            Stat::make('Təsdiq gözləyən icraçılar', (string) $pending)
                ->description($pending > 0 ? 'Yeni qeydiyyatlar baxılmalıdır' : 'Gözləmədə yoxdur')
                ->descriptionIcon($pending > 0 ? 'heroicon-m-bell-alert' : 'heroicon-m-check-circle')
                ->color($pending > 0 ? 'warning' : 'success')
                ->url('/admin/users?tableFilters[provider_approval_status][value]=pending'),
        ];
    }
}
