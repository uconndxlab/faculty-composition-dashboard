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
        'public_private',
        'is_public',
        'carnegie_classification',
        'is_uconn',
        'is_aau_public',
    ];

    protected $casts = [
        'is_uconn' => 'boolean',
        'is_public' => 'boolean',
        'is_aau_public' => 'boolean',
    ];
}
