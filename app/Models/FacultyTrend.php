<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacultyTrend extends Model
{
    protected $fillable = [
        'unitid',
        'institution',
        'sector',
        'public_private',
        'is_public',
        'carnegie_classification',
        'medical_degree_flag',
        'hospital_flag',
        'metric',
        'first_year',
        'last_year',
        'n_years',
        'first_value',
        'last_value',
        'absolute_change',
        'percent_change',
        'average_annual_change',
        'slope',
        'intercept',
        'r_squared',
        'p_value',
        'std_error',
    ];
}
