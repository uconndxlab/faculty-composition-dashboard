<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForecastingOutput extends Model
{
    protected $table = 'forecasting_outputs';

    protected $fillable = [
        'ntt_per_tt_loss',
        'student_growth_rate',
        'replacement_rate',
        'year',
        'assistant',
        'associate',
        'full',
        'ntt',
        'total_faculty',
        'total_students',
        'student_ntt_ratio',
        'student_faculty_ratio',
    ];
}
