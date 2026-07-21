<?php

namespace App\Services;

class FacultyModelingService
{
    private const TRANSITION_MATRIX = [
        'Assistant' => [
            'Assistant' => 0.8121,
            'Associate' => 0.1251,
            'Full' => 0.0,
            'Exit' => 0.0628,
        ],
        'Associate' => [
            'Assistant' => 0.0,
            'Associate' => 0.895,
            'Full' => 0.0653,
            'Exit' => 0.0396,
        ],
        'Full' => [
            'Assistant' => 0.0,
            'Associate' => 0.0,
            'Full' => 0.9359,
            'Exit' => 0.0631,
        ],
    ];

    private const HIRE_PROBS = [
        'historical' => [
            'Assistant' => 0.7404129793510325,
            'Associate' => 0.13569321533923304,
            'Full' => 0.12389380530973451,
        ],
        'recent' => [
            'Assistant' => 0.8082191780821918,
            'Associate' => 0.0684931506849315,
            'Full' => 0.1232876712328767,
        ],
    ];

    private const BASELINE = [
        'Assistant' => 247.0,
        'Associate' => 381.0,
        'Full' => 500.0,
        'NTT' => 580.0,
        'Students' => 27510.0,
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
            'scenarioSummary' => $this->scenarioSummary($selected),
            'controlChoices' => $this->controlChoices(),
            'advancedAssumptions' => $this->advancedAssumptions($selected),
        ];
    }

    private function defaults(): array
    {
        return [
            'main' => [
                'hiring_model' => 'recent',
                'ntt_replacement_probability' => 0.5,
                'ntt_per_tenure_exit' => 0.25,
                'student_growth_rate' => 0.0,
            ],
            'settings' => [
                'years_forward' => 10,
                'start_year' => 2025,
            ],
            'baseline' => self::BASELINE,
            'transition_matrix' => self::TRANSITION_MATRIX,
            'hire_probs' => self::HIRE_PROBS,
        ];
    }

    private function selectedInputs(array $query, array $defaults): array
    {
        $hiringModel = is_string($query['hiring_model'] ?? null) ? strtolower((string) $query['hiring_model']) : $defaults['main']['hiring_model'];

        if (! array_key_exists($hiringModel, self::HIRE_PROBS)) {
            $hiringModel = $defaults['main']['hiring_model'];
        }

        return [
            'main' => [
                'hiring_model' => $hiringModel,
                'ntt_replacement_probability' => $this->resolveChoice(
                    $query['ntt_replacement_probability'] ?? null,
                    [0.0, 0.25, 0.5, 0.75, 1.0],
                    $defaults['main']['ntt_replacement_probability']
                ),
                'ntt_per_tenure_exit' => $this->resolveChoice(
                    $query['ntt_per_tenure_exit'] ?? null,
                    [0.0, 0.25, 0.5, 0.75, 1.0],
                    $defaults['main']['ntt_per_tenure_exit']
                ),
                'student_growth_rate' => $this->resolveChoice(
                    $query['student_growth_rate'] ?? null,
                    [-0.01, 0.0, 0.01, 0.02],
                    $defaults['main']['student_growth_rate']
                ),
            ],
            'settings' => $defaults['settings'],
            'baseline' => $defaults['baseline'],
            'transition_matrix' => $defaults['transition_matrix'],
            'hire_probs' => $defaults['hire_probs'],
        ];
    }

    private function resolveChoice(mixed $requested, array $availableValues, float $defaultTarget): float
    {
        $target = is_numeric($requested) ? (float) $requested : $defaultTarget;

        return $this->nearestAvailableValue($target, $availableValues);
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
        $assistant = (float) $selected['baseline']['Assistant'];
        $associate = (float) $selected['baseline']['Associate'];
        $full = (float) $selected['baseline']['Full'];
        $ntt = (float) $selected['baseline']['NTT'];
        $students = (float) $selected['baseline']['Students'];

        $startYear = (int) $selected['settings']['start_year'];
        $endYear = $startYear + (int) $selected['settings']['years_forward'];
        $rows = [];

        for ($year = $startYear; $year <= $endYear; $year++) {
            $tenureSystem = $assistant + $associate + $full;
            $totalFaculty = $tenureSystem + $ntt;

            $rows[] = [
                'year' => $year,
                'assistant' => round($assistant, 1),
                'associate' => round($associate, 1),
                'full' => round($full, 1),
                'tenure_system' => round($tenureSystem, 1),
                'ntt' => round($ntt, 1),
                'total_faculty' => round($totalFaculty, 1),
                'total_students' => round($students, 1),
                'student_ntt_ratio' => $ntt > 0 ? round($students / $ntt, 1) : null,
                'student_faculty_ratio' => $totalFaculty > 0 ? round($students / $totalFaculty, 1) : null,
            ];

            if ($year === $endYear) {
                break;
            }

            $transitionedAssistant =
                ($assistant * self::TRANSITION_MATRIX['Assistant']['Assistant']) +
                ($associate * self::TRANSITION_MATRIX['Associate']['Assistant']) +
                ($full * self::TRANSITION_MATRIX['Full']['Assistant']);

            $transitionedAssociate =
                ($assistant * self::TRANSITION_MATRIX['Assistant']['Associate']) +
                ($associate * self::TRANSITION_MATRIX['Associate']['Associate']) +
                ($full * self::TRANSITION_MATRIX['Full']['Associate']);

            $transitionedFull =
                ($assistant * self::TRANSITION_MATRIX['Assistant']['Full']) +
                ($associate * self::TRANSITION_MATRIX['Associate']['Full']) +
                ($full * self::TRANSITION_MATRIX['Full']['Full']);

            $assistantExits = $assistant * self::TRANSITION_MATRIX['Assistant']['Exit'];
            $associateExits = $associate * self::TRANSITION_MATRIX['Associate']['Exit'];
            $fullExits = $full * self::TRANSITION_MATRIX['Full']['Exit'];

            $totalExits = $assistantExits + $associateExits + $fullExits;

            $nttExits = $totalExits * $selected['main']['ntt_replacement_probability'];
            $tenureExits = $totalExits * (1 - $selected['main']['ntt_replacement_probability']);

            $ntt += $nttExits * $selected['main']['ntt_per_tenure_exit'];

            $hirePattern = self::HIRE_PROBS[$selected['main']['hiring_model']];
            $assistantHires = $hirePattern['Assistant'] * $tenureExits;
            $associateHires = $hirePattern['Associate'] * $tenureExits;
            $fullHires = $hirePattern['Full'] * $tenureExits;

            $assistant = $transitionedAssistant + $assistantHires;
            $associate = $transitionedAssociate + $associateHires;
            $full = $transitionedFull + $fullHires;

            $students *= (1 + $selected['main']['student_growth_rate']);
        }

        return $rows;
    }

    private function scenarioSummary(array $selected): string
    {
        $hiring = ucfirst($selected['main']['hiring_model']);
        $routedShare = $this->formatPercent($selected['main']['ntt_replacement_probability']);
        $nttPerExit = rtrim(rtrim(number_format((float) $selected['main']['ntt_per_tenure_exit'], 2), '0'), '.');
        $growth = $this->formatPercent($selected['main']['student_growth_rate'], true);

        return 'Hiring pattern: ' . $hiring . '. Share of tenure-system exits routed to NTT capacity: ' . $routedShare . '. NTT faculty added per tenure-system exit: ' . $nttPerExit . '. Student growth rate: ' . $growth . '.';
    }

    private function controlChoices(): array
    {
        return [
            'hiring_model' => [
                ['label' => 'Historical', 'value' => 'historical'],
                ['label' => 'Recent', 'value' => 'recent'],
            ],
            'ntt_replacement_probability' => [
                ['label' => '0%', 'value' => 0.0],
                ['label' => '25%', 'value' => 0.25],
                ['label' => '50%', 'value' => 0.5],
                ['label' => '75%', 'value' => 0.75],
                ['label' => '100%', 'value' => 1.0],
            ],
            'ntt_per_tenure_exit' => [
                ['label' => '0', 'value' => 0.0],
                ['label' => '0.25', 'value' => 0.25],
                ['label' => '0.5', 'value' => 0.5],
                ['label' => '0.75', 'value' => 0.75],
                ['label' => '1.0', 'value' => 1.0],
            ],
            'student_growth_rate' => [
                ['label' => '-1%', 'value' => -0.01],
                ['label' => '0%', 'value' => 0.0],
                ['label' => '+1%', 'value' => 0.01],
                ['label' => '+2%', 'value' => 0.02],
            ],
        ];
    }

    private function advancedAssumptions(array $selected): array
    {
        return [
            'transitionMatrix' => self::TRANSITION_MATRIX,
            'hiringProbabilities' => self::HIRE_PROBS,
            'baseline' => [
                'Assistant' => round((float) $selected['baseline']['Assistant'], 1),
                'Associate' => round((float) $selected['baseline']['Associate'], 1),
                'Full' => round((float) $selected['baseline']['Full'], 1),
                'NTT' => round((float) $selected['baseline']['NTT'], 1),
                'Students' => round((float) $selected['baseline']['Students'], 1),
            ],
            'settings' => [
                'start_year' => (int) $selected['settings']['start_year'],
                'years_forward' => (int) $selected['settings']['years_forward'],
            ],
            'notes' => [
                'ntt_replacement_probability' => 'This is the share of tenure-system exits routed to NTT capacity, not the number of NTT hires per exit.',
                'ntt_per_tenure_exit' => 'This is the number of NTT faculty added per NTT-routed tenure-system exit.',
            ],
        ];
    }

    private function summaryCards(array $latest): array
    {
        return [
            ['label' => 'Assistant', 'value' => $this->formatValue($latest['assistant'], 1)],
            ['label' => 'Associate', 'value' => $this->formatValue($latest['associate'], 1)],
            ['label' => 'Full', 'value' => $this->formatValue($latest['full'], 1)],
            ['label' => 'Tenure-System', 'value' => $this->formatValue($latest['tenure_system'], 1)],
            ['label' => 'NTT', 'value' => $this->formatValue($latest['ntt'], 1)],
            ['label' => 'Student / Faculty Ratio', 'value' => $this->formatValue($latest['student_faculty_ratio'], 1)],
        ];
    }

    private function interpretation(array $first, array $last): array
    {
        $messages = [];

        $tenureChange = $this->percentChange($first['tenure_system'], $last['tenure_system']);
        $nttChange = $this->percentChange($first['ntt'], $last['ntt']);
        $ratioChange = (float) ($last['student_faculty_ratio'] ?? 0.0) - (float) ($first['student_faculty_ratio'] ?? 0.0);

        if ($tenureChange < -5) {
            $messages[] = 'Tenure-system faculty declines over the modeled horizon.';
        } elseif ($tenureChange > 5) {
            $messages[] = 'Tenure-system faculty grows over the modeled horizon.';
        } else {
            $messages[] = 'Tenure-system faculty remains comparatively stable.';
        }

        if ($nttChange > 5) {
            $messages[] = 'NTT faculty grows as exits are routed into NTT capacity.';
        } elseif ($nttChange < -5) {
            $messages[] = 'NTT faculty declines relative to baseline.';
        } else {
            $messages[] = 'NTT faculty remains close to baseline levels.';
        }

        if ($ratioChange > 0.05) {
            $messages[] = 'Student/faculty ratio rises, indicating higher instructional pressure.';
        } elseif ($ratioChange < -0.05) {
            $messages[] = 'Student/faculty ratio falls, indicating lower instructional pressure.';
        } else {
            $messages[] = 'Student/faculty ratio stays near baseline.';
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
            $this->comparisonRow('Students', $first['total_students'], $last['total_students'], 1),
            $this->comparisonRow('Students / NTT', $first['student_ntt_ratio'], $last['student_ntt_ratio'], 1),
            $this->comparisonRow('Student / Faculty Ratio', $first['student_faculty_ratio'], $last['student_faculty_ratio'], 1),
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
                $this->series($rows, 'Tenure-System', 'tenure_system', '#0f766e'),
                $this->series($rows, 'NTT', 'ntt', '#f97316'),
            ],
            'pipeline' => [
                $this->series($rows, 'Assistant', 'assistant', '#2563eb'),
                $this->series($rows, 'Associate', 'associate', '#16a34a'),
                $this->series($rows, 'Full', 'full', '#dc2626'),
            ],
            'ratios' => [
                $this->series($rows, 'Student / Faculty Ratio', 'student_faculty_ratio', '#0ea5e9'),
                $this->series($rows, 'Students / NTT', 'student_ntt_ratio', '#a16207'),
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

    private function formatValue(float|int|null $value, int $decimals = 1): string
    {
        if ($value === null) {
            return '—';
        }

        return number_format((float) $value, $decimals);
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

    private function formatPercent(float|int $value, bool $signed = false): string
    {
        $value = (float) $value * 100;
        $prefix = $signed && $value > 0 ? '+' : '';

        return $prefix . rtrim(rtrim(number_format($value, 1), '0'), '.') . '%';
    }

    private function percentChange(float|int|null $baseline, float|int|null $latest): float
    {
        if ($baseline === null || (float) $baseline === 0.0) {
            return 0.0;
        }

        return (((float) $latest - (float) $baseline) / (float) $baseline) * 100;
    }
}
