@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

@if(! $latest)
    <div class="alert alert-warning">
        No faculty summary data found for the selected institution and year. Visit <a href="{{ url('/imports') }}">Imports</a> to load data.
    </div>
@else

<div class="context-workspace">
    <aside class="context-sidebar" aria-label="Dashboard filters">
        <button class="sidebar-collapse-toggle context-sidebar-toggle" type="button" data-context-sidebar-toggle aria-label="Collapse dashboard filters">
            ‹
        </button>
        <div class="context-sidebar-rail-label">Dashboard</div>
        <div class="context-sidebar-content">
            <div class="context-sidebar-header">
                <div class="page-kicker">Institution Snapshot</div>
                <h1 class="page-title">Faculty Composition Dashboard</h1>
                <p class="page-subtitle">Compare institution-level faculty composition by year, tenure status, and rank mix.</p>
            </div>
            <div class="peer-sidebar-section">
                <div class="peer-sidebar-heading">Snapshot Filters</div>
                <p class="kpi-note">Select the institution, year, and chart view for this dashboard.</p>
            </div>
            <form method="GET" action="{{ url('/') }}">
                <div class="peer-sidebar-section">
                    <label for="institution" class="form-label">Institution</label>
                    <select id="institution" name="institution" class="form-select" data-search-select data-search-placeholder="Search institutions">
                        @foreach($institutions as $institution)
                            <option value="{{ $institution }}" {{ $institution === $selectedInstitution ? 'selected' : '' }}>{{ $institution }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="peer-sidebar-section">
                    <label for="year" class="form-label">Snapshot year</label>
                    <select id="year" name="year" class="form-select">
                        @foreach($years as $year)
                            <option value="{{ $year }}" {{ (int) $year === (int) $selectedYear ? 'selected' : '' }}>{{ $year }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary w-100 mt-3">Update Dashboard</button>
                </div>
            </form>
            <div class="peer-sidebar-section">
                <div class="form-label mb-2">Chart measure</div>
                <div class="btn-group btn-group-sm w-100" role="group" aria-label="Chart measure">
                    <button type="button" class="btn btn-outline-primary active" data-composition-mode="shares">Shares</button>
                    <button type="button" class="btn btn-outline-primary" data-composition-mode="counts">Counts</button>
                </div>
            </div>
            <div class="peer-sidebar-section">
                <div class="form-label mb-2">Benchmarks</div>
                <div class="d-grid gap-2">
                    <div class="form-check benchmark-check">
                        <input class="form-check-input dashboard-benchmark-toggle" type="checkbox" value="R1" id="dashboardBenchmarkR1" checked>
                        <label class="form-check-label fw-semibold" for="dashboardBenchmarkR1">R1 average</label>
                    </div>
                    <div class="form-check benchmark-check">
                        <input class="form-check-input dashboard-benchmark-toggle" type="checkbox" value="R2" id="dashboardBenchmarkR2">
                        <label class="form-check-label fw-semibold" for="dashboardBenchmarkR2">R2 average</label>
                    </div>
                </div>
                <p class="kpi-note mt-2">Share metrics use average; faculty counts use median.</p>
            </div>
            <div class="peer-sidebar-section">
                <label for="dashboardOutlookHorizon" class="form-label">Outlook horizon</label>
                <div class="outlook-slider-value"><span id="dashboardOutlookHorizonLabel">3 years ahead</span></div>
                <input id="dashboardOutlookHorizon" class="form-range" type="range" min="0" max="15" step="1" value="3">
                <div class="d-flex justify-content-between small text-muted number-tabular">
                    <span>Latest</span>
                    <span>+15 yrs</span>
                </div>
                <p class="kpi-note mt-2">Dashed lines extend average historical slopes. This is directional context, not a forecast model.</p>
            </div>
            <div class="peer-sidebar-section">
                <div class="form-label mb-2">Current selection</div>
                <div class="metric-definition mb-2">{{ $selectedInstitution }}</div>
                <div class="metric-chip">{{ $selectedYear }}</div>
            </div>
        </div>
    </aside>

    <div class="context-main">

<section class="card chart-panel mb-4">
    <div class="card-header card-header-brand">
        <div class="fw-semibold">Selected-Year Snapshot</div>
        <div class="small">Headline composition and rank mix cards for the active sidebar selection.</div>
    </div>
    <div class="card-body">
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
        <div class="outlook-chart-note mt-2" id="dashboardOutlookNote">Move the outlook slider to extend average slopes beyond the latest actual year.</div>
        </div>
    </div>
    <div class="card-body">
        <canvas id="compositionChart" height="80"></canvas>
        <div class="benchmark-summary d-flex flex-wrap gap-2 mt-3" id="dashboardSlopeSummary"></div>
    </div>
</section>

    </div>
</div>

@endif

@endsection

@if($latest)
@push('scripts')
<script>
const chartData = @json($chartData);
const compositionModeButtons = [...document.querySelectorAll('[data-composition-mode]')];
const dashboardOutlookHorizon = document.getElementById('dashboardOutlookHorizon');
const dashboardOutlookHorizonLabel = document.getElementById('dashboardOutlookHorizonLabel');
const dashboardOutlookNote = document.getElementById('dashboardOutlookNote');
const dashboardSlopeSummary = document.getElementById('dashboardSlopeSummary');
const dashboardBenchmarkToggleInputs = [...document.querySelectorAll('.dashboard-benchmark-toggle')];
let currentCompositionMode = 'shares';
const hiddenDashboardSeries = new Set();
const dashboardBenchmarkStyles = {
    R1: { label: 'R1 average', dash: [8, 5] },
    R2: { label: 'R2 average', dash: [2, 6] },
};

const dashboardOutlookBoundaryPlugin = {
    id: 'dashboardOutlookBoundary',
    afterDraw(chart) {
        const horizon = dashboardHorizon();
        const latestIndex = chartData.labels.length - 1;

        if (horizon <= 0 || latestIndex < 0) {
            return;
        }

        const xScale = chart.scales.x;
        const area = chart.chartArea;
        const x = xScale.getPixelForValue(latestIndex);
        const ctx = chart.ctx;

        ctx.save();
        ctx.setLineDash([4, 4]);
        ctx.strokeStyle = 'rgba(100, 116, 139, 0.55)';
        ctx.lineWidth = 1;
        ctx.beginPath();
        ctx.moveTo(x, area.top);
        ctx.lineTo(x, area.bottom);
        ctx.stroke();
        ctx.fillStyle = 'rgba(100, 116, 139, 0.9)';
        ctx.font = '700 11px Rethink Sans, sans-serif';
        ctx.fillText('Outlook begins', x + 8, area.top + 14);
        ctx.restore();
    },
};

function dashboardHorizon() {
    return Number(dashboardOutlookHorizon.value || 0);
}

function updateDashboardHorizonLabel() {
    const horizon = dashboardHorizon();
    dashboardOutlookHorizonLabel.textContent = horizon === 0
        ? 'Latest actual year only'
        : `${horizon} year${horizon === 1 ? '' : 's'} ahead`;
}

function dashboardFormatValue(value, dataset) {
    if (value === null || value === undefined || Number.isNaN(Number(value))) {
        return '—';
    }

    return dataset.yAxisID === 'percent'
        ? `${Number(value).toLocaleString(undefined, { maximumFractionDigits: 1 })}%`
        : Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 });
}

