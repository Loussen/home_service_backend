<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppString extends Model
{
    protected $fillable = [
        'key',
        'locale',
        'value',
    ];
}
