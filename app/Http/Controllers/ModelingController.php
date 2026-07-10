<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ModelingController extends Controller
{
    private const DEFAULTS = [
        'ntt_per_tt_loss' => 0.3,
        'student_growth_rate' => 0.0,
        'replacement_rate' => 0.8,
    ];

    public function index(Request $request)
    {
        $parameterOptions = [
            'ntt_per_tt_loss' => collect([0.2, 0.25, 0.3, 0.35, 0.4, 0.45, 0.5]),
            'student_growth_rate' => collect([-0.01, -0.005, 0.0, 0.005, 0.01, 0.015, 0.02]),
            'replacement_rate' => collect([0.0, 0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1.0, 1.1]),
        ];
        $selected = [
            'ntt_per_tt_loss' => $this->selectedValue($request->query('ntt_per_tt_loss'), $parameterOptions['ntt_per_tt_loss'], self::DEFAULTS['ntt_per_tt_loss']),
            'student_growth_rate' => $this->selectedValue($request->query('student_growth_rate'), $parameterOptions['student_growth_rate'], self::DEFAULTS['student_growth_rate']),
            'replacement_rate' => $this->selectedValue($request->query('replacement_rate'), $parameterOptions['replacement_rate'], self::DEFAULTS['replacement_rate']),
        ];
        $scenarioRows = collect($this->generateRows($selected))->sortBy('year')->values();
        $latest = $scenarioRows->last();

        return view('modeling.index', [
            'parameterOptions' => $parameterOptions,
            'selected' => $selected,
            'rows' => $scenarioRows,
            'latest' => $latest,
            'cards' => $latest ? $this->cards($latest) : [],
            'chartData' => $this->chartData($scenarioRows),
        ]);
    }

    private function selectedValue(mixed $requested, $options, float $default): float
    {
        if ($requested !== null) {
            $normalized = is_numeric($requested) ? (float) $requested : $requested;
            if ($options->contains($normalized)) {
                return $normalized;
            }
        }

        return $default;
    }

    private function cards(array $latest): array
    {
        return [
            ['label' => 'Total Faculty FTE', 'value' => $this->formatNumber($latest['total_faculty'], 1)],
            ['label' => 'Total Students FTE', 'value' => $this->formatNumber($latest['total_students'], 0)],
            ['label' => 'NTT FTE', 'value' => $this->formatNumber($latest['ntt'], 1)],
            ['label' => 'S/F Ratio', 'value' => $this->formatNumber($latest['student_faculty_ratio'], 2)],
        ];
    }

    private function chartData($rows): array
    {
        return [
            'labels' => $rows->pluck('year')->values()->toArray(),
            'faculty' => [
                $this->series($rows, 'Assistant FTE', 'assistant', '#0d6efd'),
                $this->series($rows, 'Associate FTE', 'associate', '#198754'),
                $this->series($rows, 'Full Professor FTE', 'full', '#7c3aed'),
                $this->series($rows, 'NTT FTE', 'ntt', '#dc3545'),
            ],
            'ratios' => [
                $this->series($rows, 'S/NTT Ratio', 'student_ntt_ratio', '#b45309'),
                $this->series($rows, 'S/F Ratio', 'student_faculty_ratio', '#0891b2'),
            ],
        ];
    }

    private function series($rows, string $label, string $column, string $color): array
    {
        return [
            'label' => $label,
            'data' => $rows->pluck($column)->values()->toArray(),
            'borderColor' => $color,
            'backgroundColor' => 'transparent',
            'tension' => 0.25,
            'fill' => false,
        ];
    }

    private function formatNumber(float|int $value, int $decimals): string
    {
        return number_format($value, $decimals);
    }

    private function generateRows(array $selected): array
    {
        $rows = [];
        $nttPerTenureLoss = $selected['ntt_per_tt_loss'];
        $studentGrowthRate = $selected['student_growth_rate'];
        $replacementRate = $selected['replacement_rate'];

        foreach (range(2026, 2036) as $year) {
            $elapsed = $year - 2026;
            $tenureSystemLossPressure = $elapsed * max(0, 1.0 - $replacementRate) * 12;
            $replacementGrowthPressure = $elapsed * max(0, $replacementRate - 1.0) * 5;

            $assistant = $this->roundFte(max(0, 100 - ($tenureSystemLossPressure * 0.55) + ($replacementGrowthPressure * 0.9)));
            $associate = $this->roundFte(max(0, 100 - ($tenureSystemLossPressure * 0.34) + ($replacementGrowthPressure * 0.5)));
            $full = $this->roundFte(max(0, 100 - ($tenureSystemLossPressure * 0.44) + ($replacementGrowthPressure * 0.35)));
            $ntt = $this->roundFte(max(0, 150 + ($tenureSystemLossPressure * $nttPerTenureLoss * 2.5) - ($replacementGrowthPressure * 0.7)));
            $totalStudents = $this->roundFte(3000 * ((1 + $studentGrowthRate) ** $elapsed));
            $totalFaculty = $this->roundFte($assistant + $associate + $full + $ntt);

            $rows[] = [
                'ntt_per_tt_loss' => $nttPerTenureLoss,
                'student_growth_rate' => $studentGrowthRate,
                'replacement_rate' => $replacementRate,
                'year' => $year,
                'assistant' => $assistant,
                'associate' => $associate,
                'full' => $full,
                'ntt' => $ntt,
                'total_faculty' => $totalFaculty,
                'total_students' => $totalStudents,
                'student_ntt_ratio' => $this->roundRatio($totalStudents / max($ntt, 0.1)),
                'student_faculty_ratio' => $this->roundRatio($totalStudents / max($totalFaculty, 0.1)),
            ];
        }

        return $rows;
    }

    private function roundFte(float $value): float
    {
        return round($value, 1);
    }

    private function roundRatio(float $value): float
    {
        return round($value, 2);
    }
}