<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstitutionalRanking extends Model
{
    protected $table = 'institutional_rankings';

    protected $fillable = [
        'unitid',
        'name',
        'top_public_rank_nat_univ',
        'total_enrollment',
        'total_faculty',
        'student_faculty_ratio',
        'firstyr_retention_rate',
        'grad_rate_6yr_cohort',
        'grad_rate_6yr_pell',
        'pell_pct_cohort',
        'acceptance_rate',
        'avg_faculty_salary',
        'edu_exp_per_student',
        'total_rd_expenditures',
        'inst_tuition_fees',
        'net_cost',
        'medical_degrees',
        'degree_of_urbanization',
    ];
}
