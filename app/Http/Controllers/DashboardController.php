<?php

namespace App\Http\Controllers;

use App\Models\FacultySummary;
use App\Models\SimilarityRanking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    private const UCONN = 'University of Connecticut';

    public function index(Request $request)
    {
        if (! Schema::hasTable('faculty_summaries')) {
            $latest = null;
            $latestYear = null;
            $selectedInstitution = self::UCONN;
            $selectedYear = null;
            $institutions = collect();
            $years = collect();
            $isUconnSelected = true;
            $snapshotCards = [];
            $rankMixCards = [];
            $chartData = $this->buildChartData(collect());
            $peers = collect();

            return view('dashboard.index', compact('latest', 'latestYear', 'selectedInstitution', 'selectedYear', 'institutions', 'years', 'isUconnSelected', 'snapshotCards', 'rankMixCards', 'chartData', 'peers'));
        }

        $institutions = FacultySummary::whereNotNull('institution')
            ->distinct()
            ->orderBy('institution')
            ->pluck('institution')
            ->values();

        $requestedInstitution = (string) $request->query('institution', self::UCONN);
        $selectedInstitution = $institutions->contains($requestedInstitution)
            ? $requestedInstitution
            : ($institutions->contains(self::UCONN) ? self::UCONN : $institutions->first());

        $years = $selectedInstitution
            ? FacultySummary::where('institution', $selectedInstitution)
                ->whereNotNull('year')
                ->distinct()
                ->orderByDesc('year')
                ->pluck('year')
                ->values()
            : collect();

        $requestedYear = $request->query('year');
        $requestedYear = is_numeric($requestedYear) ? (int) $requestedYear : null;
        $selectedYear = $requestedYear !== null && $years->contains($requestedYear)
            ? $requestedYear
            : $years->first();

        $latest = $selectedInstitution && $selectedYear
            ? FacultySummary::where('institution', $selectedInstitution)
                ->where('year', $selectedYear)
                ->first()
            : null;

        $latestYear = $selectedYear;
        $isUconnSelected = $selectedInstitution === self::UCONN;

        $history = $selectedInstitution
            ? FacultySummary::where('institution', $selectedInstitution)
            ->orderBy('year')
            ->get()
            : collect();

        $snapshotCards = $latest ? $this->buildSnapshotCards($latest, $history) : [];
        $rankMixCards = $latest ? $this->buildRankMixCards($latest) : [];

        $chartData = $this->buildChartData($history);

        $peers = $isUconnSelected ? $this->buildPeerRows($selectedYear) : collect();

        return view('dashboard.index', compact('latest', 'latestYear', 'selectedInstitution', 'selectedYear', 'institutions', 'years', 'isUconnSelected', 'snapshotCards', 'rankMixCards', 'chartData', 'peers'));
    }

    private function buildPeerRows(?int $year)
    {
        if (! $year || ! Schema::hasTable('similarity_rankings')) {
            return collect();
        }

        $rankings = SimilarityRanking::orderBy('composite_similarity_rank')
            ->limit(10)
            ->get();

        if ($rankings->isEmpty() || ! Schema::hasTable('faculty_summaries')) {
            return $rankings->map(fn($peer) => [
                'rank' => $peer->composite_similarity_rank,
                'institution' => $peer->institution,
                'sector' => $peer->sector,
                'total_faculty' => $peer->total_faculty !== null ? number_format($peer->total_faculty) : '—',
                'tenure_system_share' => '—',
                'non_tenure_share' => '—',
                'assistant_share' => '—',
                'senior_share' => '—',
            ]);
        }

        $summaries = FacultySummary::where('year', $year)
            ->whereIn('institution', $rankings->pluck('institution'))
            ->get()
            ->keyBy('institution');

        return $rankings->map(function ($peer) use ($summaries) {
            $summary = $summaries->get($peer->institution);

            return [
                'rank' => $peer->composite_similarity_rank,
                'institution' => $peer->institution,
                'sector' => $peer->sector,
                'total_faculty' => $summary?->total_faculty !== null ? number_format($summary->total_faculty) : ($peer->total_faculty !== null ? number_format($peer->total_faculty) : '—'),
                'tenure_system_share' => $this->formatPercent($summary?->pct_tenure_system),
                'non_tenure_share' => $this->formatPercent($summary?->pct_non_tenure),
                'assistant_share' => $this->formatPercent($summary?->pct_assistant_professor),
                'senior_share' => $this->formatPercent($summary?->pct_senior_faculty),
            ];
        });
    }

    private function buildSnapshotCards(FacultySummary $latest, $history): array
    {
        return [
            [
                'label' => 'Total Faculty',
                'value' => $latest->total_faculty !== null ? number_format($latest->total_faculty) : '—',
                'description' => 'Total instructional faculty count in the selected year.',
            ],
            [
                'label' => 'Total Faculty Change',
                'value' => $this->formatTotalFacultyChange($latest, $history),
                'description' => 'Percent change in total faculty since the first available year.',
            ],
            [
                'label' => 'Tenure-System Share',
                'value' => $this->formatPercent($latest->pct_tenure_system),
                'description' => 'Share of faculty who are tenured or tenure-track.',
            ],
            [
                'label' => 'Non-Tenure Share',
                'value' => $this->formatPercent($latest->pct_non_tenure),
                'description' => 'Share of faculty outside the tenure system.',
            ],
        ];
    }

    private function buildRankMixCards(FacultySummary $latest): array
    {
        return [
            [
                'label' => 'Assistant Professor Share',
                'value' => $this->formatPercent($latest->pct_assistant_professor),
                'description' => 'Rank/title share across all tenure statuses.',
            ],
            [
                'label' => 'Associate Professor Share',
                'value' => $this->formatPercent($latest->pct_associate_professor),
                'description' => 'Rank/title share across all tenure statuses.',
            ],
            [
                'label' => 'Full Professor Share',
                'value' => $this->formatPercent($latest->pct_professor),
                'description' => 'Rank/title share across all tenure statuses.',
            ],
            [
                'label' => 'Senior Faculty Share',
                'value' => $this->formatPercent($latest->pct_senior_faculty),
                'description' => 'Combined associate and full professor titles, regardless of tenure status.',
            ],
        ];
    }

    private function buildChartData($history): array
    {
        $pct = fn($v) => $v !== null ? round((float) $v * 100, 2) : null;

        return [
            'labels' => $history->pluck('year')->values()->toArray(),
            'datasets' => [
                [
                    'label'           => 'Tenure-System Share',
                    'data'            => $history->map(fn($r) => $pct($r->pct_tenure_system))->values()->toArray(),
                    'borderColor'     => '#0d6efd',
                    'backgroundColor' => 'transparent',
                    'tension'         => 0.3,
                    'fill'            => false,
                ],
                [
                    'label'           => 'Senior Faculty Share',
                    'data'            => $history->map(fn($r) => $pct($r->pct_senior_faculty))->values()->toArray(),
                    'borderColor'     => '#198754',
                    'backgroundColor' => 'transparent',
                    'tension'         => 0.3,
                    'fill'            => false,
                ],
                [
                    'label'           => 'Assistant Professor Share',
                    'data'            => $history->map(fn($r) => $pct($r->pct_assistant_professor))->values()->toArray(),
                    'borderColor'     => '#fd7e14',
                    'backgroundColor' => 'transparent',
                    'tension'         => 0.3,
                    'fill'            => false,
                ],
                [
                    'label'           => 'Non-Tenure Share',
                    'data'            => $history->map(fn($r) => $pct($r->pct_non_tenure))->values()->toArray(),
                    'borderColor'     => '#dc3545',
                    'backgroundColor' => 'transparent',
                    'tension'         => 0.3,
                    'fill'            => false,
                ],
            ],
        ];
    }

    private function formatPercent($value): string
    {
        return $value !== null ? number_format((float) $value * 100, 1) . '%' : '—';
    }

    private function formatTotalFacultyChange(FacultySummary $latest, $history): string
    {
        $first = $history
            ->where('year', '<=', $latest->year)
            ->filter(fn($row) => $row->total_faculty !== null)
            ->sortBy('year')
            ->first();

        if (! $first || $first->total_faculty == 0 || $latest->total_faculty === null) {
            return '—';
        }

        $change = (((float) $latest->total_faculty - (float) $first->total_faculty) / abs((float) $first->total_faculty)) * 100;
        $sign = $change > 0 ? '+' : '';

        return $sign . number_format($change, 1) . '%';
    }
}
