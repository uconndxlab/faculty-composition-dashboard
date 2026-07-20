<?php

namespace App\Services;

class FacultyModelingService
{
    private const END_YEAR = 2035;

    /**
     * Temporary placeholder transition matrix.
     *
     * This is intentionally explicit and readable so IR can replace it with the
     * final matrix later without changing the rest of the modeling UI.
     */
    private const TRANSITION_MATRIX = [
   [0.7916, 0.0634, 0.0894, 0.0005, 0.0000, 0.0000, 0.0000, 0.0000],
    [0.0000, 0.4784, 0.4328, 0.0000, 0.0000, 0.0000, 0.0000, 0.0000],
    [0.0000, 0.0000, 0.8843, 0.0214, 0.0000, 0.0717, 0.0000, 0.0000],
    [0.0000, 0.0000, 0.0000, 0.7381, 0.1716, 0.0000, 0.0000, 0.0000],
    [0.0000, 0.0000, 0.0000, 0.0000, 0.8361, 0.0000, 0.0000, 0.0000],
    [0.0000, 0.0000, 0.0000, 0.0000, 0.0000, 0.8900, 0.0779, 0.0000],
    [0.0000, 0.0000, 0.0000, 0.0000, 0.0000, 0.0000, 0.7628, 0.1836],
    [0.0000, 0.0000, 0.0000, 0.0000, 0.0000, 0.0000, 0.0000, 0.8586]
    ];

    /**
     * IR notebook-derived default hire mix.
     */
    private const NEW_HIRE_DISTRIBUTION = [
        0.741,
        0.000,
        0.127,
        0.000,
        0.000,
        0.086,
        0.046,
        0.000,
    ];

    public function build(array $query = []): array
    {
        $defaults = $this->defaults();
        $selected = $this->selectedInputs($query, $defaults);
        $rows = $this->projectRows($selected);

        $first = $rows[0];
        $latest = $rows[count($rows) - 1];

        return [
            'selected' => $selected,
            'defaults' => $defaults,
            'rows' => $rows,
            'summaryCards' => $this->summaryCards($latest),
            'interpretation' => $this->interpretation($first, $latest),
            'comparisonRows' => $this->comparisonRows($first, $latest),
            'chartData' => $this->chartData($rows),
            'baselineBadge' => $this->baselineBadge($selected['main']),
            'isBaselineScenario' => $this->matchesScenario($selected['main'], $defaults['main']) && $this->matchesScenario($selected['advanced'], $defaults['advanced']),
            'scenarioSummary' => $this->scenarioSummary($selected),
            'scenarioPresets' => $this->scenarioPresets($selected),
            'controlChoices' => $this->controlChoices(),
            'advancedAssumptions' => $this->advancedAssumptions(
                $selected['advanced'],
                $selected['new_hire_distribution'],
                $defaults['transition_matrix']
            ),
        ];
    }

    private function defaults(): array
    {
        return [
            'main' => [
                'replacement_rate' => 0.5,
                'student_growth_rate' => 0.0,
                'ntt_student_faculty_ratio' => 16.4,
            ],
            'advanced' => [
                'baseline_year' => 2025,
                'baseline_assistant' => 247.0,
                'baseline_associate' => 378.0,
                'baseline_full' => 493.0,
                'baseline_ntt' => 566.0,
                'baseline_student_fte' => 27510.0,
                'tenure_system_student_faculty_ratio' => 15.4,
            ],
            'new_hire_distribution' => self::NEW_HIRE_DISTRIBUTION,
            'transition_matrix' => self::TRANSITION_MATRIX,
        ];
    }

