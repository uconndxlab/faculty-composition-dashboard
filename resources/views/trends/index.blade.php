@extends('layouts.app')

@section('title', 'Peer Trends')

@section('content')

@if($trends->isEmpty())
    <div class="alert alert-warning">
        No trend data found for University of Connecticut. Visit <a href="{{ url('/imports') }}">Imports</a> to load data.
    </div>
@else

@php
    function fmtVal(string $metric, mixed $v): string {
        if ($v === null) return '—';
        if (str_starts_with($metric, 'pct_')) {
            return number_format((float)$v * 100, 2) . '%';
        }
        return number_format((float)$v, 0);
    }

    function fmtAbs(string $metric, mixed $v): string {
        if ($v === null) return '—';
        $sign = (float)$v >= 0 ? '+' : '';
        if (str_starts_with($metric, 'pct_')) {
            return $sign . number_format((float)$v * 100, 2) . ' pp';
        }
        return $sign . number_format((float)$v, 0);
    }

    function fmtPct(mixed $v): string {
        if ($v === null) return '—';
        $sign = (float)$v >= 0 ? '+' : '';
        return $sign . number_format((float)$v * 100, 1) . '%';
    }

    function fmtSlope(string $metric, mixed $v): string {
        if ($v === null) return '—';
        $sign = (float)$v >= 0 ? '+' : '';
        if (str_starts_with($metric, 'pct_')) {
            return $sign . number_format((float)$v * 100, 3) . ' pp/yr';
        }
        return $sign . number_format((float)$v, 1) . '/yr';
    }
@endphp

@if(empty($peerTrendData['series']))
    <div class="alert alert-warning">
        Peer trend data is not available yet. Confirm that faculty summaries, similarity rankings, and trajectory similarities have been imported.
    </div>
@endif

