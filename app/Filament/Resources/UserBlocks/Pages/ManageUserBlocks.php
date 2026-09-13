<?php

namespace App\Filament\Resources\UserBlocks\Pages;

use App\Filament\Resources\UserBlocks\UserBlockResource;
use Filament\Resources\Pages\ManageRecords;

class ManageUserBlocks extends ManageRecords
{
    protected static string $resource = UserBlockResource::class;
}