    private function selectedInputs(array $query, array $defaults): array
    {
        return [
            'main' => [
                'replacement_rate' => $this->resolveChoice($query['replacement_rate'] ?? null, [0.0, 0.25, 0.5, 0.75, 1.0, 1.1], $defaults['main']['replacement_rate']),
                'student_growth_rate' => $this->resolveChoice($query['student_growth_rate'] ?? null, [-0.01, 0.0, 0.01, 0.02], $defaults['main']['student_growth_rate']),
                'ntt_student_faculty_ratio' => $this->resolveChoice($query['ntt_student_faculty_ratio'] ?? $query['ntt_per_tt_loss'] ?? null, [15.4, 16.4, 17.4, 18.4], $defaults['main']['ntt_student_faculty_ratio']),
            ],
            'advanced' => [
                'baseline_year' => $this->resolveInteger($query['baseline_year'] ?? null, $defaults['advanced']['baseline_year']),
                'baseline_assistant' => $this->resolveNumeric($query['baseline_assistant'] ?? null, $defaults['advanced']['baseline_assistant']),
                'baseline_associate' => $this->resolveNumeric($query['baseline_associate'] ?? null, $defaults['advanced']['baseline_associate']),
                'baseline_full' => $this->resolveNumeric($query['baseline_full'] ?? null, $defaults['advanced']['baseline_full']),
                'baseline_ntt' => $this->resolveNumeric($query['baseline_ntt'] ?? null, $defaults['advanced']['baseline_ntt']),
                'baseline_student_fte' => $this->resolveNumeric($query['baseline_student_fte'] ?? null, $defaults['advanced']['baseline_student_fte']),
                'tenure_system_student_faculty_ratio' => $this->resolveNumeric($query['tenure_system_student_faculty_ratio'] ?? null, $defaults['advanced']['tenure_system_student_faculty_ratio']),
            ],
            'new_hire_distribution' => $this->selectedNewHireDistribution($query, $defaults['new_hire_distribution']),
            'transition_matrix' => $defaults['transition_matrix'],
        ];
    }

    private function selectedNewHireDistribution(array $query, array $defaultDistribution): array
    {
        $distribution = [];

        foreach ($defaultDistribution as $index => $defaultValue) {
            $percentageKey = 'hire_dist_pct_' . $index;
            $ratioKey = 'hire_dist_' . $index;

            if (is_numeric($query[$percentageKey] ?? null)) {
                $distribution[$index] = max(0.0, ((float) $query[$percentageKey]) / 100);
                continue;
            }

            $distribution[$index] = max(0.0, $this->resolveNumeric($query[$ratioKey] ?? null, (float) $defaultValue));
        }

        $sum = array_sum($distribution);
        if ($sum <= 0.0) {
            return $defaultDistribution;
        }

        return array_map(static fn(float $value): float => $value / $sum, $distribution);
    }

    private function resolveChoice(mixed $requested, array $availableValues, float $defaultTarget): float
    {
        $target = is_numeric($requested) ? (float) $requested : $defaultTarget;

        return $this->nearestAvailableValue($target, $availableValues);
    }

    private function resolveNumeric(mixed $requested, float $default): float
    {
        return is_numeric($requested) ? (float) $requested : $default;
    }

    private function resolveInteger(mixed $requested, int $default): int
    {
        return is_numeric($requested) ? (int) round((float) $requested) : $default;
    }

    private function nearestAvailableValue(float $target, array $availableValues): float
    {
        if (empty($availableValues)) {
            return $target;
        }

        $closest = (float) $availableValues[0];
        $closestDistance = abs($closest - $target);

        foreach ($availableValues as $candidateValue) {
            $candidate = (float) $candidateValue;
            $distance = abs($candidate - $target);

            if ($distance < $closestDistance) {
                $closest = $candidate;
                $closestDistance = $distance;
            }
        }

        return $closest;
    }

    private function projectRows(array $selected): array
    {
        $state = [
            $selected['advanced']['baseline_assistant'],
            0.0,
            $selected['advanced']['baseline_associate'],
            0.0,
            0.0,
            $selected['advanced']['baseline_full'],
            0.0,
            0.0,
        ];

        $students = $selected['advanced']['baseline_student_fte'];
        $rows = [];

        for ($year = $selected['advanced']['baseline_year']; $year <= self::END_YEAR; $year++) {
            if ($year > $selected['advanced']['baseline_year']) {
                $students *= (1 + $selected['main']['student_growth_rate']);

                $surviving = $this->applyTransitionMatrix($state);
                $facultyLoss = max(0.0, array_sum($state) - array_sum($surviving));
                $replacementHires = $facultyLoss * $selected['main']['replacement_rate'];

                $state = $this->addReplacementHires($surviving, $replacementHires, $selected['new_hire_distribution']);
            }

            $assistant = $state[0] + $state[1];
            $associate = $state[2] + $state[3] + $state[4];
            $full = $state[5] + $state[6] + $state[7];
            $tenureSystem = $assistant + $associate + $full;

            if ($year === $selected['advanced']['baseline_year']) {
                $ntt = max(0.0, $selected['advanced']['baseline_ntt']);
            } else {
                $requiredNtt = ($students - ($tenureSystem * $selected['advanced']['tenure_system_student_faculty_ratio'])) / $selected['main']['ntt_student_faculty_ratio'];
                $ntt = max(0.0, $requiredNtt);
            }
            $totalFaculty = $tenureSystem + $ntt;

            $rows[] = [
                'year' => $year,
                'assistant' => $assistant,
                'associate' => $associate,
                'full' => $full,
                'tenure_system' => $tenureSystem,
                'ntt' => $ntt,
                'total_faculty' => $totalFaculty,
                'total_students' => $students,
                'student_ntt_ratio' => $ntt > 0 ? $students / $ntt : null,
                'student_faculty_ratio' => $totalFaculty > 0 ? $students / $totalFaculty : null,
            ];
        }

        return $rows;
    }

