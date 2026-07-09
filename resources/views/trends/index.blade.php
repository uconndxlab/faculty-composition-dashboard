@extends('layouts.app')

@section('title', 'Peer Trends')

@section('content')

<div class="page-header">
    <div>
        <div class="page-kicker">Peer Comparison Workspace</div>
        <h1 class="page-title">Peer Trends</h1>
        <p class="page-subtitle">Compare where UConn is now, how it is moving, and which institutions make useful current or trajectory peers.</p>
    </div>
    @if(! empty($peerTrendData['latestYear']))
        <span class="metric-chip">Latest year {{ $peerTrendData['latestYear'] }}</span>
    @endif
</div>

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

<div class="card mb-4">
    <div class="card-header card-header-brand">
        <div class="fw-semibold">Comparison Controls</div>
        <div class="small">Choose a metric, then compare UConn against a ranked peer set or up to four custom institutions.</div>
    </div>
    <div class="card-body">
        <div class="control-panel row g-3 align-items-end">
            <div class="col-md-4 col-xl-2">
                <label for="comparisonMode" class="form-label">Compare by</label>
                <select id="comparisonMode" class="form-select">
                    <option value="ranked">Ranked set</option>
                    <option value="custom">Custom institutions</option>
                </select>
            </div>
            <div class="col-md-4 col-xl-3">
                <label for="workspaceMetric" class="form-label">Primary metric</label>
                <select id="workspaceMetric" class="form-select"></select>
            </div>
            <div class="col-md-4 col-xl-3" id="rankedSetControl">
                <label for="workspaceSet" class="form-label">Comparison set</label>
                <select id="workspaceSet" class="form-select"></select>
            </div>
            <div class="col-md-4 col-xl-2" id="rankedFocusControl">
                <label for="workspacePeer" class="form-label">Focus institution</label>
                <select id="workspacePeer" class="form-select" data-search-select data-search-placeholder="Search focus institutions"></select>
            </div>
            <div class="col-md-4 col-xl-2">
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
        <div class="control-panel row g-3 align-items-end mt-3 d-none" id="customControls">
            @for($i = 1; $i <= 4; $i++)
                <div class="col-md-6 col-xl-3">
                    <label for="customPeer{{ $i }}" class="form-label">Custom peer {{ $i }}</label>
                    <select id="customPeer{{ $i }}" class="form-select custom-peer-select" data-search-select data-search-placeholder="Search institutions"></select>
                </div>
            @endfor
            <div class="col-md-6 col-xl-3">
                <label for="customFocusPeer" class="form-label">KPI focus</label>
                <select id="customFocusPeer" class="form-select" data-search-select data-search-placeholder="Search selected peers"></select>
            </div>
            <div class="col-md-6 col-xl-9">
                <p class="kpi-note mb-0">Custom institutions are not sorted by similarity. They appear in the order selected and can be used for one-to-one comparison or up to four peers.</p>
            </div>
        </div>
        <div class="control-panel row g-3 align-items-center mt-3">
            <div class="col-md-4 col-xl-3">
                <div class="form-label mb-2">Benchmark overlays</div>
                <div class="d-flex flex-wrap gap-3">
                    <div class="form-check mb-0">
                        <input class="form-check-input benchmark-toggle" type="checkbox" value="R1" id="benchmarkR1">
                        <label class="form-check-label fw-semibold" for="benchmarkR1">R1 average</label>
                    </div>
                    <div class="form-check mb-0">
                        <input class="form-check-input benchmark-toggle" type="checkbox" value="R2" id="benchmarkR2">
                        <label class="form-check-label fw-semibold" for="benchmarkR2">R2 average</label>
                    </div>
                </div>
            </div>
            <div class="col-md-8 col-xl-9">
                <p class="kpi-note mb-0">Benchmarks are aggregate trajectories from mapped Carnegie buckets, not ranked peer institutions. Faculty counts use median; share metrics use average.</p>
            </div>
        </div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center">
        <div>
            <div class="fw-semibold">Comparison Set Options</div>
            <div class="text-muted small">Ranked sets come from imported similarity files. Custom institutions come directly from faculty summaries and are not similarity-ranked.</div>
        </div>
        <button class="btn btn-sm btn-outline-primary collapse-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#comparisonSetOptionsPanel" aria-expanded="false" aria-controls="comparisonSetOptionsPanel">
            Details
        </button>
    </div>
    <div class="collapse" id="comparisonSetOptionsPanel">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6 col-xl-4">
                <div class="kpi-card">
                    <div class="kpi-label">Custom Institutions</div>
                    <p class="kpi-note mb-2">Choose one to four institutions directly, regardless of imported similarity or trajectory rank.</p>
                    <div class="small text-muted">
                        Derived from <code>faculty_summaries</code>. Institutions are shown in selected order, not rank order.
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="kpi-card">
                    <div class="kpi-label">R1/R2 Benchmarks</div>
                    <p class="kpi-note mb-2">Aggregate lines showing average R1 and R2 trajectories for the selected metric.</p>
                    <div class="small text-muted">
                        R1 maps to <code>Mixed Undergraduate/Graduate Large</code>; R2 maps to <code>Mixed Undergraduate/Graduate Medium</code>.
                    </div>
                </div>
            </div>
            @foreach($peerTrendData['sets'] ?? [] as $setKey => $set)
                <div class="col-md-6 col-xl-4">
                    <div class="kpi-card">
                        <div class="kpi-label">{{ $set['label'] }}</div>
                        <p class="kpi-note mb-2">{{ $set['description'] ?? 'Selected comparison set.' }}</p>
                        <div class="small text-muted">
                            @if($setKey === 'trajectory')
                                Derived from <code>trajectory_similarities</code>, ordered by <code>trajectory_similarity_rank</code>.
                            @else
                                Derived from <code>similarity_rankings</code>, ordered by <code>{{ $set['rankColumn'] ?? 'rank' }}</code>.
                            @endif
                            <span class="number-tabular">{{ count($set['institutions'] ?? []) }}</span> institutions loaded.
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    </div>
</div>