function dashboardFormatSlope(value, dataset) {
    if (value === null || value === undefined || Number.isNaN(Number(value))) {
        return '—';
    }

    const sign = Number(value) >= 0 ? '+' : '';
    return dataset.yAxisID === 'percent'
        ? `${sign}${Number(value).toLocaleString(undefined, { maximumFractionDigits: 2 })} pp/yr`
        : `${sign}${Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 })}/yr`;
}

function dashboardSlope(labels, values) {
    const points = labels
        .map((label, index) => ({ year: Number(label), value: values[index] }))
        .filter((point) => point.value !== null && point.value !== undefined && !Number.isNaN(Number(point.value)) && !Number.isNaN(point.year));

    if (points.length < 2) {
        return null;
    }

    const meanYear = points.reduce((sum, point) => sum + point.year, 0) / points.length;
    const meanValue = points.reduce((sum, point) => sum + Number(point.value), 0) / points.length;
    const denominator = points.reduce((sum, point) => sum + Math.pow(point.year - meanYear, 2), 0);

    if (denominator === 0) {
        return null;
    }

    return points.reduce((sum, point) => sum + (point.year - meanYear) * (Number(point.value) - meanValue), 0) / denominator;
}

function dashboardProjectedValue(value, slope, years, dataset) {
    if (value === null || value === undefined || slope === null || slope === undefined) {
        return null;
    }

    const projected = Number(value) + Number(slope) * years;

    return dataset.yAxisID === 'percent'
        ? Math.min(100, Math.max(0, projected))
        : Math.max(0, projected);
}