    private function applyTransitionMatrix(array $state): array
    {
        $next = array_fill(0, 8, 0.0);

        foreach (self::TRANSITION_MATRIX as $fromIndex => $row) {
            foreach ($row as $toIndex => $share) {
                $next[$toIndex] += $state[$fromIndex] * $share;
            }
        }

        return $next;
    }

    private function addReplacementHires(array $state, float $replacementHires, array $newHireDistribution): array
    {
        foreach ($newHireDistribution as $bucketIndex => $share) {
            $state[$bucketIndex] += $replacementHires * $share;
        }

        return $state;
    }

    private function scenarioSummary(array $selected): string
    {
        $replacement = $this->formatPercent($selected['main']['replacement_rate']);
        $growth = $this->formatPercent($selected['main']['student_growth_rate']);
        $nttTarget = $this->formatRatioTarget($selected['main']['ntt_student_faculty_ratio']);

        return 'Scenario assumes ' . $replacement . ' tenure-system replacement, ' .
            $growth . ' annual student growth, and an NTT student/faculty target of ' .
            $nttTarget . '.';
    }

    private function scenarioPresets(array $selected): array
    {
        $presets = [
            'current_path' => [
                'label' => 'Current path',
                'description' => '50% replacement, no student growth, 16.4 NTT target',
                'targets' => ['replacement_rate' => 0.5, 'student_growth_rate' => 0.0, 'ntt_student_faculty_ratio' => 16.4],
            ],
            'preserve_tenure_system' => [
                'label' => 'Preserve tenure system',
                'description' => '100% replacement, no student growth, 16.4 NTT target',
                'targets' => ['replacement_rate' => 1.0, 'student_growth_rate' => 0.0, 'ntt_student_faculty_ratio' => 16.4],
            ],
            'instructional_pressure' => [
                'label' => 'Instructional pressure',
                'description' => '50% replacement, +1% growth, 16.4 NTT target',
                'targets' => ['replacement_rate' => 0.5, 'student_growth_rate' => 0.01, 'ntt_student_faculty_ratio' => 16.4],
            ],
            'constrained_replacement' => [
                'label' => 'Constrained replacement',
                'description' => '25% replacement, no student growth, 16.4 NTT target',
                'targets' => ['replacement_rate' => 0.25, 'student_growth_rate' => 0.0, 'ntt_student_faculty_ratio' => 16.4],
            ],
        ];

        return array_map(function (array $preset) use ($selected) {
            $resolved = $this->resolvePresetTargets($preset['targets']);

            return [
                'label' => $preset['label'],
                'description' => $preset['description'],
                'values' => $resolved,
                'url' => route('modeling.index', $resolved),
                'active' => $this->matchesScenario($selected['main'], $resolved),
            ];
        }, $presets);
    }

    private function resolvePresetTargets(array $targets): array
    {
        return [
            'replacement_rate' => $this->nearestAvailableValue($targets['replacement_rate'], [0.0, 0.25, 0.5, 0.75, 1.0, 1.1]),
            'student_growth_rate' => $this->nearestAvailableValue($targets['student_growth_rate'], [-0.01, 0.0, 0.01, 0.02]),
            'ntt_student_faculty_ratio' => $this->nearestAvailableValue($targets['ntt_student_faculty_ratio'], [15.4, 16.4, 17.4, 18.4]),
        ];
    }

    private function controlChoices(): array
    {
        return [
            'replacement_rate' => [
                ['label' => '0%', 'value' => 0.0],
                ['label' => '25%', 'value' => 0.25],
                ['label' => '50%', 'value' => 0.5],
                ['label' => '75%', 'value' => 0.75],
                ['label' => '100%', 'value' => 1.0],
                ['label' => '110%', 'value' => 1.1],
            ],
            'student_growth_rate' => [
                ['label' => '-1%', 'value' => -0.01],
                ['label' => '0%', 'value' => 0.0],
                ['label' => '+1%', 'value' => 0.01],
                ['label' => '+2%', 'value' => 0.02],
            ],
            'ntt_student_faculty_ratio' => [
                ['label' => 'Lower load / more NTT needed', 'value' => 15.4],
                ['label' => 'Current path', 'value' => 16.4],
                ['label' => 'Higher load / fewer NTT needed', 'value' => 17.4],
            ],
        ];
    }

