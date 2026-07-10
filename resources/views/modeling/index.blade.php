@extends('layouts.app')

@section('title', 'Modeling')

@section('content')

@php
    $formatDecimal = fn($value) => rtrim(rtrim(number_format((float) $value, 2), '0'), '.');
    $formatPercent = fn($value) => rtrim(rtrim(number_format((float) $value * 100, 1), '0'), '.') . '%';
    $formatNumber = fn($value, $decimals = 1) => number_format((float) $value, $decimals);
@endphp

<div class="mb-4">
    <h1 class="h3">Modeling</h1>
    <p class="text-muted mb-0">This workspace shows the projected consequences of selected replacement and enrollment assumptions using sample IR model output. It is scenario modeling, not a statistical forecast.</p>
</div>

<form method="GET" action="{{ route('modeling.index') }}" class="card mb-4">
    <div class="card-header">
        <div class="fw-semibold">Scenario Assumptions</div>
    </div>
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="ntt_per_tt_loss" class="form-label">NTT added per Tenure-System faculty lost</label>
                <select id="ntt_per_tt_loss" name="ntt_per_tt_loss" class="form-select">
                    @foreach($parameterOptions['ntt_per_tt_loss'] as $value)
                        <option value="{{ $value }}" {{ (float) $selected['ntt_per_tt_loss'] === (float) $value ? 'selected' : '' }}>{{ $formatDecimal($value) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="student_growth_rate" class="form-label">Student body growth rate</label>
                <select id="student_growth_rate" name="student_growth_rate" class="form-select">
                    @foreach($parameterOptions['student_growth_rate'] as $value)
                        <option value="{{ $value }}" {{ (float) $selected['student_growth_rate'] === (float) $value ? 'selected' : '' }}>{{ $formatPercent($value) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label for="replacement_rate" class="form-label">Tenure-System replacement rate</label>
                <select id="replacement_rate" name="replacement_rate" class="form-select">
                    @foreach($parameterOptions['replacement_rate'] as $value)
                        <option value="{{ $value }}" {{ (float) $selected['replacement_rate'] === (float) $value ? 'selected' : '' }}>{{ $formatPercent($value) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
    <div class="card-footer bg-white">
        <button type="submit" class="btn btn-primary">Update Model</button>
    </div>
</form>

@if($rows->isEmpty())
    <div class="alert alert-warning">No rows found for the selected assumptions.</div>
@else
    <div class="row g-3 mb-4">
        @foreach($cards as $card)
            <div class="col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-muted small text-uppercase fw-semibold mb-1">{{ $card['label'] }}</div>
                        <div class="fs-4 fw-bold number-tabular">{{ $card['value'] }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-7">
            <div class="card h-100">
                <div class="card-header">
                    <div class="fw-semibold">Faculty Composition by Year</div>
                </div>
                <div class="card-body">
                    <canvas id="facultyCompositionChart" height="130"></canvas>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="card h-100">
                <div class="card-header">
                    <div class="fw-semibold">Student Ratios by Year</div>
                </div>
                <div class="card-body">
                    <canvas id="studentRatiosChart" height="180"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <div class="fw-semibold">Results</div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Year</th>
                        <th class="text-end">Assistant</th>
                        <th class="text-end">Associate</th>
                        <th class="text-end">Full</th>
                        <th class="text-end">NTT</th>
                        <th class="text-end">Total Faculty</th>
                        <th class="text-end">Total Students</th>
                        <th class="text-end">S/NTT Ratio</th>
                        <th class="text-end">S/F Ratio</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rows as $row)
                        <tr>
                            <td class="number-tabular">{{ $row['year'] }}</td>
                            <td class="text-end number-tabular">{{ $formatNumber($row['assistant']) }}</td>
                            <td class="text-end number-tabular">{{ $formatNumber($row['associate']) }}</td>
                            <td class="text-end number-tabular">{{ $formatNumber($row['full']) }}</td>
                            <td class="text-end number-tabular">{{ $formatNumber($row['ntt']) }}</td>
                            <td class="text-end number-tabular">{{ $formatNumber($row['total_faculty']) }}</td>
                            <td class="text-end number-tabular">{{ $formatNumber($row['total_students'], 0) }}</td>
                            <td class="text-end number-tabular">{{ $formatNumber($row['student_ntt_ratio'], 2) }}</td>
                            <td class="text-end number-tabular">{{ $formatNumber($row['student_faculty_ratio'], 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection

@if($rows->isNotEmpty())
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

createModelingChart('facultyCompositionChart', modelingData.faculty, 'Faculty FTE');
createModelingChart('studentRatiosChart', modelingData.ratios, 'Ratio');
</script>
@endpush
@endif