<div class="context-workspace peer-workspace">
    <aside class="context-sidebar peer-sidebar" aria-label="Peer trends controls">
        <button class="sidebar-collapse-toggle context-sidebar-toggle" type="button" data-context-sidebar-toggle aria-label="Collapse peer trends controls">
            ‹
        </button>
        <div class="context-sidebar-rail-label">Trends</div>
        <div class="context-sidebar-content">
        <div class="context-sidebar-header">
            <div class="page-kicker">Peer Comparison Workspace</div>
            <h1 class="page-title">Peer Trends</h1>
            <p class="page-subtitle">Compare where UConn is now, how it is moving, and which institutions make useful current or trajectory peers.</p>
            @if(! empty($peerTrendData['latestYear']))
                <span class="metric-chip">Latest year {{ $peerTrendData['latestYear'] }}</span>
            @endif
        </div>
        <div class="peer-sidebar-section">
            <div class="peer-sidebar-heading">Comparison Setup</div>
            <p class="kpi-note">UConn is fixed. Add institutions and benchmarks to compare against it.</p>
        </div>
        <div class="peer-sidebar-section">
            <label for="comparisonMode" class="form-label">Institution source</label>
            <select id="comparisonMode" class="form-select">
                <option value="custom">Choose institutions</option>
                <option value="ranked">Use ranked set</option>
            </select>
        </div>
        <div class="peer-sidebar-section d-none" id="rankedSetControl">
            <label for="workspaceSet" class="form-label">Ranked set</label>
            <select id="workspaceSet" class="form-select"></select>
        </div>
        <div class="peer-sidebar-section d-none" id="rankedFocusControl">
            <label for="workspacePeer" class="form-label">Focus institution</label>
            <select id="workspacePeer" class="form-select" data-search-select data-search-placeholder="Search focus institutions"></select>
        </div>
        <div class="peer-sidebar-section" id="customControls">
            <div class="form-label">Compare institutions</div>
            @for($i = 1; $i <= 4; $i++)
                <div class="mb-2">
                    <label for="customPeer{{ $i }}" class="visually-hidden">Custom institution {{ $i }}</label>
                    <select id="customPeer{{ $i }}" class="form-select custom-peer-select" data-search-select data-search-placeholder="Search institutions"></select>
                </div>
            @endfor
            <label for="customFocusPeer" class="form-label mt-2">Primary comparison</label>
            <select id="customFocusPeer" class="form-select" data-search-select data-search-placeholder="Search selected peers"></select>
        </div>
        <div class="peer-sidebar-section">
            <div class="form-label mb-2">Benchmarks</div>
            <div class="d-grid gap-2">
                <div class="form-check benchmark-check">
                    <input class="form-check-input benchmark-toggle" type="checkbox" value="R1" id="benchmarkR1" checked>
                    <label class="form-check-label fw-semibold" for="benchmarkR1">R1 average</label>
                </div>
                <div class="form-check benchmark-check">
                    <input class="form-check-input benchmark-toggle" type="checkbox" value="R2" id="benchmarkR2">
                    <label class="form-check-label fw-semibold" for="benchmarkR2">R2 average</label>
                </div>
            </div>
            <p class="kpi-note mt-2">Share metrics use average; faculty counts use median.</p>
        </div>
        <div class="peer-sidebar-section">
            <label for="outlookHorizon" class="form-label">Outlook horizon</label>
            <div class="outlook-slider-value"><span id="outlookHorizonLabel">3 years ahead</span></div>
            <input id="outlookHorizon" class="form-range" type="range" min="0" max="5" step="1" value="3">
            <div class="d-flex justify-content-between small text-muted number-tabular">
                <span>2025</span>
                <span>+5 yrs</span>
            </div>
            <p class="kpi-note mt-2">Outlook extends the average yearly trend. It is not a forecast model.</p>
        </div>
        <div class="peer-sidebar-section">
            <div class="form-label mb-2">Visual key</div>
            <div class="d-flex flex-wrap gap-2">
                <span class="visual-key-item"><span class="visual-key-dot visual-key-dot-uconn"></span>UConn</span>
                <span class="visual-key-item"><span class="visual-key-dot visual-key-dot-focus"></span>Focus</span>
                <span class="visual-key-item"><span class="visual-key-dot visual-key-dot-context"></span>Context</span>
                <span class="visual-key-item"><span class="visual-key-dot visual-key-dot-r1"></span>R1</span>
                <span class="visual-key-item"><span class="visual-key-dot visual-key-dot-r2"></span>R2</span>
            </div>
        </div>
        </div>
    </aside>

    <div class="context-main peer-main">
        <div class="row g-3 mb-4" id="workspaceStats">
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-label">UConn Latest</div>
                    <div class="kpi-value" id="statUconnLatest">—</div>
                    <p class="kpi-note" id="statUconnLatestSlope">Avg slope —</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-label">UConn Change</div>
                    <div class="kpi-value" id="statUconnChange">—</div>
                    <p class="kpi-note" id="statUconnChangeSlope">Avg slope —</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-label" id="statPeerLatestLabel">Peer Latest</div>
                    <div class="kpi-value" id="statPeerLatest">—</div>
                    <p class="kpi-note" id="statPeerLatestSlope">Avg slope —</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="kpi-card">
                    <div class="kpi-label" id="statPeerChangeLabel">Peer Change</div>
                    <div class="kpi-value" id="statPeerChange">—</div>
                    <p class="kpi-note" id="statPeerChangeSlope">Avg slope —</p>
                </div>
            </div>
        </div>

        <div class="card chart-panel mb-4">
            <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-start">
                <div>
                    <div class="fw-semibold">UConn vs Comparisons Over Time</div>
                    <div class="text-muted small">The selected metric is shown by year. UConn stays pinned while institutions and R1/R2 benchmarks provide context.</div>
                    <div class="benchmark-summary d-flex flex-wrap gap-2 mt-2" id="benchmarkSummary"></div>
                    <div class="outlook-chart-note mt-2" id="chartOutlookNote">Dashed lines extend average slopes beyond the latest actual year.</div>
                </div>
                <div class="chart-measure-control trend-measure-control">
                    <label for="workspaceMetric" class="form-label">Chart measure</label>
                    <select id="workspaceMetric" class="form-select form-select-sm"></select>
                    <div class="metric-definition mt-2" id="metricDefinition">—</div>
                </div>
            </div>
            <div class="card-body">
                <canvas id="peerTrendLineChart" height="120"></canvas>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <div class="fw-semibold">Outlook If Average Trends Continue</div>
                <div class="text-muted small" id="outlookDescription">Linear extension of the latest value using average yearly slope.</div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Target</th>
                            <th class="text-end">Latest</th>
                            <th class="text-end">Avg. yearly change</th>
                            <th class="text-end" id="projectedHeader">Projected</th>
                            <th class="text-end">Projected gap vs UConn</th>
                        </tr>
                    </thead>
                    <tbody id="outlookBody"></tbody>
                </table>
            </div>
            <div class="panel-note">Outlook extends historical average movement and should be read as directional context, not a prediction.</div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-xl-7">
                <div class="card h-100">
                    <div class="card-header">
                        <div class="fw-semibold">Visible Comparison Set</div>
                        <div class="text-muted small" id="comparisonSetDescription">—</div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover table-custom mb-0">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Institution</th>
                                    <th>Sector</th>
                                    <th class="text-end" id="selectedMetricHeader">Metric</th>
                                    <th class="text-end">Slope</th>
                                </tr>
                            </thead>
                            <tbody id="comparisonSetBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-xl-5">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between gap-2 align-items-center">
                        <div class="fw-semibold">Reading the Workspace</div>
                        <button class="btn btn-sm btn-outline-primary collapse-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#readingWorkspacePanel" aria-expanded="false" aria-controls="readingWorkspacePanel">
                            Notes
                        </button>
                    </div>
                    <div class="collapse" id="readingWorkspacePanel">
                    <div class="card-body small text-muted">
                        <p><strong class="text-body">Current-similar peers</strong> look like UConn in the latest year for the selected dimension.</p>
                        <p><strong class="text-body">Trajectory-similar peers</strong> are moving like UConn over time, even when their current mix differs.</p>
                        <p><strong class="text-body">Tenure status and rank/title</strong> are separate dimensions. Non-tenure faculty can hold assistant, associate, or full professor titles, so use the non-tenure rank-detail metrics when that distinction matters.</p>
                        <p class="mb-0"><strong class="text-body">Slope</strong> is the average yearly rate of change. Percent metrics are shown in percentage points per year.</p>
                    </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center">
                <div>
                    <div class="fw-semibold">Explore Distributions</div>
                    <div class="text-muted small">Optional scatter views for current value, position, and direction.</div>
                </div>
                <button class="btn btn-sm btn-outline-primary collapse-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#distributionPanel" aria-expanded="false" aria-controls="distributionPanel">
                    Charts
                </button>
            </div>
            <div class="collapse" id="distributionPanel">
            <div class="card-body">

<div class="row g-3 mb-4">
    <div class="col-xl-7">
        <div class="card chart-panel h-100">
            <div class="card-header">
                <div class="fw-semibold">Current Peer Position</div>
                <div class="text-muted small">Latest-year composition. Bubble size reflects total faculty.</div>
            </div>
            <div class="card-body">
                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label for="scatterXMetric" class="form-label">X-axis</label>
                        <select id="scatterXMetric" class="form-select form-select-sm"></select>
                    </div>
                    <div class="col-md-6">
                        <label for="scatterYMetric" class="form-label">Y-axis</label>
                        <select id="scatterYMetric" class="form-select form-select-sm"></select>
                    </div>
                </div>
                <canvas id="currentPositionChart" height="190"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card chart-panel h-100">
            <div class="card-header">
                <div class="fw-semibold">Current Value vs Direction</div>
                <div class="text-muted small">X-axis is the latest value; Y-axis is the yearly trend slope for the selected metric.</div>
            </div>
            <div class="card-body">
                <canvas id="changePositionChart" height="260"></canvas>
            </div>
        </div>
    </div>