function dashboardFutureLabels(horizon) {
    const latestYear = Number(chartData.labels[chartData.labels.length - 1]);

    return Array.from({ length: horizon }, (_, index) => latestYear + index + 1);
}

function enabledDashboardBenchmarks() {
    return dashboardBenchmarkToggleInputs
        .filter((input) => input.checked)
        .map((input) => input.value);
}

function dashboardBaseDatasetsForMode(mode) {
    const selectedDatasets = (chartData.modes[mode] || chartData.modes.shares).map((dataset) => ({
        ...dataset,
        sourceLabel: 'Selected institution',
        sourceKey: 'selected',
    }));

    const benchmarkDatasets = enabledDashboardBenchmarks().flatMap((bucket) => {
        const benchmark = chartData.benchmarks?.[bucket];
        const style = dashboardBenchmarkStyles[bucket] || { label: `${bucket} average`, dash: [8, 5] };

        return (benchmark?.modes?.[mode] || []).map((dataset) => ({
            ...dataset,
            label: `${style.label} ${dataset.label}`,
            borderDash: style.dash,
            borderWidth: 2,
            pointRadius: 1.5,
            sourceLabel: style.label,
            sourceKey: bucket,
        }));
    });

    return [...selectedDatasets, ...benchmarkDatasets];
}

function dashboardDatasetsForMode(mode) {
    const horizon = dashboardHorizon();
    const actualLabels = chartData.labels;
    const futureLabels = dashboardFutureLabels(horizon);
    const actualPadding = Array(Math.max(actualLabels.length - 1, 0)).fill(null);

    return dashboardBaseDatasetsForMode(mode).flatMap((dataset) => {
        const slope = dashboardSlope(actualLabels, dataset.data);
        const latestValue = [...dataset.data].reverse().find((value) => value !== null && value !== undefined);
        const seriesKey = `${mode}:${dataset.sourceKey}:${dataset.label}`;
        const historyDataset = {
            ...dataset,
            data: [...dataset.data, ...Array(futureLabels.length).fill(null)],
            seriesKey,
        };
        const outlookValues = horizon > 0 && latestValue !== undefined && slope !== null
            ? [latestValue, ...futureLabels.map((_, index) => dashboardProjectedValue(latestValue, slope, index + 1, dataset))]
            : [];
        const outlookDataset = {
            ...dataset,
            label: `${dataset.label} outlook`,
            data: [...actualPadding, ...outlookValues],
            borderDash: [3, 6],
            borderWidth: 2,
            pointRadius: 0,
            isOutlook: true,
            seriesKey,
            slope,
        };

        return outlookDataset.data.some((value) => value !== null && value !== undefined)
            ? [historyDataset, outlookDataset]
            : [historyDataset];
    });
}

function dashboardLabels() {
    return [...chartData.labels, ...dashboardFutureLabels(dashboardHorizon())];
}

