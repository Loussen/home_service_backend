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

        $resubmit = User::query()
            ->where('active_role', 'provider')
            ->where('provider_approval_status', 'pending')
            ->whereNotNull('provider_resubmitted_at')
            ->count();

        $firstTime = max(0, $pending - $resubmit);

        return [
            Stat::make('Təsdiq gözləyən', (string) $pending)
                ->description($firstTime.' yeni · '.$resubmit.' yenidən baxış')
                ->descriptionIcon($pending > 0 ? 'heroicon-m-bell-alert' : 'heroicon-m-check-circle')
                ->color($pending > 0 ? 'warning' : 'success')
                ->url('/admin/users?tableFilters[provider_approval_status][value]=pending'),
            Stat::make('Yenidən baxış', (string) $resubmit)
                ->description($resubmit > 0 ? 'Rədd sonrası yenidən göndərilənlər' : 'Hazırda yoxdur')
                ->descriptionIcon($resubmit > 0 ? 'heroicon-m-arrow-path' : 'heroicon-m-check-circle')
                ->color($resubmit > 0 ? 'info' : 'gray')
                ->url('/admin/users?tableFilters[provider_resubmit][value]=1'),
        ];
    }
}
