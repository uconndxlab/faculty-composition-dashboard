<?php

namespace App\Http\Controllers;

use App\Models\FacultySummary;
use App\Models\FacultyTrend;
use App\Models\InstitutionalRanking;
use App\Models\SimilarityRanking;
use App\Models\TrajectorySimilarity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class FacultyTrendController extends Controller
{
    private const UCONN = 'University of Connecticut';

    private const DEFAULT_METRICS = [
        'pct_non_tenure',
        'pct_tenure_system',
        'pct_tenured',
        'pct_t_track',
        'pct_non_tenure_assistant_professor',
        'pct_non_tenure_associate_professor',
        'pct_non_tenure_professor',
        'pct_senior_faculty',
        'pct_assistant_professor',
        'pct_associate_professor',
        'pct_professor',
        'total_faculty',
    ];

    private const METRICS = [
        'pct_non_tenure' => [
            'label' => 'Non-Tenure Share',
            'summaryColumn' => 'pct_non_tenure',
            'isPercentMetric' => true,
            'group' => 'Tenure Mix',
        ],
        'pct_tenure_system' => [
            'label' => 'Tenure-System Share',
            'summaryColumn' => 'pct_tenure_system',
            'isPercentMetric' => true,
            'group' => 'Tenure Mix',
        ],
        'pct_tenured' => [
            'label' => 'Tenured Share',
            'summaryColumn' => 'pct_tenured',
            'isPercentMetric' => true,
            'group' => 'Tenure Mix',
        ],
        'pct_t_track' => [
            'label' => 'Tenure-Track Share',
            'summaryColumn' => 'pct_t_track',
            'isPercentMetric' => true,
            'group' => 'Tenure Mix',
        ],
        'pct_non_tenure_assistant_professor' => [
            'label' => 'Non-Tenure Assistant Professor Share',
            'summaryColumn' => 'pct_non_tenure_assistant_professor',
            'isPercentMetric' => true,
            'group' => 'Non-Tenure Rank Detail',
        ],
        'pct_non_tenure_associate_professor' => [
            'label' => 'Non-Tenure Associate Professor Share',
            'summaryColumn' => 'pct_non_tenure_associate_professor',
            'isPercentMetric' => true,
            'group' => 'Non-Tenure Rank Detail',
        ],
        'pct_non_tenure_professor' => [
            'label' => 'Non-Tenure Full Professor Share',
            'summaryColumn' => 'pct_non_tenure_professor',
            'isPercentMetric' => true,
            'group' => 'Non-Tenure Rank Detail',
        ],
        'pct_senior_faculty' => [
            'label' => 'Senior Faculty Share',
            'summaryColumn' => 'pct_senior_faculty',
            'isPercentMetric' => true,
            'group' => 'Rank Mix',
        ],
        'pct_assistant_professor' => [
            'label' => 'Assistant Professor Share',
            'summaryColumn' => 'pct_assistant_professor',
            'isPercentMetric' => true,
            'group' => 'Rank Mix',
        ],
        'pct_associate_professor' => [
            'label' => 'Associate Professor Share',
            'summaryColumn' => 'pct_associate_professor',
            'isPercentMetric' => true,
            'group' => 'Rank Mix',
        ],
        'pct_professor' => [
            'label' => 'Full Professor Share',
            'summaryColumn' => 'pct_professor',
            'isPercentMetric' => true,
            'group' => 'Rank Mix',
        ],
        'total_faculty' => [
            'label' => 'Total Faculty',
            'summaryColumn' => 'total_faculty',
            'isPercentMetric' => false,
            'group' => 'Scale',
        ],
    ];

    public function index()
    {
        $metricLabels = $this->metricLabels();
        $rankDimensions = $this->rankDimensions();

        if (! Schema::hasTable('faculty_trends')) {
            $trends = collect();
            $changeChartData = ['labels' => [], 'datasets' => [['label' => 'Percent Change', 'data' => []]]];
            $comparisonInstitutions = collect();
            $trendExplorerData = [];
            $peerTrendData = $this->emptyPeerTrendData($metricLabels, $rankDimensions);
            $trajectories = collect();
            $usNewsRanks = collect();

            return view('trends.index', compact('trends', 'metricLabels', 'changeChartData', 'comparisonInstitutions', 'trendExplorerData', 'peerTrendData', 'trajectories', 'rankDimensions', 'usNewsRanks'));
        }

        $institutionProfiles = [];

        $uconnSummaries = Schema::hasTable('faculty_summaries')
            ? FacultySummary::where('institution', self::UCONN)->orderBy('year')->get()
            : collect();
        $trends = $this->orderedTrendRows(
            $this->trendsWithSummaryFallback(
                FacultyTrend::where('institution', self::UCONN)
                    ->whereIn('metric', self::DEFAULT_METRICS)
                    ->get(),
                collect([self::UCONN => $uconnSummaries])
            )->get(self::UCONN, collect())
        );

        $changeChartData = [
            'labels' => $trends->map(fn($trend) => $metricLabels[$trend->metric] ?? $trend->metric)->values()->toArray(),
            'datasets' => [[
                'label' => 'Percent Change',
                'data' => $trends->map(fn($trend) => $trend->percent_change !== null ? round((float) $trend->percent_change * 100, 1) : null)->values()->toArray(),
                'backgroundColor' => $trends->map(fn($trend) => (float) ($trend->percent_change ?? 0) >= 0 ? '#198754' : '#dc3545')->values()->toArray(),
            ]],
        ];

        $trajectories = Schema::hasTable('trajectory_similarities')
            ? TrajectorySimilarity::whereNotNull('trajectory_similarity_rank')
                ->orderBy('trajectory_similarity_rank')
                ->limit(15)
                ->get()
            : collect();

        $comparisonInstitutions = $trajectories->map(fn($row) => [
            'institution' => $row->institution,
            'rank' => $row->trajectory_similarity_rank,
            'distance' => $row->trajectory_distance_from_uconn,
        ])->values();

        $trendExplorerData = $this->buildTrendExplorerData($metricLabels, $comparisonInstitutions);

        $institutionProfiles = Schema::hasTable('institutional_rankings')
            ? InstitutionalRanking::whereNotNull('unitid')->get()
                ->mapWithKeys(fn($r) => [(string) $r->unitid => [
                    'unitid' => $r->unitid,
                    'rank' => $r->top_public_rank_nat_univ,
                    'grad_rate' => $r->grad_rate_6yr_cohort !== null ? (float) $r->grad_rate_6yr_cohort : null,
                    'grad_rate_pell' => $r->grad_rate_6yr_pell !== null ? (float) $r->grad_rate_6yr_pell : null,
                    'retention_rate' => $r->firstyr_retention_rate !== null ? (float) $r->firstyr_retention_rate : null,
                    'acceptance_rate' => $r->acceptance_rate !== null ? (float) $r->acceptance_rate : null,
                    'avg_faculty_salary' => $r->avg_faculty_salary !== null ? (float) $r->avg_faculty_salary : null,
                    'student_faculty_ratio' => $r->student_faculty_ratio !== null ? (float) $r->student_faculty_ratio : null,
                ]])
                ->toArray()
            : [];

        $peerTrendData = $this->buildPeerTrendData($metricLabels, $rankDimensions, $trajectories, $institutionProfiles);

        $usNewsRanks = Schema::hasTable('institutional_rankings')
            ? InstitutionalRanking::whereNotNull('unitid')
                ->whereNotNull('top_public_rank_nat_univ')
                ->pluck('top_public_rank_nat_univ', 'unitid')
            : collect();

        return view('trends.index', compact('trends', 'metricLabels', 'changeChartData', 'comparisonInstitutions', 'trendExplorerData', 'peerTrendData', 'trajectories', 'rankDimensions', 'usNewsRanks'));
    }

    private function metricLabels(): array
    {
        return collect(self::METRICS)
            ->mapWithKeys(fn(array $metric, string $key) => [$key => $metric['label']])
            ->toArray();
    }

    private function rankDimensions(): array
    {
        return [
            'composite' => [
                'label' => 'Composite',
                'column' => 'composite_similarity_rank',
                'description' => 'Best default peer list. Blends multiple similarity approaches so the result is broad enough to be useful and still explainable.',
            ],
            'nine_d' => [
                'label' => 'Detailed Cell Similarity',
                'column' => 'detailed_cell_similarity_rank',
                'description' => 'Compares the full faculty composition profile across detailed cell dimensions (tenure-status × rank). This replaces the prior 9D approach and is more granular.',
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
    }

    private function buildTrendExplorerData(array $metricLabels, Collection $comparisonInstitutions): array
    {
        $institutions = collect([self::UCONN])
            ->merge($comparisonInstitutions->pluck('institution'))
            ->filter()
            ->unique()
            ->values();

        $rows = FacultyTrend::whereIn('institution', $institutions)
            ->whereIn('metric', self::DEFAULT_METRICS)
            ->get()
            ->groupBy('institution');

        return [
            'uconn' => self::UCONN,
            'metrics' => collect(self::DEFAULT_METRICS)->map(fn($metric) => [
                'key' => $metric,
                'label' => $metricLabels[$metric] ?? $metric,
                'isPercentMetric' => str_starts_with($metric, 'pct_'),
            ])->values()->toArray(),
            'institutions' => $comparisonInstitutions->toArray(),
            'trends' => $rows->map(function ($institutionRows) {
                return $institutionRows->keyBy('metric')->map(fn($row) => $this->trendForExplorer($row))->toArray();
            })->toArray(),
        ];
    }

    private function buildPeerTrendData(array $metricLabels, array $rankDimensions, Collection $trajectories, array $institutionProfiles = []): array
    {
        if (! Schema::hasTable('faculty_summaries')) {
            return $this->emptyPeerTrendData($metricLabels, $rankDimensions);
        }

        $ranksByUnitid = Schema::hasTable('institutional_rankings')
            ? InstitutionalRanking::whereNotNull('unitid')->pluck('top_public_rank_nat_univ', 'unitid')
            : collect();

        $uconnUnitid = FacultySummary::where('institution', self::UCONN)->whereNotNull('unitid')->value('unitid');
        $uconnRank = $uconnUnitid ? ($ranksByUnitid->get((string) $uconnUnitid) ?? null) : null;

        $currentSets = $this->currentPeerSets($rankDimensions);
        $trajectorySet = $trajectories->map(fn($row) => [
            'institution' => $row->institution,
            'rank' => $row->trajectory_similarity_rank,
            'source' => 'trajectory',
            'sector' => $row->sector,
            'carnegie' => $row->carnegie_classification,
            'distance' => $row->trajectory_distance_from_uconn !== null ? round((float) $row->trajectory_distance_from_uconn, 4) : null,
        ])->values()->toArray();

        $rankBandSet = [
            'rank_band' => [
                'label' => 'Similar US News Rank',
                'description' => 'Institutions within a rank band of UConn in the US News Best Public Universities ranking. Adjust the band slider to widen or narrow the group.',
                'isRankBand' => true,
                'institutions' => [],
            ],
        ];

        $sets = collect([
            'trajectory' => [
                'label' => 'Trajectory-Similar Peers',
                'description' => 'Institutions changing in similar directions and at similar rates.',
                'institutions' => $trajectorySet,
            ],
        ])->merge($currentSets)->merge($rankBandSet)->toArray();

        $allInstitutions = FacultySummary::whereNotNull('institution')
            ->orderBy('institution')
            ->orderByDesc('year')
            ->get()
            ->unique('institution')
            ->map(fn(FacultySummary $row) => $this->institutionOptionForWorkspace($row, $ranksByUnitid))
            ->values();

        $institutions = collect([self::UCONN])
            ->merge(collect($sets)->flatMap(fn($set) => collect($set['institutions'])->pluck('institution')))
            ->merge($allInstitutions->pluck('institution'))
            ->filter()
            ->unique()
            ->values();

        $summaryRows = FacultySummary::whereIn('institution', $institutions)
            ->orderBy('institution')
            ->orderBy('year')
            ->get();

        $latestYear = FacultySummary::where('institution', self::UCONN)->max('year');
        $latestRows = $latestYear
            ? FacultySummary::where('year', $latestYear)
                ->whereIn('institution', $institutions)
                ->get()
                ->keyBy('institution')
            : collect();

        $trendRows = FacultyTrend::whereIn('institution', $institutions)
            ->whereIn('metric', self::DEFAULT_METRICS)
            ->get()
            ->groupBy('institution');
        $trendRows = $this->trendsWithSummaryFallback($trendRows, $summaryRows->groupBy('institution'));

        return [
            'uconn' => self::UCONN,
            'latestYear' => $latestYear,
            'uconnRank' => $uconnRank,
            'uconnUnitid' => $uconnUnitid,
            'defaultMetric' => 'pct_non_tenure',
            'defaultSet' => count($trajectorySet) > 0 ? 'trajectory' : 'current_composite',
            'metrics' => $this->metricCatalog($metricLabels),
            'sets' => $sets,
            'allInstitutions' => $allInstitutions->toArray(),
            'benchmarks' => $this->benchmarkSeries(),
            'series' => $this->summarySeries($summaryRows),
            'latest' => $latestRows->map(fn($row) => $this->summaryForWorkspace($row))->toArray(),
            'trends' => $trendRows->map(function ($institutionRows) {
                return $institutionRows->keyBy('metric')->map(fn($row) => $this->trendForExplorer($row))->toArray();
            })->toArray(),
            'institutionProfiles' => $institutionProfiles,
        ];
    }

    private function trendsWithSummaryFallback(Collection $trendRows, Collection $summaryRowsByInstitution): Collection
    {
        return $summaryRowsByInstitution->map(function (Collection $summaryRows, string $institution) use ($trendRows) {
            $institutionTrends = $trendRows->get($institution, collect())->values();
            $existingMetrics = $institutionTrends->pluck('metric')->filter()->all();
            $missingMetrics = collect(self::DEFAULT_METRICS)->diff($existingMetrics);

            $fallbackTrends = $missingMetrics
                ->map(fn(string $metric) => $this->trendFromSummaryRows($institution, $summaryRows, $metric))
                ->filter()
                ->values();

            return $institutionTrends->concat($fallbackTrends)->values();
        });
    }

    private function trendFromSummaryRows(string $institution, Collection $summaryRows, string $metric): ?object
    {
        $definition = self::METRICS[$metric];
        $values = $summaryRows
            ->filter(fn(FacultySummary $row) => $row->year !== null && $row->{$definition['summaryColumn']} !== null)
            ->sortBy('year')
            ->map(fn(FacultySummary $row) => [
                'year' => (int) $row->year,
                'value' => (float) $row->{$definition['summaryColumn']},
                'sector' => $row->sector,
                'carnegie_classification' => $row->carnegie_classification,
            ])
            ->values();

        if ($values->count() < 2) {
            return null;
        }

        $first = $values->first();
        $last = $values->last();
        $absoluteChange = $last['value'] - $first['value'];
        $percentChange = $first['value'] != 0.0 ? $absoluteChange / abs($first['value']) : null;
        $slope = $this->linearSlope($values);

        return (object) [
            'institution' => $institution,
            'sector' => $last['sector'],
            'carnegie_classification' => $last['carnegie_classification'],
            'metric' => $metric,
            'first_year' => $first['year'],
            'last_year' => $last['year'],
            'n_years' => $values->count(),
            'first_value' => $first['value'],
            'last_value' => $last['value'],
            'absolute_change' => $absoluteChange,
            'percent_change' => $percentChange,
            'average_annual_change' => ($last['year'] - $first['year']) !== 0 ? $absoluteChange / ($last['year'] - $first['year']) : null,
            'slope' => $slope,
            'r_squared' => null,
            'p_value' => null,
        ];
    }

    private function linearSlope(Collection $values): ?float
    {
        $count = $values->count();

        if ($count < 2) {
            return null;
        }

        $meanYear = $values->avg('year');
        $meanValue = $values->avg('value');
        $denominator = $values->sum(fn(array $point) => ($point['year'] - $meanYear) ** 2);

        if ($denominator == 0.0) {
            return null;
        }

        $numerator = $values->sum(fn(array $point) => ($point['year'] - $meanYear) * ($point['value'] - $meanValue));

        return $numerator / $denominator;
    }

    private function orderedTrendRows(Collection $rows): Collection
    {
        $order = collect(self::DEFAULT_METRICS)->flip();

        return $rows
            ->sortBy(fn($row) => $order->get($row->metric, 999))
            ->values();
    }

    private function institutionOptionForWorkspace(FacultySummary $summary, Collection $ranksByUnitid = null): array
    {
        return [
            'institution' => $summary->institution,
            'unitid' => $summary->unitid,
            'sector' => $summary->sector,
            'carnegie' => $summary->carnegie_classification,
            'totalFaculty' => $summary->total_faculty,
            'latestYear' => $summary->year,
            'usNewsRank' => $ranksByUnitid && $summary->unitid ? ($ranksByUnitid->get((string) $summary->unitid) ?? null) : null,
        ];
    }

    private function benchmarkSeries(): array
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
            ->mapWithKeys(function (string $label, string $bucket) use ($rows) {
                $bucketRows = $rows
                    ->filter(fn(array $row) => $row['bucket'] === $bucket)
                    ->pluck('summary')
                    ->groupBy('year')
                    ->sortKeys();

                $series = [];

                foreach (self::DEFAULT_METRICS as $metric) {
                    $definition = self::METRICS[$metric];
                    $series[$metric] = $bucketRows->map(function (Collection $yearRows, int $year) use ($definition) {
                        $values = $yearRows
                            ->pluck($definition['summaryColumn'])
                            ->filter(fn($value) => $value !== null)
                            ->map(fn($value) => (float) $value)
                            ->values();

                        if ($values->isEmpty()) {
                            return null;
                        }

                        $value = $definition['summaryColumn'] === 'total_faculty'
                            ? $values->median()
                            : $values->avg();

                        return [
                            'year' => $year,
                            'value' => $this->scaleSummaryValue($value, $definition['isPercentMetric']),
                            'n' => $values->count(),
                        ];
                    })->filter()->values()->toArray();
                }

                return [
                    $bucket => [
                        'label' => $label,
                        'series' => $series,
                    ],
                ];
            })
            ->toArray();
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

    private function currentPeerSets(array $rankDimensions): Collection
    {
        if (! Schema::hasTable('similarity_rankings')) {
            return collect();
        }

        return collect($rankDimensions)->mapWithKeys(function (array $dimension, string $key) {
            $rows = SimilarityRanking::whereNotNull($dimension['column'])
                ->orderBy($dimension['column'])
                ->limit(15)
                ->get()
                ->map(fn($row) => [
                    'institution' => $row->institution,
                    'rank' => $row->{$dimension['column']},
                    'source' => 'current',
                    'sector' => $row->sector,
                    'carnegie' => $row->carnegie_classification,
                    'totalFaculty' => $row->total_faculty,
                ])
                ->values()
                ->toArray();

            return [
                'current_' . $key => [
                    'label' => $dimension['label'] . ' Current Peers',
                    'description' => $dimension['description'],
                    'rankColumn' => $dimension['column'],
                    'institutions' => $rows,
                ],
            ];
        });
    }

    private function metricCatalog(array $metricLabels): array
    {
        return collect(self::DEFAULT_METRICS)->map(function (string $metric) use ($metricLabels) {
            $definition = self::METRICS[$metric];

            return [
                'key' => $metric,
                'label' => $metricLabels[$metric] ?? $metric,
                'summaryColumn' => $definition['summaryColumn'],
                'isPercentMetric' => $definition['isPercentMetric'],
                'unit' => $definition['isPercentMetric'] ? '%' : 'faculty',
                'changeUnit' => $definition['isPercentMetric'] ? 'pp' : 'faculty',
                'group' => $definition['group'],
            ];
        })->values()->toArray();
    }

    private function summarySeries(Collection $rows): array
    {
        return $rows->groupBy('institution')->map(function (Collection $institutionRows) {
            return $institutionRows->map(function (FacultySummary $row) {
                $values = ['year' => $row->year];

                foreach (self::DEFAULT_METRICS as $metric) {
                    $definition = self::METRICS[$metric];
                    $values[$metric] = $this->scaleSummaryValue($row->{$definition['summaryColumn']}, $definition['isPercentMetric']);
                }

                return $values;
            })->values()->toArray();
        })->toArray();
    }

    private function summaryForWorkspace(FacultySummary $summary): array
    {
        return [
            'institution' => $summary->institution,
            'sector' => $summary->sector,
            'carnegie' => $summary->carnegie_classification,
            'totalFaculty' => $summary->total_faculty,
            'bubbleSize' => $this->bubbleSize($summary->total_faculty),
            'pct_non_tenure' => $this->pct($summary->pct_non_tenure),
            'pct_tenure_system' => $this->pct($summary->pct_tenure_system),
            'pct_tenured' => $this->pct($summary->pct_tenured),
            'pct_t_track' => $this->pct($summary->pct_t_track),
            'pct_non_tenure_assistant_professor' => $this->pct($summary->pct_non_tenure_assistant_professor),
            'pct_non_tenure_associate_professor' => $this->pct($summary->pct_non_tenure_associate_professor),
            'pct_non_tenure_professor' => $this->pct($summary->pct_non_tenure_professor),
            'pct_senior_faculty' => $this->pct($summary->pct_senior_faculty),
            'pct_assistant_professor' => $this->pct($summary->pct_assistant_professor),
            'pct_associate_professor' => $this->pct($summary->pct_associate_professor),
            'pct_professor' => $this->pct($summary->pct_professor),
            'total_faculty' => $summary->total_faculty !== null ? (float) $summary->total_faculty : null,
        ];
    }

    private function emptyPeerTrendData(array $metricLabels, array $rankDimensions): array
    {
        return [
            'uconn' => self::UCONN,
            'latestYear' => null,
            'uconnRank' => null,
            'uconnUnitid' => null,
            'defaultMetric' => 'pct_non_tenure',
            'defaultSet' => 'trajectory',
            'institutionProfiles' => [],
            'metrics' => collect(self::DEFAULT_METRICS)->map(fn($metric) => [
                'key' => $metric,
                'label' => $metricLabels[$metric] ?? $metric,
                'summaryColumn' => self::METRICS[$metric]['summaryColumn'],
                'isPercentMetric' => self::METRICS[$metric]['isPercentMetric'],
                'unit' => self::METRICS[$metric]['isPercentMetric'] ? '%' : 'faculty',
                'changeUnit' => self::METRICS[$metric]['isPercentMetric'] ? 'pp' : 'faculty',
                'group' => self::METRICS[$metric]['group'],
            ])->values()->toArray(),
            'sets' => collect([
                'trajectory' => [
                    'label' => 'Trajectory-Similar Peers',
                    'description' => 'Institutions changing in similar directions and at similar rates.',
                    'institutions' => [],
                ],
            ])->merge(collect($rankDimensions)->mapWithKeys(fn(array $dimension, string $key) => [
                'current_' . $key => [
                    'label' => $dimension['label'] . ' Current Peers',
                    'description' => $dimension['description'],
                    'rankColumn' => $dimension['column'],
                    'institutions' => [],
                ],
            ]))->merge([
                'rank_band' => [
                    'label' => 'Similar US News Rank',
                    'description' => 'Institutions within a rank band of UConn in the US News Best Public Universities ranking.',
                    'isRankBand' => true,
                    'institutions' => [],
                ],
            ])->toArray(),
            'allInstitutions' => [],
            'benchmarks' => [
                'R1' => ['label' => 'R1 average', 'series' => []],
                'R2' => ['label' => 'R2 average', 'series' => []],
            ],
            'series' => [],
            'latest' => [],
            'trends' => [],
        ];
    }

    private function trendForExplorer($row): array
    {
        $isPercentMetric = str_starts_with((string) $row->metric, 'pct_');

        return [
            'metric' => $row->metric,
            'firstYear' => $row->first_year,
            'lastYear' => $row->last_year,
            'firstValue' => $this->scaleTrendValue($row->first_value, $isPercentMetric),
            'lastValue' => $this->scaleTrendValue($row->last_value, $isPercentMetric),
            'absoluteChange' => $this->scaleTrendValue($row->absolute_change, $isPercentMetric),
            'percentChange' => $row->percent_change !== null ? round((float) $row->percent_change * 100, 1) : null,
            'slope' => $this->scaleTrendValue($row->slope, $isPercentMetric),
            'rSquared' => $row->r_squared !== null ? round((float) $row->r_squared, 3) : null,
            'pValue' => $row->p_value !== null ? round((float) $row->p_value, 4) : null,
            'unit' => $isPercentMetric ? '%' : 'faculty',
            'changeUnit' => $isPercentMetric ? 'percentage points' : 'faculty',
        ];
    }

    private function scaleTrendValue($value, bool $isPercentMetric): ?float
    {
        if ($value === null) {
            return null;
        }

        $scaled = $isPercentMetric ? (float) $value * 100 : (float) $value;

        return round($scaled, $isPercentMetric ? 2 : 1);
    }

    private function scaleSummaryValue($value, bool $isPercentMetric): ?float
    {
        if ($value === null) {
            return null;
        }

        return round($isPercentMetric ? (float) $value * 100 : (float) $value, $isPercentMetric ? 2 : 1);
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
