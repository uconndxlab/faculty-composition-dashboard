<?php

namespace App\Http\Controllers;

use App\Models\FacultySummary;
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
            $snapshotCards = [];
            $rankMixCards = [];
            $chartData = $this->buildChartData(collect());

            return view('dashboard.index', compact('latest', 'latestYear', 'selectedInstitution', 'selectedYear', 'institutions', 'years', 'snapshotCards', 'rankMixCards', 'chartData'));
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

        $history = $selectedInstitution
            ? FacultySummary::where('institution', $selectedInstitution)
            ->orderBy('year')
            ->get()
            : collect();

        $snapshotCards = $latest ? $this->buildSnapshotCards($latest, $history) : [];
        $rankMixCards = $latest ? $this->buildRankMixCards($latest) : [];

        $chartData = $this->buildChartData($history);

        return view('dashboard.index', compact('latest', 'latestYear', 'selectedInstitution', 'selectedYear', 'institutions', 'years', 'snapshotCards', 'rankMixCards', 'chartData'));
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
        $labels = $history->pluck('year')->values();

        return [
            'labels' => $labels->toArray(),
            'modes' => [
                'shares' => [
                    [
                        'label'           => 'Tenure-System Share',
                        'data'            => $history->map(fn($r) => $pct($r->pct_tenure_system))->values()->toArray(),
                        'borderColor'     => '#0d6efd',
                        'backgroundColor' => 'transparent',
                        'yAxisID'         => 'percent',
                        'tension'         => 0.3,
                        'fill'            => false,
                    ],
                    [
                        'label'           => 'Non-Tenure Share',
                        'data'            => $history->map(fn($r) => $pct($r->pct_non_tenure))->values()->toArray(),
                        'borderColor'     => '#dc3545',
                        'backgroundColor' => 'transparent',
                        'yAxisID'         => 'percent',
                        'tension'         => 0.3,
                        'fill'            => false,
                    ],
                    [
                        'label'           => 'Total Faculty',
                        'data'            => $history->map(fn($r) => $r->total_faculty !== null ? (float) $r->total_faculty : null)->values()->toArray(),
                        'borderColor'     => '#198754',
                        'backgroundColor' => 'transparent',
                        'yAxisID'         => 'faculty',
                        'tension'         => 0.3,
                        'fill'            => false,
                    ],
                ],
                'counts' => [
                    [
                        'label'           => 'Tenure-System Count',
                        'data'            => $history->map(fn($r) => $r->tenure_system_total !== null ? (float) $r->tenure_system_total : null)->values()->toArray(),
                        'borderColor'     => '#0d6efd',
                        'backgroundColor' => 'transparent',
                        'yAxisID'         => 'faculty',
                        'tension'         => 0.3,
                        'fill'            => false,
                    ],
                    [
                        'label'           => 'Non-Tenure Count',
                        'data'            => $history->map(fn($r) => $r->non_tenure_total !== null ? (float) $r->non_tenure_total : null)->values()->toArray(),
                        'borderColor'     => '#dc3545',
                        'backgroundColor' => 'transparent',
                        'yAxisID'         => 'faculty',
                        'tension'         => 0.3,
                        'fill'            => false,
                    ],
                    [
                        'label'           => 'Total Faculty',
                        'data'            => $history->map(fn($r) => $r->total_faculty !== null ? (float) $r->total_faculty : null)->values()->toArray(),
                        'borderColor'     => '#198754',
                        'backgroundColor' => 'transparent',
                        'yAxisID'         => 'faculty',
                        'tension'         => 0.3,
                        'fill'            => false,
                    ],
                ],
            ],
            'benchmarks' => $this->buildBenchmarkChartData($labels),
        ];
    }

    private function buildBenchmarkChartData($labels): array
    {
        $rows = FacultySummary::whereNotNull('carnegie_classification')
            ->whereNotNull('year')
            ->orderBy('year')
            ->get()
            ->map(function (FacultySummary $summary) {
                $bucket = $this->benchmarkBucket($summary->carnegie_classification);

                return $bucket ? ['bucket' => $bucket, 'summary' => $summary] : null;
            })
            ->filter();

        return collect(['R1' => 'R1 average', 'R2' => 'R2 average'])
            ->mapWithKeys(function (string $label, string $bucket) use ($rows, $labels) {
                $bucketRows = $rows
                    ->filter(fn($row) => ($row['bucket'] ?? null) === $bucket)
                    ->pluck('summary')
                    ->groupBy('year')
                    ->sortKeys();

                return [
                    $bucket => [
                        'label' => $label,
                        'modes' => [
                            'shares' => [
                                $this->benchmarkDataset($bucketRows, $labels, 'Tenure-System Share', 'pct_tenure_system', '#7c3aed', 'percent'),
                                $this->benchmarkDataset($bucketRows, $labels, 'Non-Tenure Share', 'pct_non_tenure', '#f97316', 'percent'),
                                $this->benchmarkDataset($bucketRows, $labels, 'Total Faculty', 'total_faculty', '#0f172a', 'faculty'),
                            ],
                            'counts' => [
                                $this->benchmarkDataset($bucketRows, $labels, 'Tenure-System Count', 'tenure_system_total', '#7c3aed', 'faculty'),
                                $this->benchmarkDataset($bucketRows, $labels, 'Non-Tenure Count', 'non_tenure_total', '#f97316', 'faculty'),
                                $this->benchmarkDataset($bucketRows, $labels, 'Total Faculty', 'total_faculty', '#0f172a', 'faculty'),
                            ],
                        ],
                    ],
                ];
            })
            ->toArray();
    }

    private function benchmarkDataset($bucketRows, $labels, string $label, string $column, string $color, string $axis): array
    {
        return [
            'label' => $label,
            'data' => $labels->map(function ($year) use ($bucketRows, $column, $axis) {
                $yearRows = collect($bucketRows->get($year, []));
                $values = $yearRows
                    ->pluck($column)
                    ->filter(fn($value) => $value !== null)
                    ->map(fn($value) => (float) $value)
                    ->values();

                if ($values->isEmpty()) {
                    return null;
                }

                $value = $axis === 'faculty' ? $values->median() : $values->avg();

                return $axis === 'percent' ? round($value * 100, 2) : round($value, 2);
            })->values()->toArray(),
            'borderColor' => $color,
            'backgroundColor' => 'transparent',
            'yAxisID' => $axis,
            'tension' => 0.3,
            'fill' => false,
            'isBenchmark' => true,
        ];
    }

    private function benchmarkBucket(?string $carnegieClassification): ?string
    {
        if ($carnegieClassification === null) {
            return null;
        }

        $classification = trim($carnegieClassification);

        return match (true) {
            preg_match('/^Mixed Undergraduate\/Graduate(?:-Doctorate)? Large$/i', $classification) === 1 => 'R1',
            preg_match('/^Mixed Undergraduate\/Graduate(?:-Doctorate)? Medium$/i', $classification) === 1 => 'R2',
            default => null,
        };
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