    private function advancedAssumptions(array $advanced, array $newHireDistribution, array $transitionMatrix): array
    {
        $bucketLabels = [
            'Assistant Professor <5 years',
            'Assistant Professor 5+ years',
            'Associate Professor <60',
            'Associate Professor 60-64',
            'Associate Professor 65+',
            'Professor <60',
            'Professor 60-64',
            'Professor 65+',
        ];

        return [
            'baselineInputs' => [
                ['name' => 'baseline_year', 'label' => 'Baseline year', 'value' => $advanced['baseline_year'], 'step' => 1],
                ['name' => 'baseline_assistant', 'label' => 'Baseline Assistant Professors', 'value' => $advanced['baseline_assistant'], 'step' => 1],
                ['name' => 'baseline_associate', 'label' => 'Baseline Associate Professors', 'value' => $advanced['baseline_associate'], 'step' => 1],
                ['name' => 'baseline_full', 'label' => 'Baseline Full Professors', 'value' => $advanced['baseline_full'], 'step' => 1],
                ['name' => 'baseline_ntt', 'label' => 'Baseline NTT faculty', 'value' => $advanced['baseline_ntt'], 'step' => 1],
                ['name' => 'baseline_student_fte', 'label' => 'Baseline student FTE', 'value' => $advanced['baseline_student_fte'], 'step' => 1],
                ['name' => 'tenure_system_student_faculty_ratio', 'label' => 'Tenure-system student/faculty ratio', 'value' => $advanced['tenure_system_student_faculty_ratio'], 'step' => 0.1],
            ],
            'bucketLabels' => $bucketLabels,
            'newHireDistribution' => array_map(
                static fn(string $label, float $value): array => ['label' => $label, 'value' => $value],
                $bucketLabels,
                $newHireDistribution
            ),
            'transitionMatrix' => $transitionMatrix,
            'transitionMatrixNote' => 'Transition assumptions are a temporary placeholder until IR provides/blesses the final transition matrix.',
        ];
    }

    private function summaryCards(array $latest): array
    {
        return [
            ['label' => 'Assistant Professors', 'value' => $this->formatFaculty($latest['assistant']), 'description' => 'Latest modeled year'],
            ['label' => 'Tenure-System Faculty', 'value' => $this->formatFaculty($latest['tenure_system']), 'description' => 'Assistant + associate + full'],
            ['label' => 'NTT Faculty', 'value' => $this->formatFaculty($latest['ntt']), 'description' => 'Latest modeled year'],
            ['label' => 'Total Faculty', 'value' => $this->formatFaculty($latest['total_faculty']), 'description' => 'Latest modeled year'],
            ['label' => 'Student / Faculty Ratio', 'value' => $this->formatRatio($latest['student_faculty_ratio']), 'description' => 'Latest modeled year'],
        ];
    }

    private function interpretation(array $first, array $last): array
    {
        $assistantChangePct = $this->percentChange($first['assistant'], $last['assistant']);
        $tenureSystemChangePct = $this->percentChange($first['tenure_system'], $last['tenure_system']);
        $nttChangePct = $this->percentChange($first['ntt'], $last['ntt']);
        $studentFacultyRatioChange = (float) $last['student_faculty_ratio'] - (float) $first['student_faculty_ratio'];

        $messages = [];

        if ($assistantChangePct <= -25) {
            $messages[] = 'Assistant professor pipeline declines substantially.';
        }

        if ($tenureSystemChangePct <= -10) {
            $messages[] = 'Tenure-system capacity declines.';
        }

        if ($nttChangePct >= 25) {
            $messages[] = 'Reliance on non-tenure faculty increases.';
        }

        if ($studentFacultyRatioChange > 0) {
            $messages[] = 'Instructional pressure increases.';
        }

        if (empty($messages)) {
            $messages[] = 'The selected scenario produces relatively stable modeled outcomes.';
        }

        return [
            'baselineYear' => $first['year'],
            'latestYear' => $last['year'],
            'messages' => $messages,
        ];
    }

