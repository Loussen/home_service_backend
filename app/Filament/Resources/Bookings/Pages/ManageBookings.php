<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use Filament\Resources\Pages\ManageRecords;

class ManageBookings extends ManageRecords
{
    protected static string $resource = BookingResource::class;
}
