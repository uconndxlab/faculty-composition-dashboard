@extends('layouts.app')

@section('title', 'Modeling')

@section('content')
@php
    $selectedMain = $selected['main'];
    $firstRow = $rows[0] ?? null;
    $lastRow = $rows[count($rows) - 1] ?? null;

    $selectedHiringLabel = $selectedMain['hiring_model'] === 'historical' ? 'historical' : 'recent';
    $selectedRoutedPct = (int) round(((float) $selectedMain['ntt_replacement_probability']) * 100);
    $selectedNttPerExit = rtrim(rtrim(number_format((float) $selectedMain['ntt_per_tenure_exit'], 2), '0'), '.');
    $selectedGrowthPct = (float) $selectedMain['student_growth_rate'] * 100;
    $selectedGrowthLabel = ($selectedGrowthPct > 0 ? '+' : '') . rtrim(rtrim(number_format($selectedGrowthPct, 0), '0'), '.') . '%';

    $scenarioSentence = 'Using the ' . $selectedHiringLabel . ' hiring mix, ' .
        $selectedRoutedPct . '% of tenure-system exits are routed to NTT capacity, with ' .
        $selectedNttPerExit . ' NTT FTE added per routed exit and ' .
        ($selectedGrowthPct === 0.0 ? 'flat' : $selectedGrowthLabel) . ' student growth.';

    $comparisonByMetric = collect($comparisonRows)->keyBy('metric');

    $presets = [
        [
            'label' => 'Current path',
            'params' => [
                'hiring_model' => 'recent',
                'ntt_replacement_probability' => 0.5,
                'ntt_per_tenure_exit' => 0.25,
                'student_growth_rate' => 0.0,
            ],
        ],
        [
            'label' => 'Preserve tenure system',
            'params' => [
                'hiring_model' => 'recent',
                'ntt_replacement_probability' => 0.0,
                'ntt_per_tenure_exit' => 0.25,
                'student_growth_rate' => 0.0,
            ],
        ],
        [
            'label' => 'NTT-heavy replacement',
            'params' => [
                'hiring_model' => 'recent',
                'ntt_replacement_probability' => 0.75,
                'ntt_per_tenure_exit' => 0.5,
                'student_growth_rate' => 0.0,
            ],
        ],
        [
            'label' => 'Growth pressure',
            'params' => [
                'hiring_model' => 'recent',
                'ntt_replacement_probability' => 0.5,
                'ntt_per_tenure_exit' => 0.25,
                'student_growth_rate' => 0.01,
            ],
        ],
    ];

    $isPresetActive = function (array $params) use ($selectedMain): bool {
        return $selectedMain['hiring_model'] === $params['hiring_model']
            && abs((float) $selectedMain['ntt_replacement_probability'] - (float) $params['ntt_replacement_probability']) < 1e-9
            && abs((float) $selectedMain['ntt_per_tenure_exit'] - (float) $params['ntt_per_tenure_exit']) < 1e-9
            && abs((float) $selectedMain['student_growth_rate'] - (float) $params['student_growth_rate']) < 1e-9;
    };

    $outcomeCards = [
        ['label' => 'Tenure-System Faculty', 'metric' => 'Tenure-System', 'key' => 'tenure_system', 'decimals' => 1],
        ['label' => 'Assistant Professors', 'metric' => 'Assistant', 'key' => 'assistant', 'decimals' => 1],
        ['label' => 'NTT Faculty', 'metric' => 'NTT', 'key' => 'ntt', 'decimals' => 1],
        ['label' => 'Total Faculty', 'metric' => 'Total Faculty', 'key' => 'total_faculty', 'decimals' => 1],
        ['label' => 'Student / Faculty Ratio', 'metric' => 'Student / Faculty Ratio', 'key' => 'student_faculty_ratio', 'decimals' => 1],
    ];

    $tenureDelta = $firstRow && $lastRow ? ((float) $lastRow['tenure_system'] - (float) $firstRow['tenure_system']) : 0.0;
    $nttDelta = $firstRow && $lastRow ? ((float) $lastRow['ntt'] - (float) $firstRow['ntt']) : 0.0;
    $ratioDelta = $firstRow && $lastRow ? ((float) $lastRow['student_faculty_ratio'] - (float) $firstRow['student_faculty_ratio']) : 0.0;

    $tenurePhrase = $tenureDelta < -0.1 ? 'tenure-system faculty decline' : ($tenureDelta > 0.1 ? 'tenure-system faculty grow' : 'tenure-system faculty remain near baseline');
    $nttPhrase = $nttDelta < -0.1 ? 'NTT capacity declines' : ($nttDelta > 0.1 ? 'NTT capacity grows' : 'NTT capacity remains near baseline');
    $ratioPhrase = $ratioDelta > 0.05 ? 'Student/faculty ratio rises, indicating higher instructional pressure.' : ($ratioDelta < -0.05 ? 'Student/faculty ratio falls, indicating lower instructional pressure.' : 'Student/faculty ratio remains near baseline.');

    $interpretationText = 'Under this scenario, ' . $tenurePhrase . ' while ' . $nttPhrase . '. ' . $ratioPhrase;
