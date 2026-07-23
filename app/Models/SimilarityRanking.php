<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SimilarityRanking extends Model
{
    protected $fillable = [
        'unitid',
        'institution',
        'sector',
        'public_private',
        'is_public',
        'research_activity_class',
        'carnegie_classification',
        'medical_degree_flag',
        'hospital_flag',
        'year',
        'total_faculty',
        'pct_tenured_professor',
        'pct_tenured_associate_professor',
        'pct_tenured_assistant_professor',
        'pct_t_track_professor',
        'pct_t_track_associate_professor',
        'pct_t_track_assistant_professor',
        'pct_non_tenure_professor',
        'pct_non_tenure_associate_professor',
        'pct_non_tenure_assistant_professor',
        'uconn_euclidean_distance',
        'rank_euclidean_distance',
        'tenure_euclidean_distance',
        'detailed_cell_euclidean_distance',
        'pct_professor',
        'pct_associate_professor',
        'pct_assistant_professor',
        'pct_tenured',
        'pct_t_track',
        'pct_non_tenure',
        'pct_tenure_system',
        'pct_professor_ranked',
        'pct_associate_professor_ranked',
        'pct_assistant_professor_ranked',
        'nine_d_rank_pct',
        'rank_rank_pct',
        'tenure_rank_pct',
        'composite_similarity_score',
        'rank_similarity_rank',
        'tenure_similarity_rank',
        'composite_similarity_rank',
        'euclidean_similarity_rank',
        'nine_d_similarity_rank',
        'detailed_cell_similarity_rank',
        'detailed_cell_distance_pct_rank',
        'rank_distance_pct_rank',
        'tenure_distance_pct_rank',
    ];
}
