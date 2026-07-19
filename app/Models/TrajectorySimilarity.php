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
        'slope_pct_assistant_professor',
        'slope_pct_associate_professor',
        'slope_pct_professor',
        'slope_pct_senior_faculty',
        'slope_ugrds_per_faculty',
        'tenure_system_total',
        'non_tenure_total',
        'senior_faculty_total',
        'ugrd_fte',
        'grad_fte',
        'tenure_system_total_delta',
        'non_tenure_total_delta',
        'senior_faculty_total_delta',
        'ugrd_fte_delta',
        'grad_fte_delta',
    ];
}
