@extends('layouts.app')

@section('title', 'Modeling')

@section('content')
@php
    $formatPercent = fn ($value) => rtrim(rtrim(number_format((float) $value * 100, 1), '0'), '.') . '%';
    $formatNumber = fn ($value, $decimals = 1) => rtrim(rtrim(number_format((float) $value, $decimals), '0'), '.');

    $comparisonByMetric = collect($comparisonRows)->keyBy('metric');
    $summaryMetricMap = [
        'Assistant Professors' => 'Assistant',
        'Tenure-System Faculty' => 'Tenure-System',
        'NTT Faculty' => 'NTT',
        'Total Faculty' => 'Total Faculty',
        'Student / Faculty Ratio' => 'Student / Faculty Ratio',
    ];

    $editableDefaultsParams = [];
    foreach ($advancedAssumptions['newHireDistribution'] as $index => $item) {
        $editableDefaultsParams['hire_dist_pct_' . $index] = round(((float) $item['value']) * 100, 3);
    }

    $currentPathPreset = collect($scenarioPresets)->first(fn ($preset) => strtolower($preset['label']) === 'current path');
    $codeDefaultsUrl = route('modeling.index', array_merge($selected['main'], $selected['advanced']));
    $resetUrl = $currentPathPreset
        ? route('modeling.index', array_merge($currentPathPreset['values'], $selected['advanced']))
        : route('modeling.index');
@endphp

