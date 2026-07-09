@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="page-header">
    <div>
        <div class="page-kicker">Institution Snapshot</div>
        <h1 class="page-title">Faculty Composition Dashboard</h1>
        <p class="page-subtitle">Compare institution-level faculty composition by year, tenure status, and rank mix.</p>
    </div>
</div>

@if(! $latest)
    <div class="alert alert-warning">
        No faculty summary data found for the selected institution and year. Visit <a href="{{ url('/imports') }}">Imports</a> to load data.
    </div>
@else

<section class="card chart-panel mb-4">
    <div class="card-header card-header-brand">
        <div class="fw-semibold">Selected-Year Snapshot</div>
        <div class="small">Choose an institution and year to update the headline composition and rank mix cards.</div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ url('/') }}" class="control-panel row g-3 align-items-end mb-4">
            <div class="col-lg-6">
                <label for="institution" class="form-label">Institution</label>
                <select id="institution" name="institution" class="form-select" data-search-select data-search-placeholder="Search institutions">
                    @foreach($institutions as $institution)
                        <option value="{{ $institution }}" {{ $institution === $selectedInstitution ? 'selected' : '' }}>{{ $institution }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-3">
                <label for="year" class="form-label">Snapshot year</label>
                <select id="year" name="year" class="form-select">
                    @foreach($years as $year)
                        <option value="{{ $year }}" {{ (int) $year === (int) $selectedYear ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-sm-6 col-lg-3">
                <button type="submit" class="btn btn-primary w-100">Update Dashboard</button>
            </div>
        </form>

        <div class="d-flex flex-column flex-lg-row justify-content-between gap-2 mb-3">
            <div>
                <h2 class="h5 mb-1">{{ $selectedInstitution }}</h2>
                <div class="text-muted small">Faculty composition snapshot for {{ $selectedYear }}.</div>
            </div>
            <div class="text-lg-end">
                <div class="text-muted small">Selected year</div>
                <div class="fs-5 fw-semibold">{{ $selectedYear }}</div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            @foreach($snapshotCards as $card)
                <div class="col-md-6 col-xl-3">
                    <div class="kpi-card">
                        <div class="kpi-label">{{ $card['label'] }}</div>
                        <div class="kpi-value">{{ $card['value'] }}</div>
                        <p class="kpi-note">{{ $card['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mb-3">
            <h3 class="h6 mb-1">Faculty Rank Mix</h3>
            <p class="text-muted small mb-0">Title/rank composition across all tenure statuses. Associate and full professor titles can include non-tenure faculty.</p>
        </div>
        <div class="row g-3">
            @foreach($rankMixCards as $card)
                <div class="col-sm-6 col-xl-3">
                    <div class="kpi-card">
                        <div class="kpi-label">{{ $card['label'] }}</div>
                        <div class="kpi-value">{{ $card['value'] }}</div>
                        <p class="kpi-note">{{ $card['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="panel-note">
        Percentages are shares of total faculty. This view summarizes institution-level data and does not yet show department, school, or CIP-level detail.
    </div>
</section>

<section class="card chart-panel mb-4">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-3 align-items-md-center">
        <div>
        <div class="fw-semibold">{{ $selectedInstitution }} Faculty Composition Over Time</div>
        <div class="text-muted small">Toggle between share and count views for tenure-system and non-tenure faculty. Total faculty remains visible for scale.</div>
        </div>
        <div class="btn-group btn-group-sm" role="group" aria-label="Chart measure">
            <button type="button" class="btn btn-outline-primary active" data-composition-mode="shares">Shares</button>
            <button type="button" class="btn btn-outline-primary" data-composition-mode="counts">Counts</button>
        </div>
    </div>
    <div class="card-body">
        <canvas id="compositionChart" height="80"></canvas>
    </div>
</section>

@endif

@endsection

@if($latest)
@push('scripts')
<script>
const chartData = @json($chartData);
const compositionModeButtons = [...document.querySelectorAll('[data-composition-mode]')];

const compositionChart = new Chart(document.getElementById('compositionChart'), {
    type: 'line',
    data: {
        labels: chartData.labels,
        datasets: chartData.modes.shares,
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { position: 'top' },
            tooltip: {
                callbacks: {
                    label: (context) => {
                        if (context.parsed.y === null || context.parsed.y === undefined) {
                            return `${context.dataset.label}: —`;
                        }
                        const value = Number(context.parsed.y).toLocaleString(undefined, { maximumFractionDigits: context.dataset.yAxisID === 'faculty' ? 0 : 1 });
                        return context.dataset.yAxisID === 'faculty'
                            ? `${context.dataset.label}: ${value}`
                            : `${context.dataset.label}: ${value}%`;
                    },
                },
            },
        },
        scales: {
            x: {
                title: { display: true, text: 'Year' },
            },
            percent: {
                position: 'left',
                display: true,
                title: { display: true, text: 'Percent (%)' },
                ticks: { callback: (v) => v + '%' },
            },
            faculty: {
                position: 'right',
                title: { display: true, text: 'Total Faculty' },
                grid: { drawOnChartArea: false },
                ticks: { callback: (v) => Number(v).toLocaleString() },
            },
        },
    },
});

function setCompositionMode(mode) {
    compositionChart.data.datasets = chartData.modes[mode] || chartData.modes.shares;
    compositionChart.options.scales.percent.display = mode === 'shares';
    compositionChart.options.scales.faculty.position = mode === 'shares' ? 'right' : 'left';
    compositionChart.options.scales.faculty.title.text = mode === 'shares' ? 'Total Faculty' : 'Faculty Count';
    compositionModeButtons.forEach((button) => {
        button.classList.toggle('active', button.dataset.compositionMode === mode);
    });
    compositionChart.update();
}

compositionModeButtons.forEach((button) => {
    button.addEventListener('click', () => setCompositionMode(button.dataset.compositionMode));
});
</script>
@endpush
@endif
