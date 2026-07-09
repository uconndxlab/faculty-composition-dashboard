<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TrajectorySimilarity extends Model
{
    protected $fillable = [
        'unitid',
        'institution',
        'sector',
        'carnegie_classification',
        'medical_degree_flag',
        'hospital_flag',
        'trajectory_distance_from_uconn',
        'trajectory_similarity_rank',
        'n_shared_trajectory_metrics',
        'slope_pct_non_tenure',
        'slope_pct_t_track',
        'slope_pct_tenure_system',
        'slope_pct_tenured',
        'pct_change_total_faculty',
    ];
}