<div class="row g-3 mb-4" id="workspaceStats">
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-label">UConn Latest</div>
            <div class="kpi-value" id="statUconnLatest">—</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-label">UConn Change</div>
            <div class="kpi-value" id="statUconnChange">—</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-label" id="statPeerLatestLabel">Peer Latest</div>
            <div class="kpi-value" id="statPeerLatest">—</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-label" id="statPeerChangeLabel">Peer Change</div>
            <div class="kpi-value" id="statPeerChange">—</div>
        </div>
    </div>
</div>

<div class="card chart-panel mb-4">
    <div class="card-header">
        <div class="fw-semibold">Movement Over Time</div>
        <div class="text-muted small">The selected metric is shown by year. UConn stays pinned; the comparison set gives context without requiring a separate page.</div>
        <div class="benchmark-summary d-flex flex-wrap gap-2 mt-2" id="benchmarkSummary"></div>
    </div>
    <div class="card-body">
        <canvas id="peerTrendLineChart" height="120"></canvas>
    </div>
</div>

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
    R1: '#0f766e',
    R2: '#b45309',
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
const allInstitutionMap = new Map((peerTrendData.allInstitutions || []).map((row) => [row.institution, row]));

let lineChart;
let currentScatterChart;
let changeScatterChart;

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

function lineDatasets(metric) {
    const palette = ['#6c757d', '#198754', '#fd7e14', '#20c997', '#6610f2'];
    const institutionDatasets = visibleInstitutions().map((institution, index) => {
        const rows = peerTrendData.series?.[institution] || [];
        const isUconn = institution === peerTrendData.uconn;
        const isFocus = institution === focusInstitution();
        const color = isUconn ? workspaceColors.uconn : (isFocus ? workspaceColors.focus : palette[index % palette.length]);

        return {
            label: institution,
            data: rows.map((row) => ({ x: row.year, y: row[metric.key] })),
            borderColor: color,
            backgroundColor: color,
            borderWidth: isUconn || isFocus ? 3 : 1.5,
            pointRadius: isUconn || isFocus ? 3 : 2,
            tension: 0.25,
            spanGaps: true,
        };
    });

    const benchmarkDatasets = enabledBenchmarks().map((bucket) => {
        const benchmark = peerTrendData.benchmarks?.[bucket] || { label: `${bucket} average` };
        const color = workspaceColors[bucket] || workspaceColors.contextBorder;
        const rows = benchmarkRows(bucket, metric);

        if (rows.length === 0) {
            return null;
        }

        return {
            label: benchmark.label || `${bucket} average`,
            data: rows.map((row) => ({
                x: row.year,
                y: row.value,
                n: row.n,
                benchmark: bucket,
            })),
            borderColor: color,
            backgroundColor: color,
            borderDash: [8, 5],
            borderWidth: 2.25,
            pointRadius: 2,
            tension: 0.25,
            spanGaps: true,
        };
    }).filter(Boolean);

    return [...institutionDatasets, ...benchmarkDatasets];
}

function renderLineChart() {
    const metric = selectedMetric();
    const data = { datasets: lineDatasets(metric) };

    const options = {
        responsive: true,
        parsing: false,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    title: (items) => items.length > 0 ? String(Math.trunc(items[0].parsed.x)) : '',
                    label: (context) => {
                        const raw = context.raw || {};
                        const suffix = raw.benchmark && raw.n ? ` (${raw.n} institutions)` : '';

                        return `${context.dataset.label}: ${formatValue(context.parsed.y, metric)}${suffix}`;
                    },
                },
            },
        },
        scales: {
            x: {
                type: 'linear',
                title: { display: true, text: 'Year' },
                ticks: { precision: 0, callback: (value) => String(Math.trunc(value)) },
            },
            y: {
                title: { display: true, text: metric.isPercentMetric ? 'Share of Total Faculty' : 'Faculty Count' },
                ticks: { callback: (value) => metric.isPercentMetric ? value + '%' : value },
            },
        },
    };

    if (!lineChart) {
        lineChart = new Chart(document.getElementById('peerTrendLineChart'), { type: 'line', data, options });
    } else {
        lineChart.data = data;
        lineChart.options = options;
        lineChart.update();
    }
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

function renderWorkspace() {
    updateStats();
    renderBenchmarkSummary();
    renderLineChart();
    renderCurrentScatter();
    renderChangeScatter();
    renderComparisonTable();
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
    benchmarkToggleInputs.forEach((input) => input.addEventListener('change', renderWorkspace));
    setSelect.addEventListener('change', () => {
        updatePeerOptions();
        renderWorkspace();
    });
}
</script>
@endpush
@endif
