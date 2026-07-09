<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Institution extends Model
{
    protected $fillable = [
        'unitid',
        'name',
        'state',
        'sector',
        'carnegie_classification',
        'is_uconn',
    ];

    protected $casts = [
        'is_uconn' => 'boolean',
    ];
}
