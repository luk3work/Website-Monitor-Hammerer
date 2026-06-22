<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertChannel extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'target'    => 'encrypted',
        'rules'     => 'array',
        'is_active' => 'boolean',
    ];
}
