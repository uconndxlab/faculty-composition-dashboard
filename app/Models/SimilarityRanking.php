<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimilarityRanking extends Model
{
    protected $fillable = [
        'institution',
        'sector',
        'carnegie_classification',
        'hospital_flag',
        'total_faculty',
        'nine_d_similarity_rank',
        'rank_similarity_rank',
        'tenure_similarity_rank',
        'composite_similarity_rank',
    ];
}