@endphp

<div class="context-workspace peer-workspace modeling-workspace">
    <aside class="context-sidebar peer-sidebar modeling-sidebar" aria-label="Modeling controls">
        <button class="sidebar-collapse-toggle context-sidebar-toggle" type="button" data-context-sidebar-toggle aria-label="Collapse modeling controls">
            ‹
        </button>
        <div class="context-sidebar-rail-label">Modeling</div>
        <div class="context-sidebar-content">
            <div class="context-sidebar-header">
                <div class="page-kicker">Scenario Explorer</div>
                <h1 class="page-title">Modeling</h1>
                <p class="page-subtitle">Explore how hiring, replacement, and enrollment assumptions affect future faculty composition.</p>
            </div>

            <div class="peer-sidebar-section d-none">
                <div class="peer-sidebar-heading">Starting Points</div>
                <div class="d-grid gap-2">
                    @foreach($presets as $preset)
                        @php
                            $active = $isPresetActive($preset['params']);
                        @endphp
                        <a
                            href="{{ route('modeling.index', $preset['params']) }}"
                            class="btn btn-sm {{ $active ? 'btn-primary' : 'btn-outline-secondary' }}"
                        >
                            {{ $preset['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <form method="GET" action="{{ route('modeling.index') }}" class="peer-sidebar-section">
                <div class="peer-sidebar-heading mb-2">Scenario Assumptions</div>
        <div class="py-3 border-bottom modeling-control-block">
            <div class="fw-semibold">Hiring pattern</div>
            <div class="text-muted small">Determines whether new tenure-system hires follow historical or recent rank mix.</div>
            <div class="small mt-1 text-muted">This affects the Assistant / Associate / Full distribution, not the other assumptions.</div>
            <div class="small text-muted mt-2">Selected</div>
            <div class="fw-semibold mb-2">{{ $selectedMain['hiring_model'] === 'historical' ? 'Historical hiring mix' : 'Recent hiring mix' }}</div>
            <div class="d-flex flex-wrap gap-2" role="group" aria-label="Hiring pattern options">
                @foreach($controlChoices['hiring_model'] as $choice)
                    @php
                        $optionLabel = $choice['value'] === 'historical' ? 'Historical hiring mix' : 'Recent hiring mix';
                    @endphp
                    <input
                        class="btn-check"
                        type="radio"
                        name="hiring_model"
                        id="hiring_model_{{ $loop->index }}"
                        value="{{ $choice['value'] }}"
                        {{ $selectedMain['hiring_model'] === $choice['value'] ? 'checked' : '' }}
                    >
                    <label class="btn btn-outline-secondary btn-sm modeling-option-btn" for="hiring_model_{{ $loop->index }}">{{ $optionLabel }}</label>
                @endforeach
            </div>
        </div>

        <div class="py-3 border-bottom modeling-control-block">
            <div class="fw-semibold">Where do tenure-system exits go?</div>
            <div class="text-muted small">Choose how much tenure-system loss is absorbed through NTT capacity instead of tenure-system replacement hiring.</div>
            <div class="small text-muted mt-2">Selected</div>
            <div class="fw-semibold mb-2">{{ (int) round(((float) $selectedMain['ntt_replacement_probability']) * 100) }}% NTT</div>
            <div class="d-flex flex-wrap gap-2" role="group" aria-label="Where exits go options">
                @foreach($controlChoices['ntt_replacement_probability'] as $choice)
                    <input
                        class="btn-check"
                        type="radio"
                        name="ntt_replacement_probability"
                        id="ntt_replacement_probability_{{ $loop->index }}"
                        value="{{ $choice['value'] }}"
                        {{ abs((float) $selectedMain['ntt_replacement_probability'] - (float) $choice['value']) < 1e-9 ? 'checked' : '' }}
                    >
                    <label class="btn btn-outline-secondary btn-sm modeling-option-btn" for="ntt_replacement_probability_{{ $loop->index }}">{{ $choice['label'] }} NTT</label>
                @endforeach
            </div>
        </div>

        <div class="py-3 border-bottom modeling-control-block">
            <div class="fw-semibold">NTT added per shifted exit</div>
            <div class="text-muted small">For exits routed to NTT capacity, choose how much NTT FTE is added.</div>
            <div class="small mt-1 text-muted">This only matters when some exits are routed to NTT capacity.</div>
            <div class="small text-muted mt-2">Selected</div>
            <div class="fw-semibold mb-2">{{ rtrim(rtrim(number_format((float) $selectedMain['ntt_per_tenure_exit'], 2), '0'), '.') }}</div>
            <div class="d-flex flex-wrap gap-2" role="group" aria-label="NTT added per shifted exit options">
                @foreach($controlChoices['ntt_per_tenure_exit'] as $choice)
                    <input
                        class="btn-check"
                        type="radio"
                        name="ntt_per_tenure_exit"
                        id="ntt_per_tenure_exit_{{ $loop->index }}"
                        value="{{ $choice['value'] }}"
                        {{ abs((float) $selectedMain['ntt_per_tenure_exit'] - (float) $choice['value']) < 1e-9 ? 'checked' : '' }}
                    >
                    <label class="btn btn-outline-secondary btn-sm modeling-option-btn" for="ntt_per_tenure_exit_{{ $loop->index }}">{{ $choice['label'] }}</label>
                @endforeach
            </div>
        </div>

        <div class="py-3 pb-2 modeling-control-block">
            <div class="fw-semibold">Student growth</div>
            <div class="text-muted small">Annual change in modeled student FTE.</div>
            <div class="small text-muted mt-2">Selected</div>
            <div class="fw-semibold mb-2">{{ $selectedGrowthLabel }}</div>
            <div class="d-flex flex-wrap gap-2" role="group" aria-label="Student growth options">
                @foreach($controlChoices['student_growth_rate'] as $choice)
                    <input
                        class="btn-check"
                        type="radio"
                        name="student_growth_rate"
                        id="student_growth_rate_{{ $loop->index }}"
                        value="{{ $choice['value'] }}"
                        {{ abs((float) $selectedMain['student_growth_rate'] - (float) $choice['value']) < 1e-9 ? 'checked' : '' }}
                    >
                    <label class="btn btn-outline-secondary btn-sm modeling-option-btn" for="student_growth_rate_{{ $loop->index }}">{{ $choice['label'] }}</label>
                @endforeach
            </div>
        </div>

        <div class="small text-muted mt-2">{{ $scenarioSentence }}</div>
                <div class="d-flex gap-2 mt-3">
                    <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Apply scenario</button>
                    <a href="{{ route('modeling.index') }}" class="btn btn-outline-secondary btn-sm">Reset</a>
                </div>
            </form>
        </div>
    </aside>

    <div class="context-main peer-main modeling-main">

<div class="row row-cols-1 row-cols-md-2 row-cols-xl-5 g-3 mb-4">
    @foreach($outcomeCards as $card)
        @php
            $value = $lastRow ? number_format((float) $lastRow[$card['key']], $card['decimals']) : '—';
            $delta = $comparisonByMetric[$card['metric']] ?? null;
        @endphp
        <div class="col">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="text-muted small mb-1">{{ $card['label'] }}</div>
                    <div class="fs-4 fw-semibold number-tabular">{{ $value }}</div>
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
        <p class="mb-0">{{ $interpretationText }}</p>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 pt-3">
                <h2 class="h5 mb-0">Tenure-system vs NTT over time</h2>
            </div>
            <div class="card-body pt-2">
                <canvas id="tenureVsNttChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-3">
                <h2 class="h5 mb-0">Assistant / Associate / Full over time</h2>
            </div>
            <div class="card-body pt-2">
                <canvas id="pipelineChart" height="160"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white border-0 pt-3">
                <h2 class="h5 mb-0">Student ratios</h2>
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
                    <th class="text-end">Students</th>
                    <th class="text-end">Students / NTT</th>
                    <th class="text-end">Student / Faculty Ratio</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    <tr>
                        <td class="number-tabular">{{ $row['year'] }}</td>
                        <td class="text-end number-tabular">{{ number_format((float) $row['assistant'], 1) }}</td>
                        <td class="text-end number-tabular">{{ number_format((float) $row['associate'], 1) }}</td>
                        <td class="text-end number-tabular">{{ number_format((float) $row['full'], 1) }}</td>
                        <td class="text-end number-tabular">{{ number_format((float) $row['tenure_system'], 1) }}</td>
                        <td class="text-end number-tabular">{{ number_format((float) $row['ntt'], 1) }}</td>
                        <td class="text-end number-tabular">{{ number_format((float) $row['total_faculty'], 1) }}</td>
                        <td class="text-end number-tabular">{{ number_format((float) $row['total_students'], 1) }}</td>
                        <td class="text-end number-tabular">{{ $row['student_ntt_ratio'] !== null ? number_format((float) $row['student_ntt_ratio'], 1) : '—' }}</td>
                        <td class="text-end number-tabular">{{ $row['student_faculty_ratio'] !== null ? number_format((float) $row['student_faculty_ratio'], 1) : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="accordion mb-4" id="modelAssumptionsAccordion">
    <div class="accordion-item border-0 shadow-sm">
        <h2 class="accordion-header" id="modelAssumptionsHeading">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#modelAssumptionsCollapse" aria-expanded="false" aria-controls="modelAssumptionsCollapse">
                Model assumptions
            </button>
        </h2>
        <div id="modelAssumptionsCollapse" class="accordion-collapse collapse" aria-labelledby="modelAssumptionsHeading" data-bs-parent="#modelAssumptionsAccordion">
            <div class="accordion-body">
                <p class="text-muted mb-3">These values define the scenario model's baseline and transition behavior.</p>

                <div class="mb-3">
                    <h3 class="h6 mb-2">Baseline counts</h3>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Assistant</th>
                                    <th>Associate</th>
                                    <th>Full</th>
                                    <th>NTT</th>
                                    <th>Students</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="number-tabular">{{ number_format((float) $advancedAssumptions['baseline']['Assistant'], 1) }}</td>
                                    <td class="number-tabular">{{ number_format((float) $advancedAssumptions['baseline']['Associate'], 1) }}</td>
                                    <td class="number-tabular">{{ number_format((float) $advancedAssumptions['baseline']['Full'], 1) }}</td>
                                    <td class="number-tabular">{{ number_format((float) $advancedAssumptions['baseline']['NTT'], 1) }}</td>
                                    <td class="number-tabular">{{ number_format((float) $advancedAssumptions['baseline']['Students'], 1) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="mb-3">
                    <h3 class="h6 mb-2">Hiring probabilities</h3>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Hiring pattern</th>
                                    <th class="text-end">Assistant</th>
                                    <th class="text-end">Associate</th>
                                    <th class="text-end">Full</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($advancedAssumptions['hiringProbabilities'] as $pattern => $values)
                                    <tr>
                                        <td class="text-capitalize">{{ $pattern }}</td>
                                        <td class="text-end number-tabular">{{ number_format((float) $values['Assistant'], 4) }}</td>
                                        <td class="text-end number-tabular">{{ number_format((float) $values['Associate'], 4) }}</td>
                                        <td class="text-end number-tabular">{{ number_format((float) $values['Full'], 4) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <h3 class="h6 mb-2">Transition matrix</h3>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>From \ To</th>
                                    <th class="text-end">Assistant</th>
                                    <th class="text-end">Associate</th>
                                    <th class="text-end">Full</th>
                                    <th class="text-end">Exit</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($advancedAssumptions['transitionMatrix'] as $fromRank => $row)
                                    <tr>
                                        <td>{{ $fromRank }}</td>
                                        <td class="text-end number-tabular">{{ number_format((float) $row['Assistant'], 4) }}</td>
                                        <td class="text-end number-tabular">{{ number_format((float) $row['Associate'], 4) }}</td>
                                        <td class="text-end number-tabular">{{ number_format((float) $row['Full'], 4) }}</td>
                                        <td class="text-end number-tabular">{{ number_format((float) $row['Exit'], 4) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
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
                        label: (context) => context.dataset.label + ': ' + Number(context.parsed.y).toLocaleString(undefined, { maximumFractionDigits: 1 }),
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

createModelingChart('tenureVsNttChart', modelingData.tenureVsNtt, 'Faculty');
createModelingChart('pipelineChart', modelingData.pipeline, 'Faculty');
createModelingChart('studentRatiosChart', modelingData.ratios, 'Ratio');
</script>
@endpush
@endif
