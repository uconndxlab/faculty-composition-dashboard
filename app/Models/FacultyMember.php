<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacultyMember extends Model
{
    protected $fillable = [
        'employee_id',
        'name',
        'school',
        'department',
        'rank',
        'tenure_status',
        'faculty_type',
        'start_year',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
