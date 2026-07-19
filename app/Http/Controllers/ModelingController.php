<?php

namespace App\Http\Controllers;

use App\Models\ForecastingOutput;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ModelingController extends Controller
{
    public function index(Request $request)
    {
        if (! Schema::hasTable('forecasting_outputs') || ForecastingOutput::count() === 0) {
            return view('modeling.index', [
                'parameterOptions' => [],
                'selected'         => [],
                'rows'             => collect(),
                'latest'           => null,
                'cards'            => [],
                'chartData'        => ['labels' => [], 'faculty' => [], 'ratios' => []],
            ]);
        }

        $parameterOptions = [
            'ntt_per_tt_loss'    => ForecastingOutput::distinct()->orderBy('ntt_per_tt_loss')->pluck('ntt_per_tt_loss'),
            'student_growth_rate' => ForecastingOutput::distinct()->orderBy('student_growth_rate')->pluck('student_growth_rate'),
            'replacement_rate'   => ForecastingOutput::distinct()->orderBy('replacement_rate')->pluck('replacement_rate'),
        ];

        $selected = [
            'ntt_per_tt_loss'    => $this->selectedValue($request->query('ntt_per_tt_loss'), $parameterOptions['ntt_per_tt_loss']),
            'student_growth_rate' => $this->selectedValue($request->query('student_growth_rate'), $parameterOptions['student_growth_rate']),
            'replacement_rate'   => $this->selectedValue($request->query('replacement_rate'), $parameterOptions['replacement_rate']),
        ];

        $outputs = ForecastingOutput::where('ntt_per_tt_loss', $selected['ntt_per_tt_loss'])
            ->where('student_growth_rate', $selected['student_growth_rate'])
            ->where('replacement_rate', $selected['replacement_rate'])
            ->orderBy('year')
            ->get();

        $rows = $outputs->map(fn($r) => [
            'ntt_per_tt_loss'      => (float) $r->ntt_per_tt_loss,
            'student_growth_rate'  => (float) $r->student_growth_rate,
            'replacement_rate'     => (float) $r->replacement_rate,
            'year'                 => (int) $r->year,
            'assistant'            => (float) $r->assistant,
            'associate'            => (float) $r->associate,
            'full'                 => (float) $r->full,
            'ntt'                  => (float) $r->ntt,
            'total_faculty'        => (float) $r->total_faculty,
            'total_students'       => (float) $r->total_students,
            'student_ntt_ratio'    => (float) $r->student_ntt_ratio,
            'student_faculty_ratio' => (float) $r->student_faculty_ratio,
        ])->values();

        $latest = $rows->last();

        return view('modeling.index', [
            'parameterOptions' => $parameterOptions,
            'selected'         => $selected,
            'rows'             => $rows,
            'latest'           => $latest,
            'cards'            => $latest ? $this->cards($latest) : [],
            'chartData'        => $this->chartData($rows),
        ]);
    }

    private function selectedValue(mixed $requested, $options): float
    {
        if ($requested !== null && is_numeric($requested)) {
            $cast = (float) $requested;
            if ($options->contains(fn($v) => abs((float) $v - $cast) < 1e-9)) {
                return $cast;
            }
        }

        return (float) ($options->first() ?? 0);
    }

    private function cards(array $latest): array
    {
        return [
            ['label' => 'Total Faculty',       'value' => number_format($latest['total_faculty'], 1)],
            ['label' => 'Total Students',       'value' => number_format($latest['total_students'], 0)],
            ['label' => 'NTT',                  'value' => number_format($latest['ntt'], 1)],
            ['label' => 'Student / Faculty Ratio', 'value' => number_format($latest['student_faculty_ratio'], 2)],
        ];
    }

    private function chartData($rows): array
    {
        return [
            'labels'  => $rows->pluck('year')->values()->toArray(),
            'faculty' => [
                $this->series($rows, 'Assistant',  'assistant',  '#0d6efd'),
                $this->series($rows, 'Associate',  'associate',  '#198754'),
                $this->series($rows, 'Full',       'full',       '#7c3aed'),
                $this->series($rows, 'NTT',        'ntt',        '#dc3545'),
            ],
            'ratios' => [
                $this->series($rows, 'Student / NTT',     'student_ntt_ratio',    '#b45309'),
                $this->series($rows, 'Student / Faculty', 'student_faculty_ratio', '#0891b2'),
            ],
        ];
    }

    private function series($rows, string $label, string $column, string $color): array
    {
        return [
            'label'           => $label,
            'data'            => $rows->pluck($column)->values()->toArray(),
            'borderColor'     => $color,
            'backgroundColor' => 'transparent',
            'tension'         => 0.25,
            'fill'            => false,
        ];
    }
}