function renderDashboardSlopeSummary() {
    const datasets = dashboardBaseDatasetsForMode(currentCompositionMode);
    dashboardSlopeSummary.innerHTML = datasets.map((dataset) => {
        const slope = dashboardSlope(chartData.labels, dataset.data);
        return `<span class="benchmark-chip">${dataset.label} slope: ${dashboardFormatSlope(slope, dataset)}</span>`;
    }).join('');
}

function renderDashboardChart() {
    const horizon = dashboardHorizon();
    updateDashboardHorizonLabel();
    compositionChart.data.labels = dashboardLabels();
    compositionChart.data.datasets = dashboardDatasetsForMode(currentCompositionMode);
    compositionChart.data.datasets.forEach((dataset, index) => {
        compositionChart.setDatasetVisibility(index, !hiddenDashboardSeries.has(dataset.seriesKey));
    });
    compositionChart.options.scales.percent.display = currentCompositionMode === 'shares';
    compositionChart.options.scales.faculty.position = currentCompositionMode === 'shares' ? 'right' : 'left';
    compositionChart.options.scales.faculty.title.text = currentCompositionMode === 'shares' ? 'Total Faculty' : 'Faculty Count';
    dashboardOutlookNote.textContent = horizon > 0
        ? `Dashed lines extend average slopes ${horizon} year${horizon === 1 ? '' : 's'} beyond the latest actual year.`
        : 'Move the outlook slider to extend average slopes beyond the latest actual year.';
    renderDashboardSlopeSummary();
    compositionChart.update();
}

const compositionChart = new Chart(document.getElementById('compositionChart'), {
    type: 'line',
    data: {
        labels: [],
        datasets: [],
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: {
                position: 'top',
                onClick: (event, item, legend) => {
                    const chart = legend.chart;
                    const dataset = chart.data.datasets[item.datasetIndex];

                    if (!dataset?.seriesKey) {
                        return;
                    }

                    const shouldShow = !chart.isDatasetVisible(item.datasetIndex);
                    chart.data.datasets.forEach((candidate, index) => {
                        if (candidate.seriesKey === dataset.seriesKey) {
                            chart.setDatasetVisibility(index, shouldShow);
                        }
                    });

                    if (shouldShow) {
                        hiddenDashboardSeries.delete(dataset.seriesKey);
                    } else {
                        hiddenDashboardSeries.add(dataset.seriesKey);
                    }

                    chart.update();
                },
                labels: {
                    filter: (item, chart) => !chart.datasets[item.datasetIndex]?.isOutlook,
                },
            },
            tooltip: {
                callbacks: {
                    label: (context) => {
                        if (context.parsed.y === null || context.parsed.y === undefined) {
                            return `${context.dataset.label}: —`;
                        }
                        const value = Number(context.parsed.y).toLocaleString(undefined, { maximumFractionDigits: context.dataset.yAxisID === 'faculty' ? 0 : 1 });
                        const slope = context.dataset.isOutlook ? ` · slope ${dashboardFormatSlope(context.dataset.slope, context.dataset)}` : '';
                        const label = context.dataset.isOutlook ? context.dataset.label.replace(' outlook', '') : context.dataset.label;
                        return context.dataset.yAxisID === 'faculty'
                            ? `${label}: ${value}${slope}`
                            : `${label}: ${value}%${slope}`;
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
    plugins: [dashboardOutlookBoundaryPlugin],
});

function setCompositionMode(mode) {
    currentCompositionMode = mode;
    compositionModeButtons.forEach((button) => {
        button.classList.toggle('active', button.dataset.compositionMode === mode);
    });
    renderDashboardChart();
}

compositionModeButtons.forEach((button) => {
    button.addEventListener('click', () => setCompositionMode(button.dataset.compositionMode));
});
dashboardBenchmarkToggleInputs.forEach((input) => {
    input.addEventListener('change', () => {
        if (input.checked) {
            dashboardBenchmarkToggleInputs.forEach((otherInput) => {
                if (otherInput !== input) {
                    otherInput.checked = false;
                }
            });
        }

        renderDashboardChart();
    });
});
dashboardOutlookHorizon.addEventListener('input', renderDashboardChart);
renderDashboardChart();
</script>
@endpush
@endif