    private function comparisonRows(array $first, array $last): array
    {
        return [
            $this->comparisonRow('Assistant', $first['assistant'], $last['assistant'], 1),
            $this->comparisonRow('Associate', $first['associate'], $last['associate'], 1),
            $this->comparisonRow('Full', $first['full'], $last['full'], 1),
            $this->comparisonRow('Tenure-System', $first['tenure_system'], $last['tenure_system'], 1),
            $this->comparisonRow('NTT', $first['ntt'], $last['ntt'], 1),
            $this->comparisonRow('Total Faculty', $first['total_faculty'], $last['total_faculty'], 1),
            $this->comparisonRow('Total Students', $first['total_students'], $last['total_students'], 0),
            $this->comparisonRow('Student / Faculty Ratio', $first['student_faculty_ratio'], $last['student_faculty_ratio'], 2),
            $this->comparisonRow('Student / NTT Ratio', $first['student_ntt_ratio'], $last['student_ntt_ratio'], 2),
        ];
    }

    private function comparisonRow(string $label, float|int|null $baseline, float|int|null $latest, int $decimals): array
    {
        $baselineValue = $baseline ?? 0.0;
        $latestValue = $latest ?? 0.0;
        $change = $latestValue - $baselineValue;

        return [
            'metric' => $label,
            'baseline' => $this->formatValue($baselineValue, $decimals),
            'latest' => $this->formatValue($latestValue, $decimals),
            'change' => $this->formatSignedValue($change, $decimals),
            'percent_change' => $this->formatPercentChange($baselineValue, $latestValue),
        ];
    }

    private function chartData(array $rows): array
    {
        return [
            'labels' => array_map(static fn(array $row) => $row['year'], $rows),
            'tenureVsNtt' => [
                $this->series($rows, 'Tenure-System', 'tenure_system', '#0d6efd'),
                $this->series($rows, 'NTT', 'ntt', '#dc3545'),
            ],
            'pipeline' => [
                $this->series($rows, 'Assistant', 'assistant', '#0d6efd'),
                $this->series($rows, 'Associate', 'associate', '#198754'),
                $this->series($rows, 'Full', 'full', '#7c3aed'),
            ],
            'ratios' => [
                $this->series($rows, 'Student / Faculty', 'student_faculty_ratio', '#0891b2'),
                $this->series($rows, 'Student / NTT', 'student_ntt_ratio', '#b45309'),
            ],
        ];
    }

    private function series(array $rows, string $label, string $column, string $color): array
    {
        return [
            'label' => $label,
            'data' => array_map(static fn(array $row) => $row[$column], $rows),
            'borderColor' => $color,
            'backgroundColor' => 'transparent',
            'tension' => 0.25,
            'fill' => false,
        ];
    }

    private function baselineBadge(array $main): string
    {
        return 'Baseline: ' . $this->formatPercent($main['replacement_rate']) . ' replacement, ' .
            $this->formatPercent($main['student_growth_rate'], false) . ' student growth, ' .
            $this->formatRatioTarget($main['ntt_student_faculty_ratio']) . ' NTT S/F target';
    }

    private function formatValue(float|int|null $value, int $decimals = 1): string
    {
        if ($value === null) {
            return '—';
        }

        return number_format((float) $value, $decimals);
    }

    private function formatFaculty(float|int|null $value): string
    {
        return $this->formatValue($value, 1);
    }

    private function formatRatio(float|int|null $value): string
    {
        return $this->formatValue($value, 2);
    }

    private function formatPercent(float|int $value, bool $signed = false): string
    {
        $value = (float) $value * 100;
        $prefix = $signed && $value > 0 ? '+' : '';

        return $prefix . rtrim(rtrim(number_format($value, 1), '0'), '.') . '%';
    }

    private function formatRatioTarget(float|int $value): string
    {
        return rtrim(rtrim(number_format((float) $value, 1), '0'), '.');
    }

    private function formatSignedValue(float|int|null $value, int $decimals = 1): string
    {
        if ($value === null) {
            return '—';
        }

        $value = (float) $value;
        $prefix = $value > 0 ? '+' : '';

        return $prefix . number_format($value, $decimals);
    }

    private function formatPercentChange(float|int|null $baseline, float|int|null $latest): string
    {
        if ($baseline === null || (float) $baseline === 0.0) {
            return '—';
        }

        $change = (((float) $latest - (float) $baseline) / (float) $baseline) * 100;
        $prefix = $change > 0 ? '+' : '';

        return $prefix . number_format($change, 1) . '%';
    }

    private function percentChange(float|int|null $baseline, float|int|null $latest): float
    {
        if ($baseline === null || (float) $baseline === 0.0) {
            return 0.0;
        }

        return (((float) $latest - (float) $baseline) / (float) $baseline) * 100;
    }

    private function matchesScenario(array $selected, array $defaults): bool
    {
        foreach ($defaults as $key => $defaultValue) {
            if (abs((float) $selected[$key] - (float) $defaultValue) > 1e-9) {
                return false;
            }
        }

        return true;
    }
}