<div class="mb-4">
    <h1 class="h3 mb-2">Modeling</h1>
    <p class="text-muted mb-0">This workspace shows the projected consequences of selected replacement and enrollment assumptions. It is scenario modeling, not a statistical forecast.</p>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center">
        <h2 class="h6 mb-0">Replacement distribution used</h2>
        <div class="d-flex gap-2">
            <a href="{{ $codeDefaultsUrl }}" class="btn btn-sm btn-outline-secondary">Use code defaults</a>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modelDefaultsModal">
                Set defaults
            </button>
        </div>
    </div>
    <div class="card-body pt-2">
        <div class="row g-2">
            @foreach($advancedAssumptions['newHireDistribution'] as $item)
                <div class="col-6 col-md-4 col-xl-3">
                    <div class="rounded-2 border p-2 h-100">
                        <div class="small text-muted">{{ $item['label'] }}</div>
                        <div class="fw-semibold number-tabular">{{ number_format($item['value'] * 100, 1) }}%</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<form method="GET" action="{{ route('modeling.index') }}" class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <h2 class="h5 mb-0">Scenario assumptions</h2>
    </div>

    <div class="card-body pt-2">
        @foreach($selected['advanced'] as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        @foreach($editableDefaultsParams as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach

        <div class="py-3 border-bottom">
            <div class="row g-3 align-items-center">
                <div class="col-lg-4">
                    <div class="fw-semibold">Tenure-system replacement rate</div>
                    <div class="text-muted small">Share of lost tenure-system faculty replaced with tenure-system hires.</div>
                </div>
                <div class="col-lg-2">
                    <div class="small text-muted">Selected</div>
                    <div class="fs-5 fw-semibold">{{ $formatPercent($selected['main']['replacement_rate']) }}</div>
                </div>
                <div class="col-lg-6">
                    <div class="btn-group flex-wrap" role="group" aria-label="Tenure-system replacement rate options">
                        @foreach($controlChoices['replacement_rate'] as $choice)
                            <input class="btn-check" type="radio" name="replacement_rate" id="replacement_rate_{{ $loop->index }}" value="{{ $choice['value'] }}" {{ abs((float) $selected['main']['replacement_rate'] - (float) $choice['value']) < 1e-9 ? 'checked' : '' }}>
                            <label class="btn btn-outline-secondary btn-sm" for="replacement_rate_{{ $loop->index }}">{{ $choice['label'] }}</label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="py-3 border-bottom">
            <div class="row g-3 align-items-center">
                <div class="col-lg-4">
                    <div class="fw-semibold">Student body growth rate</div>
                    <div class="text-muted small">Annual change in modeled student FTE.</div>
                </div>
                <div class="col-lg-2">
                    <div class="small text-muted">Selected</div>
                    <div class="fs-5 fw-semibold">{{ $formatPercent($selected['main']['student_growth_rate']) }}</div>
                </div>
                <div class="col-lg-6">
                    <div class="btn-group flex-wrap" role="group" aria-label="Student body growth rate options">
                        @foreach($controlChoices['student_growth_rate'] as $choice)
                            <input class="btn-check" type="radio" name="student_growth_rate" id="student_growth_rate_{{ $loop->index }}" value="{{ $choice['value'] }}" {{ abs((float) $selected['main']['student_growth_rate'] - (float) $choice['value']) < 1e-9 ? 'checked' : '' }}>
                            <label class="btn btn-outline-secondary btn-sm" for="student_growth_rate_{{ $loop->index }}">{{ $choice['label'] }}</label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="py-3">
            <div class="row g-3 align-items-center">
                <div class="col-lg-4">
                    <div class="fw-semibold">NTT student/faculty ratio target</div>
                    <div class="text-muted small">Lower values assume more non-tenure faculty are needed. Higher values assume fewer are needed.</div>
                </div>
                <div class="col-lg-2">
                    <div class="small text-muted">Selected</div>
                    <div class="fs-5 fw-semibold">{{ $formatNumber($selected['main']['ntt_student_faculty_ratio']) }}</div>
                </div>
                <div class="col-lg-6">
                    <div class="btn-group flex-wrap" role="group" aria-label="NTT student faculty ratio options">
                        @foreach($controlChoices['ntt_student_faculty_ratio'] as $choice)
                            <input class="btn-check" type="radio" name="ntt_student_faculty_ratio" id="ntt_student_faculty_ratio_{{ $loop->index }}" value="{{ $choice['value'] }}" {{ abs((float) $selected['main']['ntt_student_faculty_ratio'] - (float) $choice['value']) < 1e-9 ? 'checked' : '' }}>
                            <label class="btn btn-outline-secondary btn-sm" for="ntt_student_faculty_ratio_{{ $loop->index }}">{{ $formatNumber($choice['value']) }}</label>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card-footer bg-white border-0 pt-0 pb-3">
        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">Apply scenario</button>
            <a href="{{ $resetUrl }}" class="btn btn-outline-secondary">Reset to current path</a>
        </div>
    </div>
</form>

<div class="row row-cols-1 row-cols-md-2 row-cols-xl-5 g-3 mb-4">
    @foreach($summaryCards as $card)
        @php
            $metric = $summaryMetricMap[$card['label']] ?? null;
            $delta = $metric ? ($comparisonByMetric[$metric] ?? null) : null;
        @endphp
        <div class="col">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">{{ $card['label'] }}</div>
                    <div class="fs-4 fw-semibold number-tabular">{{ $card['value'] }}</div>
                    @if($delta)
                        <div class="small text-muted mt-1">{{ $delta['change'] }} from {{ $interpretation['baselineYear'] }} ({{ $delta['percent_change'] }})</div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <h2 class="h5 mb-0">Interpretation</h2>
    </div>
    <div class="card-body pt-2">
        @if(count($interpretation['messages']) === 1)
            <p class="mb-0">{{ $interpretation['messages'][0] }}</p>
        @else
            <ul class="mb-0">
                @foreach($interpretation['messages'] as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h2 class="h5 mb-0">Tenure-System vs NTT Faculty Over Time</h2>
            </div>
            <div class="card-body pt-2">
                <canvas id="tenureVsNttChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-3">
                <h2 class="h5 mb-0">Tenure-System Rank Pipeline</h2>
            </div>
            <div class="card-body pt-2">
                <canvas id="pipelineChart" height="160"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-3">
                <h2 class="h5 mb-0">Student Ratios</h2>
            </div>
            <div class="card-body pt-2">
                <canvas id="studentRatiosChart" height="160"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <h2 class="h5 mb-0">Change from baseline</h2>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Metric</th>
                    <th class="text-end">Baseline year value</th>
                    <th class="text-end">Final year value</th>
                    <th class="text-end">Change</th>
                    <th class="text-end">Percent change</th>
                </tr>
            </thead>
            <tbody>
                @foreach($comparisonRows as $row)
                    <tr>
                        <td>{{ $row['metric'] }}</td>
                        <td class="text-end number-tabular">{{ $row['baseline'] }}</td>
                        <td class="text-end number-tabular">{{ $row['latest'] }}</td>
                        <td class="text-end number-tabular">{{ $row['change'] }}</td>
                        <td class="text-end number-tabular">{{ $row['percent_change'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <h2 class="h5 mb-0">Year-by-year scenario modeling</h2>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Year</th>
                    <th class="text-end">Assistant</th>
                    <th class="text-end">Associate</th>
                    <th class="text-end">Full</th>
                    <th class="text-end">Tenure-System</th>
                    <th class="text-end">NTT</th>
                    <th class="text-end">Total Faculty</th>
                    <th class="text-end">Total Students</th>
                    <th class="text-end">Student / NTT</th>
                    <th class="text-end">Student / Faculty</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td class="number-tabular">{{ $row['year'] }}</td>
                        <td class="text-end number-tabular">{{ $formatNumber($row['assistant']) }}</td>
                        <td class="text-end number-tabular">{{ $formatNumber($row['associate']) }}</td>
                        <td class="text-end number-tabular">{{ $formatNumber($row['full']) }}</td>
                        <td class="text-end number-tabular">{{ $formatNumber($row['tenure_system']) }}</td>
                        <td class="text-end number-tabular">{{ $formatNumber($row['ntt']) }}</td>
                        <td class="text-end number-tabular">{{ $formatNumber($row['total_faculty']) }}</td>
                        <td class="text-end number-tabular">{{ number_format((float) $row['total_students'], 0) }}</td>
                        <td class="text-end number-tabular">{{ $row['student_ntt_ratio'] !== null ? number_format((float) $row['student_ntt_ratio'], 2) : '—' }}</td>
                        <td class="text-end number-tabular">{{ $row['student_faculty_ratio'] !== null ? number_format((float) $row['student_faculty_ratio'], 2) : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="modelDefaultsModal" tabindex="-1" aria-labelledby="modelDefaultsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <form method="GET" action="{{ route('modeling.index') }}">
                <div class="modal-header">
                    <h2 class="modal-title h5" id="modelDefaultsModalLabel">Set model defaults</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @foreach($selected['main'] as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach
                    @foreach($selected['advanced'] as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach

                    <div class="mb-4">
                        <h3 class="h6 mb-2">Replacement distribution (% of replacement hires)</h3>
                        <div class="row g-2">
                            @foreach($advancedAssumptions['newHireDistribution'] as $index => $item)
                                <div class="col-md-6 col-xl-3">
                                    <label class="form-label small mb-1" for="hire_dist_pct_{{ $index }}">{{ $item['label'] }}</label>
                                    <div class="input-group input-group-sm">
                                        <input
                                            id="hire_dist_pct_{{ $index }}"
                                            name="hire_dist_pct_{{ $index }}"
                                            type="number"
                                            class="form-control"
                                            value="{{ number_format($item['value'] * 100, 3, '.', '') }}"
                                            min="0"
                                            step="0.1"
                                        >
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="small text-muted mt-2">Values are normalized to sum to 100% when applied.</div>
                    </div>

                    <div>
                        <h3 class="h6 mb-2">Transition matrix (annual movement shares)</h3>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th class="text-nowrap">From \ To</th>
                                        @foreach($advancedAssumptions['bucketLabels'] as $label)
                                            <th class="text-nowrap small">{{ $label }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($advancedAssumptions['transitionMatrix'] as $rowIndex => $row)
                                        <tr>
                                            <th class="small text-nowrap">{{ $advancedAssumptions['bucketLabels'][$rowIndex] }}</th>
                                            @foreach($row as $columnIndex => $value)
                                                <td>
                                                    <span class="small number-tabular">{{ number_format((float) $value, 4) }}</span>
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="small text-muted">Read-only values sourced from code. Update the TRANSITION_MATRIX constant to change these assumptions.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Apply defaults</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="accordion mb-4" id="advancedAssumptionsAccordion">
    <div class="accordion-item border-0 shadow-sm">
        <h2 class="accordion-header" id="advancedAssumptionsHeading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#advancedAssumptionsCollapse" aria-expanded="false" aria-controls="advancedAssumptionsCollapse">
                Advanced assumptions
            </button>
        </h2>
        <div id="advancedAssumptionsCollapse" class="accordion-collapse collapse" aria-labelledby="advancedAssumptionsHeading" data-bs-parent="#advancedAssumptionsAccordion">
            <div class="accordion-body">
                <p class="text-muted mb-3">These assumptions are visible so the model is transparent. Some transition assumptions are temporary until IR provides/blesses final values.</p>

                <form method="GET" action="{{ route('modeling.index') }}">
                    @foreach($selected['main'] as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach
                    @foreach($editableDefaultsParams as $name => $value)
                        <input type="hidden" name="{{ $name }}" value="{{ $value }}">
                    @endforeach

                    <div class="row g-3">
                        @foreach($advancedAssumptions['baselineInputs'] as $input)
                            <div class="col-md-4">
                                <label for="advanced_{{ $input['name'] }}" class="form-label">{{ $input['label'] }}</label>
                                <input
                                    id="advanced_{{ $input['name'] }}"
                                    name="{{ $input['name'] }}"
                                    type="number"
                                    class="form-control"
                                    value="{{ $input['value'] }}"
                                    step="{{ $input['step'] }}"
                                >
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        <h3 class="h6 mb-2">New hire distribution</h3>
                        <div class="row g-2">
                            @foreach($advancedAssumptions['newHireDistribution'] as $item)
                                <div class="col-md-6 col-xl-3">
                                    <div class="p-2 border rounded-2 h-100">
                                        <div class="small fw-semibold">{{ $item['label'] }}</div>
                                        <div class="text-muted small">{{ number_format($item['value'] * 100, 1) }}%</div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="mt-3 alert alert-warning mb-0">
                        {{ $advancedAssumptions['transitionMatrixNote'] }}
                    </div>

                    <div class="mt-3">
                        <button type="submit" class="btn btn-outline-secondary">Apply advanced assumptions</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@if(! empty($rows))
@push('scripts')
<script>
const modelingData = @json($chartData);

function createModelingChart(canvasId, datasets, yTitle) {
    new Chart(document.getElementById(canvasId), {
        type: 'line',
        data: {
            labels: modelingData.labels,
            datasets,
        },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top' },
                tooltip: {
                    callbacks: {
                        label: (context) => `${context.dataset.label}: ${Number(context.parsed.y).toLocaleString(undefined, { maximumFractionDigits: 2 })}`,
                    },
                },
            },
            scales: {
                x: { title: { display: true, text: 'Year' } },
                y: {
                    title: { display: true, text: yTitle },
                    ticks: { callback: (value) => Number(value).toLocaleString() },
                },
            },
        },
    });
}

createModelingChart('tenureVsNttChart', modelingData.tenureVsNtt, 'Faculty FTE');
createModelingChart('pipelineChart', modelingData.pipeline, 'Faculty FTE');
createModelingChart('studentRatiosChart', modelingData.ratios, 'Ratio');
</script>
@endpush
@endif