</div>
            </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center">
                <div>
                    <div class="fw-semibold">Analyst Details</div>
                    <div class="text-muted small">Trend statistics and trajectory rankings for deeper review.</div>
                </div>
            </div>
        </div>

<div class="card mb-4">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center">
        <div>
            <div class="fw-semibold">UConn Trend Statistics</div>
            <div class="text-muted small">Detailed trend statistics for the selected UConn metrics.</div>
        </div>
        <button class="btn btn-sm btn-outline-primary collapse-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#uconnTrendStatsPanel" aria-expanded="false" aria-controls="uconnTrendStatsPanel">
            Table
        </button>
    </div>
    <div class="collapse" id="uconnTrendStatsPanel">
    <div class="table-responsive">
        <table class="table table-sm table-hover table-custom mb-0">
            <thead>
                <tr>
                    <th>Metric</th>
                    <th class="text-center">Years</th>
                    <th class="text-end">First Value</th>
                    <th class="text-end">Last Value</th>
                    <th class="text-end">Absolute Change</th>
                    <th class="text-end">% Change</th>
                    <th class="text-end">Slope</th>
                    <th class="text-end">R&sup2;</th>
                    <th class="text-end">p-value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($trends as $trend)
                <tr>
                    <td>{{ $metricLabels[$trend->metric] ?? $trend->metric }}</td>
                    <td class="text-center text-muted small">{{ $trend->first_year }}–{{ $trend->last_year }}</td>
                    <td class="text-end number-tabular">{{ fmtVal($trend->metric, $trend->first_value) }}</td>
                    <td class="text-end number-tabular">{{ fmtVal($trend->metric, $trend->last_value) }}</td>
                    <td class="text-end number-tabular {{ (float)($trend->absolute_change ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ fmtAbs($trend->metric, $trend->absolute_change) }}
                    </td>
                    <td class="text-end number-tabular {{ (float)($trend->percent_change ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ fmtPct($trend->percent_change) }}
                    </td>
                    <td class="text-end number-tabular">{{ fmtSlope($trend->metric, $trend->slope) }}</td>
                    <td class="text-end number-tabular">{{ $trend->r_squared !== null ? number_format((float)$trend->r_squared, 3) : '—' }}</td>
                    <td class="text-end number-tabular">{{ $trend->p_value !== null ? number_format((float)$trend->p_value, 4) : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div>
</div>

@if($trajectories->isNotEmpty())
<div class="card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center">
        <div>
            <div class="fw-semibold">Trajectory Similarity Detail</div>
            <div class="text-muted small">Institutions ranked by similarity in direction and rate of change.</div>
        </div>
        <button class="btn btn-sm btn-outline-primary collapse-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#trajectoryDetailPanel" aria-expanded="false" aria-controls="trajectoryDetailPanel">
            Table
        </button>
    </div>
    <div class="collapse" id="trajectoryDetailPanel">
    <div class="table-responsive">
        <table class="table table-sm table-hover table-custom mb-0">
            <thead>
                <tr>
                    <th>Trajectory Rank</th>
                    <th>Institution</th>
                    <th>Sector</th>
                    <th class="text-end">Distance</th>
                    <th class="text-end">Shared Metrics</th>
                    <th class="text-end">Non-Tenure Slope</th>
                    <th class="text-end">Tenure-System Slope</th>
                    <th class="text-end">Total Faculty Growth</th>
                </tr>
            </thead>
            <tbody>
                @foreach($trajectories as $row)
                <tr>
                    <td class="number-tabular">{{ $row->trajectory_similarity_rank }}</td>
                    <td>{{ $row->institution }}</td>
                    <td>{{ $row->sector }}</td>
                    <td class="text-end number-tabular">{{ $row->trajectory_distance_from_uconn !== null ? number_format((float) $row->trajectory_distance_from_uconn, 4) : '—' }}</td>
                    <td class="text-end number-tabular">{{ $row->n_shared_trajectory_metrics }}</td>
                    <td class="text-end number-tabular">{{ $row->slope_pct_non_tenure !== null ? number_format((float) $row->slope_pct_non_tenure * 100, 2) . ' pp/yr' : '—' }}</td>
                    <td class="text-end number-tabular">{{ $row->slope_pct_tenure_system !== null ? number_format((float) $row->slope_pct_tenure_system * 100, 2) . ' pp/yr' : '—' }}</td>
                    <td class="text-end number-tabular">{{ $row->pct_change_total_faculty !== null ? number_format((float) $row->pct_change_total_faculty * 100, 1) . '%' : '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    </div>
</div>
@endif

        </div>
    </div>

@endif

@endsection

@if($trends->isNotEmpty())
@push('scripts')
<script>
const peerTrendData = @json($peerTrendData);
const rootStyles = getComputedStyle(document.documentElement);
const cssColor = (name, fallback) => rootStyles.getPropertyValue(name).trim() || fallback;
const workspaceColors = {
    uconn: cssColor('--brand-navy', '#000e2f'),
    uconnFill: 'rgba(0, 14, 47, 0.85)',
    focus: cssColor('--brand-accent', '#2563eb'),
    focusFill: 'rgba(37, 99, 235, 0.75)',
    context: 'rgba(108, 117, 125, 0.45)',
    contextBorder: 'rgba(108, 117, 125, 0.8)',
    R1: '#a21caf',
    R2: '#0891b2',
};

const modeSelect = document.getElementById('comparisonMode');
const metricSelect = document.getElementById('workspaceMetric');
const setSelect = document.getElementById('workspaceSet');
const peerSelect = document.getElementById('workspacePeer');
const rankedSetControl = document.getElementById('rankedSetControl');
const rankedFocusControl = document.getElementById('rankedFocusControl');
const customControls = document.getElementById('customControls');
const customPeerSelects = [...document.querySelectorAll('.custom-peer-select')];
const customFocusSelect = document.getElementById('customFocusPeer');
const benchmarkToggleInputs = [...document.querySelectorAll('.benchmark-toggle')];
const benchmarkSummary = document.getElementById('benchmarkSummary');
const scatterXSelect = document.getElementById('scatterXMetric');
const scatterYSelect = document.getElementById('scatterYMetric');
const comparisonSetBody = document.getElementById('comparisonSetBody');
const comparisonSetDescription = document.getElementById('comparisonSetDescription');
const selectedMetricHeader = document.getElementById('selectedMetricHeader');
const metricDefinition = document.getElementById('metricDefinition');
const outlookHorizonSelect = document.getElementById('outlookHorizon');
const outlookHorizonLabel = document.getElementById('outlookHorizonLabel');
const outlookBody = document.getElementById('outlookBody');
const projectedHeader = document.getElementById('projectedHeader');
const outlookDescription = document.getElementById('outlookDescription');
const chartOutlookNote = document.getElementById('chartOutlookNote');
const allInstitutionMap = new Map((peerTrendData.allInstitutions || []).map((row) => [row.institution, row]));

let lineChart;
let currentScatterChart;
let changeScatterChart;
const hiddenLineSeries = new Set();

const outlookBoundaryPlugin = {
    id: 'outlookBoundary',
    afterDraw(chart) {
        const latestYear = Number(peerTrendData.latestYear);
        const horizon = outlookHorizon();

        if (!latestYear || horizon <= 0) {
            return;
        }

        const xScale = chart.scales.x;
        const area = chart.chartArea;
        const x = xScale.getPixelForValue(latestYear);
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

function metrics() {
    return peerTrendData.metrics || [];
}

function selectedMetric() {
    return metrics().find((metric) => metric.key === metricSelect.value) || metrics()[0];
}

function currentSet() {
    return peerTrendData.sets?.[setSelect.value] || Object.values(peerTrendData.sets || {})[0] || { institutions: [] };
}

function isCustomMode() {
    return modeSelect.value === 'custom';
}

function customInstitutionOptions() {
    return (peerTrendData.allInstitutions || [])
        .filter((row) => row.institution && row.institution !== peerTrendData.uconn)
        .map((row) => ({ value: row.institution, label: row.institution }));
}

function selectedCustomInstitutions() {
    const institutions = customPeerSelects
        .map((select) => select.value)
        .filter((institution) => institution && institution !== peerTrendData.uconn);

    return [...new Set(institutions)].slice(0, 4);
}

function comparisonRows(limit = null) {
    const rows = isCustomMode()
        ? selectedCustomInstitutions().map((institution, index) => {
            const option = allInstitutionMap.get(institution) || {};
            return {
                institution,
                rank: 'Custom',
                customOrder: index + 1,
                source: 'custom',
                sector: option.sector,
                carnegie: option.carnegie,
                totalFaculty: option.totalFaculty,
            };
        })
        : currentSet().institutions;

    return limit === null ? rows : rows.slice(0, limit);
}

function comparisonInstitutions(limit = null) {
    return comparisonRows(limit).map((row) => row.institution).filter(Boolean);
}

function focusInstitution() {
    if (isCustomMode()) {
        return customFocusSelect.value || selectedCustomInstitutions()[0] || '';
    }

    return peerSelect.value;
}

function enabledBenchmarks() {
    return benchmarkToggleInputs
        .filter((input) => input.checked)
        .map((input) => input.value);
}

function benchmarkRows(bucket, metric) {
    return peerTrendData.benchmarks?.[bucket]?.series?.[metric.key] || [];
}

function visibleInstitutions() {
    const focus = focusInstitution();
    const contextLimit = isCustomMode() ? 4 : 5;
    const institutions = [peerTrendData.uconn, focus, ...comparisonInstitutions(contextLimit)].filter(Boolean);
    return [...new Set(institutions)];
}

function trendRow(institution, metricKey) {
    return peerTrendData.trends?.[institution]?.[metricKey] || null;
}

function latestRow(institution) {
    return peerTrendData.latest?.[institution] || null;
}

function formatNumber(value, digits = 1) {
    if (value === null || value === undefined || Number.isNaN(Number(value))) {
        return '—';
    }
    return Number(value).toLocaleString(undefined, { maximumFractionDigits: digits });
}

function formatValue(value, metric) {
    if (value === null || value === undefined) {
        return '—';
    }
    return metric.isPercentMetric ? `${formatNumber(value)}%` : formatNumber(value, 0);
}

function formatChange(value, metric) {
    if (value === null || value === undefined) {
        return '—';
    }
    const sign = Number(value) >= 0 ? '+' : '';
    return metric.isPercentMetric ? `${sign}${formatNumber(value)} pp` : `${sign}${formatNumber(value, 0)}`;
}

function formatSlope(value, metric) {
    if (value === null || value === undefined) {
        return '—';
    }
    const sign = Number(value) >= 0 ? '+' : '';
    return metric.isPercentMetric ? `${sign}${formatNumber(value, 2)} pp/yr` : `${sign}${formatNumber(value, 1)}/yr`;
}

function formatGap(value, metric) {
    if (value === null || value === undefined) {
        return '—';
    }
    const sign = Number(value) >= 0 ? '+' : '';
    return metric.isPercentMetric ? `${sign}${formatNumber(value, 1)} pp` : `${sign}${formatNumber(value, 0)}`;
}

function outlookHorizon() {
    return Number(outlookHorizonSelect.value || 0);
}

function updateOutlookHorizonLabel() {
    const horizon = outlookHorizon();
    outlookHorizonLabel.textContent = horizon === 0
        ? 'Latest actual year only'
        : `${horizon} year${horizon === 1 ? '' : 's'} ahead`;
}

function projectedValue(value, slope, horizon, metric) {
    if (value === null || value === undefined || slope === null || slope === undefined) {
        return null;
    }

    const projected = Number(value) + Number(slope) * Number(horizon);

    if (metric.isPercentMetric) {
        return Math.min(100, Math.max(0, projected));
    }

    return Math.max(0, projected);
}

function linearSlope(points) {
    if (points.length < 2) {
        return null;
    }

    const meanYear = points.reduce((sum, point) => sum + point.year, 0) / points.length;
    const meanValue = points.reduce((sum, point) => sum + point.value, 0) / points.length;
    const denominator = points.reduce((sum, point) => sum + Math.pow(point.year - meanYear, 2), 0);

    if (denominator === 0) {
        return null;
    }

    return points.reduce((sum, point) => sum + (point.year - meanYear) * (point.value - meanValue), 0) / denominator;
}

function benchmarkTrend(bucket, metric) {
    const rows = benchmarkRows(bucket, metric)
        .filter((row) => row.value !== null && row.value !== undefined)
        .map((row) => ({ year: Number(row.year), value: Number(row.value), n: row.n }));

    if (rows.length === 0) {
        return null;
    }

    const latest = rows[rows.length - 1];

    return {
        latest: latest.value,
        slope: linearSlope(rows),
        latestYear: latest.year,
        n: latest.n,
    };
}

function projectedPointsFromLatest(latestYear, latestValue, slope, horizon, metric) {
    if (!latestYear || latestValue === null || latestValue === undefined || slope === null || slope === undefined || horizon <= 0) {
        return [];
    }

    const points = [{ x: Number(latestYear), y: Number(latestValue), projected: true }];

    for (let offset = 1; offset <= horizon; offset += 1) {
        points.push({
            x: Number(latestYear) + offset,
            y: projectedValue(latestValue, slope, offset, metric),
            projected: true,
        });
    }

    return points;
}

function fillSelect(select, options, selectedValue = null) {
    select.innerHTML = '';
    options.forEach((option) => {
        const element = document.createElement('option');
        element.value = option.value;
        element.textContent = option.label;
        if (selectedValue !== null && option.value === selectedValue) {
            element.selected = true;
        }
        select.appendChild(element);
    });
    window.refreshSearchSelect(select);
}

function initializeControls() {
    const metricOptions = metrics().map((metric) => ({ value: metric.key, label: metric.label }));
    fillSelect(metricSelect, metricOptions, peerTrendData.defaultMetric);
    fillSelect(scatterXSelect, metricOptions, 'pct_tenure_system');
    fillSelect(scatterYSelect, metricOptions, 'pct_non_tenure');

    const setOptions = Object.entries(peerTrendData.sets || {}).map(([key, set]) => ({ value: key, label: set.label }));
    fillSelect(setSelect, setOptions, peerTrendData.defaultSet);
    updatePeerOptions();
    initializeCustomOptions();
    updateModeControls();
}

function updatePeerOptions() {
    const institutions = currentSet().institutions.map((row) => ({
        value: row.institution,
        label: row.rank ? `${row.rank}. ${row.institution}` : row.institution,
    }));
    fillSelect(peerSelect, institutions, institutions[0]?.value || '');
}

function initializeCustomOptions() {
    const options = [{ value: '', label: 'Choose institution' }, ...customInstitutionOptions()];
    const defaultPeer = peerSelect.value && peerSelect.value !== peerTrendData.uconn
        ? peerSelect.value
        : options[1]?.value || '';

    customPeerSelects.forEach((select, index) => {
        fillSelect(select, options, index === 0 ? defaultPeer : '');
    });
    updateCustomFocusOptions();
}

function updateCustomFocusOptions() {
    const previousFocus = customFocusSelect.value;
    const selections = selectedCustomInstitutions();
    const options = selections.length > 0
        ? selections.map((institution) => ({ value: institution, label: institution }))
        : [{ value: '', label: 'Select a custom peer' }];
    const selectedFocus = selections.includes(previousFocus) ? previousFocus : (selections[0] || '');

    fillSelect(customFocusSelect, options, selectedFocus);
}

function updateModeControls() {
    rankedSetControl.classList.toggle('d-none', isCustomMode());
    rankedFocusControl.classList.toggle('d-none', isCustomMode());
    customControls.classList.toggle('d-none', !isCustomMode());
}

function updateMetricDefinition() {
    const metric = selectedMetric();
    metricDefinition.textContent = `${metric.group} · ${metric.isPercentMetric ? 'Share of total faculty' : 'Faculty count'} · ${metric.changeUnit}/yr trend`;
}

function lineDatasets(metric) {
    const palette = ['#6c757d', '#198754', '#fd7e14', '#20c997', '#6610f2'];
    const horizon = outlookHorizon();
    const latestYear = Number(peerTrendData.latestYear);
    const institutionDatasets = visibleInstitutions().flatMap((institution, index) => {
        const rows = peerTrendData.series?.[institution] || [];
        const isUconn = institution === peerTrendData.uconn;
        const isFocus = institution === focusInstitution();
        const color = isUconn ? workspaceColors.uconn : (isFocus ? workspaceColors.focus : palette[index % palette.length]);
        const latest = latestRow(institution);
        const trend = trendRow(institution, metric.key);
        const seriesKey = `institution:${institution}:${metric.key}`;

        const historyDataset = {
            label: institution,
            data: rows.map((row) => ({ x: row.year, y: row[metric.key] })),
            borderColor: color,
            backgroundColor: color,
            borderWidth: isUconn || isFocus ? 3 : 1.5,
            pointRadius: isUconn || isFocus ? 3 : 2,
            tension: 0.25,
            spanGaps: true,
            seriesKey,
        };

        const outlookDataset = {
            label: `${institution} outlook`,
            data: projectedPointsFromLatest(latestYear, latest?.[metric.key], trend?.slope, horizon, metric),
            borderColor: color,
            backgroundColor: color,
            borderDash: [3, 6],
            borderWidth: isUconn || isFocus ? 2.5 : 1.75,
            pointRadius: 0,
            tension: 0.2,
            spanGaps: true,
            isOutlook: true,
            seriesKey,
            slope: trend?.slope,
        };

        return outlookDataset.data.length > 0 ? [historyDataset, outlookDataset] : [historyDataset];
    });

    const benchmarkDatasets = enabledBenchmarks().flatMap((bucket) => {
        const benchmark = peerTrendData.benchmarks?.[bucket] || { label: `${bucket} average` };
        const color = workspaceColors[bucket] || workspaceColors.contextBorder;
        const rows = benchmarkRows(bucket, metric);
        const trend = benchmarkTrend(bucket, metric);
        const seriesKey = `benchmark:${bucket}:${metric.key}`;
        const historyDash = bucket === 'R2' ? [4, 4] : [14, 5];
        const outlookDash = bucket === 'R2' ? [2, 4, 8, 4] : [8, 4, 2, 4];

        if (rows.length === 0) {
            return [];
        }

        const historyDataset = {
            label: benchmark.label || `${bucket} average`,
            data: rows.map((row) => ({
                x: row.year,
                y: row.value,
                n: row.n,
                benchmark: bucket,
            })),
            borderColor: color,
            backgroundColor: color,
            borderDash: historyDash,
            borderWidth: 2.75,
            pointRadius: 2,
            tension: 0.25,
            spanGaps: true,
            seriesKey,
        };

        const outlookDataset = {
            label: `${benchmark.label || `${bucket} average`} outlook`,
            data: projectedPointsFromLatest(trend?.latestYear, trend?.latest, trend?.slope, horizon, metric).map((point) => ({
                ...point,
                benchmark: bucket,
                n: trend?.n,
            })),
            borderColor: color,
            backgroundColor: color,
            borderDash: outlookDash,
            borderWidth: 2.5,
            pointRadius: 0,
            tension: 0.2,
            spanGaps: true,
            isOutlook: true,
            seriesKey,
            slope: trend?.slope,
        };

        return outlookDataset.data.length > 0 ? [historyDataset, outlookDataset] : [historyDataset];
    });

    return [...institutionDatasets, ...benchmarkDatasets];
}

function renderLineChart() {
    const metric = selectedMetric();
    const horizon = outlookHorizon();
    const latestYear = Number(peerTrendData.latestYear);
    const data = { datasets: lineDatasets(metric) };

    const options = {
        responsive: true,
        parsing: false,
        plugins: {
            legend: {
                position: 'bottom',
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
                        hiddenLineSeries.delete(dataset.seriesKey);
                    } else {
                        hiddenLineSeries.add(dataset.seriesKey);
                    }

                    chart.update();
                },
                labels: {
                    filter: (item, chart) => !chart.datasets[item.datasetIndex]?.isOutlook,
                },
            },
            tooltip: {
                callbacks: {
                    title: (items) => items.length > 0 ? String(Math.trunc(items[0].parsed.x)) : '',
                    label: (context) => {
                        const raw = context.raw || {};
                        const suffix = raw.benchmark && raw.n ? ` (${raw.n} institutions)` : '';
                        const slope = context.dataset.isOutlook ? ` · slope ${formatSlope(context.dataset.slope, metric)}` : '';
                        const label = context.dataset.isOutlook ? context.dataset.label.replace(' outlook', '') : context.dataset.label;

                        return `${label}: ${formatValue(context.parsed.y, metric)}${suffix}${slope}`;
                    },
                },
            },
        },
        scales: {
            x: {
                type: 'linear',
                title: { display: true, text: 'Year' },
                suggestedMax: latestYear && horizon > 0 ? latestYear + horizon : undefined,
                ticks: { precision: 0, callback: (value) => String(Math.trunc(value)) },
            },
            y: {
                title: { display: true, text: metric.isPercentMetric ? 'Share of Total Faculty' : 'Faculty Count' },
                ticks: { callback: (value) => metric.isPercentMetric ? value + '%' : value },
            },
        },
    };

    chartOutlookNote.textContent = horizon > 0
        ? `Dashed lines extend average slopes through ${latestYear + horizon}. Slope values appear in outlook tooltips and table rows.`
        : 'Move the outlook slider to extend average slopes beyond the latest actual year.';

    if (!lineChart) {
        lineChart = new Chart(document.getElementById('peerTrendLineChart'), { type: 'line', data, options, plugins: [outlookBoundaryPlugin] });
    } else {
        lineChart.data = data;
        lineChart.options = options;
    }

    lineChart.data.datasets.forEach((dataset, index) => {
        lineChart.setDatasetVisibility(index, !hiddenLineSeries.has(dataset.seriesKey));
    });
    lineChart.update();
}

function pointForInstitution(institution, xMetric, yMetric) {
    const latest = latestRow(institution);
    if (!latest || latest[xMetric.key] === null || latest[yMetric.key] === null) {
        return null;
    }

    return {
        x: latest[xMetric.key],
        y: latest[yMetric.key],
        r: latest.bubbleSize || 6,
        institution,
        label: institution,
    };
}

function scatterDataset(label, institutions, xMetric, yMetric, color, borderColor) {
    return {
        label,
        data: institutions.map((institution) => pointForInstitution(institution, xMetric, yMetric)).filter(Boolean),
        backgroundColor: color,
        borderColor,
    };
}

function renderCurrentScatter() {
    const xMetric = metrics().find((metric) => metric.key === scatterXSelect.value) || selectedMetric();
    const yMetric = metrics().find((metric) => metric.key === scatterYSelect.value) || metrics()[1] || selectedMetric();
    const focus = focusInstitution();
    const contextInstitutions = comparisonInstitutions(isCustomMode() ? 4 : 12).filter((institution) => institution !== focus);

    const data = {
        datasets: [
            scatterDataset(isCustomMode() ? 'Selected institutions' : 'Comparison set', contextInstitutions, xMetric, yMetric, workspaceColors.context, workspaceColors.contextBorder),
            scatterDataset(focus || 'Focused peer', [focus], xMetric, yMetric, workspaceColors.focusFill, workspaceColors.focus),
            scatterDataset('University of Connecticut', [peerTrendData.uconn], xMetric, yMetric, workspaceColors.uconnFill, workspaceColors.uconn),
        ],
    };

    const options = bubbleOptions(xMetric, yMetric);

    if (!currentScatterChart) {
        currentScatterChart = new Chart(document.getElementById('currentPositionChart'), { type: 'bubble', data, options });
    } else {
        currentScatterChart.data = data;
        currentScatterChart.options = options;
        currentScatterChart.update();
    }
}

function renderChangeScatter() {
    const metric = selectedMetric();
    const focus = focusInstitution();
    const institutions = [peerTrendData.uconn, focus, ...comparisonInstitutions(isCustomMode() ? 4 : 12)].filter(Boolean);
    const uniqueInstitutions = [...new Set(institutions)];

    const points = uniqueInstitutions.map((institution) => {
        const latest = latestRow(institution);
        const trend = trendRow(institution, metric.key);
        if (!latest || !trend || latest[metric.key] === null || trend.slope === null) {
            return null;
        }
        return {
            x: latest[metric.key],
            y: trend.slope,
            r: latest.bubbleSize || 6,
            institution,
            label: institution,
        };
    }).filter(Boolean);

    const data = {
        datasets: [
            {
                label: isCustomMode() ? 'Selected institutions' : 'Comparison set',
                data: points.filter((point) => point.institution !== peerTrendData.uconn && point.institution !== focus),
                backgroundColor: workspaceColors.context,
                borderColor: workspaceColors.contextBorder,
            },
            {
                label: focus || 'Focused peer',
                data: points.filter((point) => point.institution === focus),
                backgroundColor: workspaceColors.focusFill,
                borderColor: workspaceColors.focus,
            },
            {
                label: 'University of Connecticut',
                data: points.filter((point) => point.institution === peerTrendData.uconn),
                backgroundColor: workspaceColors.uconnFill,
                borderColor: workspaceColors.uconn,
            },
        ],
    };

    const options = bubbleOptions(metric, { ...metric, label: 'Slope' }, true);

    if (!changeScatterChart) {
        changeScatterChart = new Chart(document.getElementById('changePositionChart'), { type: 'bubble', data, options });
    } else {
        changeScatterChart.data = data;
        changeScatterChart.options = options;
        changeScatterChart.update();
    }
}

function bubbleOptions(xMetric, yMetric, yIsSlope = false) {
    return {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: (context) => {
                        const point = context.raw;
                        const yLabel = yIsSlope ? formatSlope(point.y, xMetric) : formatValue(point.y, yMetric);
                        return `${point.institution}: ${formatValue(point.x, xMetric)} / ${yLabel}`;
                    },
                },
            },
        },
        scales: {
            x: {
                title: { display: true, text: xMetric.label },
                ticks: { callback: (value) => xMetric.isPercentMetric ? value + '%' : value },
            },
            y: {
                title: { display: true, text: yIsSlope ? `${xMetric.label} Slope` : yMetric.label },
                ticks: { callback: (value) => yIsSlope ? formatSlope(value, xMetric) : (yMetric.isPercentMetric ? value + '%' : value) },
            },
        },
    };
}

function updateStats() {
    const metric = selectedMetric();
    const focus = focusInstitution();
    const uconnTrend = trendRow(peerTrendData.uconn, metric.key);
    const focusTrend = trendRow(focus, metric.key);
    const uconnLatest = latestRow(peerTrendData.uconn);
    const focusLatest = latestRow(focus);
    const peerLabel = focus || 'Peer';

    document.getElementById('statUconnLatest').textContent = formatValue(uconnLatest?.[metric.key], metric);
    document.getElementById('statUconnChange').textContent = formatChange(uconnTrend?.absoluteChange, metric);
    document.getElementById('statPeerLatestLabel').textContent = `${peerLabel} Latest`;
    document.getElementById('statPeerChangeLabel').textContent = `${peerLabel} Change`;
    document.getElementById('statPeerLatest').textContent = formatValue(focusLatest?.[metric.key], metric);
    document.getElementById('statPeerChange').textContent = formatChange(focusTrend?.absoluteChange, metric);
    document.getElementById('statUconnLatestSlope').textContent = `Avg slope ${formatSlope(uconnTrend?.slope, metric)}`;
    document.getElementById('statUconnChangeSlope').textContent = `Avg slope ${formatSlope(uconnTrend?.slope, metric)}`;
    document.getElementById('statPeerLatestSlope').textContent = `Avg slope ${formatSlope(focusTrend?.slope, metric)}`;
    document.getElementById('statPeerChangeSlope').textContent = `Avg slope ${formatSlope(focusTrend?.slope, metric)}`;
}

function renderBenchmarkSummary() {
    const metric = selectedMetric();
    const chips = enabledBenchmarks().map((bucket) => {
        const rows = benchmarkRows(bucket, metric);
        const latest = rows[rows.length - 1];
        const benchmark = peerTrendData.benchmarks?.[bucket] || { label: `${bucket} average` };

        if (!latest) {
            return `<span class="benchmark-chip benchmark-chip-${bucket.toLowerCase()}">${benchmark.label}: unavailable</span>`;
        }

        return `<span class="benchmark-chip benchmark-chip-${bucket.toLowerCase()}">${benchmark.label} ${latest.year}: ${formatValue(latest.value, metric)} <span class="text-muted">n=${latest.n}</span></span>`;
    }).join('');

    benchmarkSummary.innerHTML = chips;
}

function renderComparisonTable() {
    const metric = selectedMetric();
    const focus = focusInstitution();
    selectedMetricHeader.textContent = metric.label;
    comparisonSetDescription.textContent = isCustomMode()
        ? 'Custom institutions are not similarity-ranked. They are shown in the order selected.'
        : (currentSet().description || 'Selected comparison set.');

    const rows = comparisonRows(isCustomMode() ? 4 : 15).map((row) => {
        const latest = latestRow(row.institution);
        const trend = trendRow(row.institution, metric.key);
        const rowClass = row.institution === peerTrendData.uconn
            ? 'comparison-row-uconn'
            : (row.institution === focus ? 'comparison-row-focus' : '');
        const rankLabel = isCustomMode() ? `Custom ${row.customOrder ?? ''}` : (row.rank ?? '—');
        return `
            <tr class="${rowClass}">
                <td class="number-tabular">${rankLabel}</td>
                <td>${row.institution}</td>
                <td>${row.sector ?? '—'}</td>
                <td class="text-end number-tabular">${formatValue(latest?.[metric.key], metric)}</td>
                <td class="text-end number-tabular">${formatSlope(trend?.slope, metric)}</td>
            </tr>
        `;
    }).join('');

    comparisonSetBody.innerHTML = rows || '<tr><td colspan="5" class="text-muted">No institutions available for this comparison set.</td></tr>';
}

function outlookTargets(metric) {
    const institutionTargets = visibleInstitutions().map((institution) => {
        const latest = latestRow(institution);
        const trend = trendRow(institution, metric.key);

        return {
            key: institution,
            label: institution,
            latest: latest?.[metric.key],
            slope: trend?.slope,
            type: institution === peerTrendData.uconn ? 'uconn' : 'institution',
        };
    });

    const benchmarkTargets = enabledBenchmarks().map((bucket) => {
        const trend = benchmarkTrend(bucket, metric);
        const benchmark = peerTrendData.benchmarks?.[bucket] || { label: `${bucket} average` };

        return {
            key: bucket,
            label: benchmark.label || `${bucket} average`,
            latest: trend?.latest,
            slope: trend?.slope,
            type: 'benchmark',
        };
    });

    return [...institutionTargets, ...benchmarkTargets];
}

function gapDirection(currentGap, projectedGap, metric) {
    if (currentGap === null || projectedGap === null) {
        return '';
    }

    const threshold = metric.isPercentMetric ? 0.1 : 1;
    const currentDistance = Math.abs(currentGap);
    const projectedDistance = Math.abs(projectedGap);
    const movement = projectedDistance - currentDistance;

    if (Math.abs(movement) <= threshold) {
        return 'roughly unchanged';
    }

    return movement < 0 ? `narrows by ${formatGap(Math.abs(movement), metric).replace('+', '')}` : `widens by ${formatGap(Math.abs(movement), metric).replace('+', '')}`;
}

function renderOutlook() {
    const metric = selectedMetric();
    const horizon = outlookHorizon();
    const targets = outlookTargets(metric);
    const uconn = targets.find((target) => target.key === peerTrendData.uconn);
    const uconnProjected = projectedValue(uconn?.latest, uconn?.slope, horizon, metric);
    updateOutlookHorizonLabel();
    projectedHeader.textContent = horizon === 0 ? 'Latest Actual' : `${horizon}-Year Outlook`;
    outlookDescription.textContent = horizon === 0
        ? `${metric.label}: latest actual value and average yearly slope.`
        : `${metric.label}: latest value plus average yearly change for ${horizon} year${horizon === 1 ? '' : 's'}.`;

    const rows = targets.map((target) => {
        const projected = projectedValue(target.latest, target.slope, horizon, metric);
        const currentGap = target.key === peerTrendData.uconn || target.latest === null || target.latest === undefined || uconn?.latest === null || uconn?.latest === undefined
            ? null
            : Number(target.latest) - Number(uconn.latest);
        const projectedGap = target.key === peerTrendData.uconn || projected === null || uconnProjected === null
            ? null
            : projected - uconnProjected;
        const direction = gapDirection(currentGap, projectedGap, metric);
        const rowClass = target.type === 'uconn' ? 'comparison-row-uconn' : (target.type === 'benchmark' ? `outlook-row-${target.key.toLowerCase()}` : '');

        return `
            <tr class="${rowClass}">
                <td>${target.label}</td>
                <td class="text-end number-tabular">${formatValue(target.latest, metric)}</td>
                <td class="text-end number-tabular">${formatSlope(target.slope, metric)}</td>
                <td class="text-end number-tabular">${formatValue(projected, metric)}</td>
                <td class="text-end number-tabular">${projectedGap === null ? '—' : `${formatGap(projectedGap, metric)} <span class="text-muted">${direction}</span>`}</td>
            </tr>
        `;
    }).join('');

    outlookBody.innerHTML = rows || '<tr><td colspan="5" class="text-muted">No outlook data available for this metric.</td></tr>';
}

function renderWorkspace() {
    updateMetricDefinition();
    updateStats();
    renderBenchmarkSummary();
    renderLineChart();
    renderCurrentScatter();
    renderChangeScatter();
    renderComparisonTable();
    renderOutlook();
}

if (metrics().length > 0) {
    initializeControls();
    renderWorkspace();

    metricSelect.addEventListener('change', renderWorkspace);
    scatterXSelect.addEventListener('change', renderCurrentScatter);
    scatterYSelect.addEventListener('change', renderCurrentScatter);
    peerSelect.addEventListener('change', renderWorkspace);
    modeSelect.addEventListener('change', () => {
        updateModeControls();
        renderWorkspace();
    });
    customPeerSelects.forEach((select) => {
        select.addEventListener('change', () => {
            updateCustomFocusOptions();
            renderWorkspace();
        });
    });
    customFocusSelect.addEventListener('change', renderWorkspace);
    benchmarkToggleInputs.forEach((input) => input.addEventListener('change', () => {
        if (input.checked) {
            benchmarkToggleInputs.forEach((otherInput) => {
                if (otherInput !== input) {
                    otherInput.checked = false;
                }
            });
        }

        renderWorkspace();
    }));
    outlookHorizonSelect.addEventListener('change', renderWorkspace);
    setSelect.addEventListener('change', () => {
        updatePeerOptions();
        renderWorkspace();
    });
}
</script>
@endpush
@endif
