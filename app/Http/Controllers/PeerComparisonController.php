<?php

namespace App\Http\Controllers;

use App\Models\FacultySummary;
use App\Models\SimilarityRanking;
use App\Models\TrajectorySimilarity;
use Illuminate\Support\Facades\Schema;

class PeerComparisonController extends Controller
{
    private const UCONN = 'University of Connecticut';

    public function index()
    {
        $rankDimensions = [
            'composite' => [
                'label' => 'Composite',
                'column' => 'composite_similarity_rank',
                'description' => 'Best default peer list. Blends multiple similarity approaches so the result is broad enough to be useful and still explainable.',
            ],
            'nine_d' => [
                'label' => '9D Similarity',
                'column' => 'nine_d_similarity_rank',
                'description' => 'Compares the full faculty composition profile across nine rank-by-tenure dimensions. Most complete, but hardest to visualize directly.',
            ],
            'tenure' => [
                'label' => 'Tenure-System',
                'column' => 'tenure_similarity_rank',
                'description' => 'Compares only tenure status mix: tenured, tenure-track, and non-tenure faculty.',
            ],
            'rank' => [
                'label' => 'Rank',
                'column' => 'rank_similarity_rank',
                'description' => 'Compares only faculty rank mix: assistant, associate, and full professor.',
            ],
        ];

        $rankings = Schema::hasTable('similarity_rankings')
            ? collect($rankDimensions)->mapWithKeys(function (array $dimension, string $key) {
                return [
                    $key => SimilarityRanking::whereNotNull($dimension['column'])
                        ->orderBy($dimension['column'])
                        ->limit(15)
                        ->get(),
                ];
            })
            : collect($rankDimensions)->mapWithKeys(fn($_, string $key) => [$key => collect()]);

        $latestYear = Schema::hasTable('faculty_summaries')
            ? FacultySummary::where('institution', self::UCONN)->max('year')
            : null;

        $explorerData = $this->buildExplorerData($rankDimensions, $latestYear);

        $trajectories = Schema::hasTable('trajectory_similarities')
            ? TrajectorySimilarity::whereNotNull('trajectory_similarity_rank')
                ->orderBy('trajectory_similarity_rank')
                ->limit(15)
                ->get()
            : collect();

        return view('peers.index', compact('rankDimensions', 'rankings', 'trajectories', 'latestYear', 'explorerData'));
    }

    private function buildExplorerData(array $rankDimensions, ?int $latestYear): array
    {
        if (! $latestYear || ! Schema::hasTable('similarity_rankings') || ! Schema::hasTable('faculty_summaries')) {
            return [];
        }

        $uconn = FacultySummary::where('institution', self::UCONN)
            ->where('year', $latestYear)
            ->first();

        if (! $uconn) {
            return [];
        }

        return collect($rankDimensions)->mapWithKeys(function (array $dimension, string $key) use ($latestYear, $uconn) {
            $rankings = SimilarityRanking::whereNotNull($dimension['column'])
                ->orderBy($dimension['column'])
                ->limit(25)
                ->get();

            $summaries = FacultySummary::where('year', $latestYear)
                ->whereIn('institution', $rankings->pluck('institution'))
                ->get()
                ->keyBy('institution');

            $institutions = $rankings->map(function ($ranking) use ($summaries, $dimension) {
                $summary = $summaries->get($ranking->institution);

                if (! $summary) {
                    return null;
                }

                return $this->summaryForExplorer($summary, $ranking->{$dimension['column']});
            })->filter()->values();

            return [
                $key => [
                    'label' => $dimension['label'],
                    'rankColumn' => $dimension['column'],
                    'uconn' => $this->summaryForExplorer($uconn, null, true),
                    'institutions' => $institutions->toArray(),
                ],
            ];
        })->toArray();
    }

    private function summaryForExplorer(FacultySummary $summary, ?int $rank = null, bool $isUconn = false): array
    {
        return [
            'institution' => $summary->institution,
            'rank' => $rank,
            'isUconn' => $isUconn,
            'sector' => $summary->sector,
            'carnegie' => $summary->carnegie_classification,
            'totalFaculty' => $summary->total_faculty,
            'bubbleSize' => $this->bubbleSize($summary->total_faculty),
            'pctTenured' => $this->pct($summary->pct_tenured),
            'pctTenureTrack' => $this->pct($summary->pct_t_track),
            'pctNonTenure' => $this->pct($summary->pct_non_tenure),
            'pctTenureSystem' => $this->pct($summary->pct_tenure_system),
            'pctAssistant' => $this->pct($summary->pct_assistant_professor),
            'pctAssociate' => $this->pct($summary->pct_associate_professor),
            'pctFull' => $this->pct($summary->pct_professor),
            'pctSenior' => $this->pct($summary->pct_senior_faculty),
        ];
    }

    private function pct($value): ?float
    {
        return $value !== null ? round((float) $value * 100, 1) : null;
    }

    private function bubbleSize($totalFaculty): float
    {
        if (! $totalFaculty) {
            return 6;
        }

        return round(max(5, min(18, sqrt((float) $totalFaculty) / 3)), 1);
    }
}
