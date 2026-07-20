@extends('layouts.app')

@section('title', 'Faculty Composition Workspace')

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
    <aside class="context-sidebar peer-sidebar" aria-label="Workspace controls">
        <button class="sidebar-collapse-toggle context-sidebar-toggle" type="button" data-context-sidebar-toggle aria-label="Collapse workspace controls">
            ‹
        </button>
        <div class="context-sidebar-rail-label">Workspace</div>
        <div class="context-sidebar-content">

        <div class="context-sidebar-header">
            <div class="page-kicker">UConn Academic Operations</div>
            <h1 class="page-title">Faculty Composition</h1>
            @if(! empty($peerTrendData['latestYear']))
                <span class="metric-chip">Data through {{ $peerTrendData['latestYear'] }}</span>
            @endif
        </div>

        {{-- Mode toggle --}}
        <div class="peer-sidebar-section">
            <div class="btn-group w-100" role="group" aria-label="Workspace mode">
                <button type="button" class="btn btn-outline-primary" id="modeProfileBtn" data-workspace-mode="profile">Profile</button>
                <button type="button" class="btn btn-outline-primary active" id="modeCompareBtn" data-workspace-mode="compare">Compare</button>
            </div>
        </div>

        {{-- Profile-mode controls --}}
        <div id="profileControls" class="d-none">
            <div class="peer-sidebar-section">
                <div class="peer-sidebar-heading">Institution</div>
                <select id="profileInstitutionSelect" class="form-select"></select>
            </div>
            <div class="peer-sidebar-section">
                <div class="peer-sidebar-heading">Snapshot Year</div>
                <select id="profileYearSelect" class="form-select"></select>
                <p class="kpi-note mt-2">KPI tiles update for the selected year. The chart always shows the full history.</p>
            </div>
            <div class="peer-sidebar-section">
                <label for="dashboardOutlookHorizon" class="form-label">Outlook horizon</label>
                <div class="outlook-slider-value"><span id="profileOutlookLabel">3 years ahead</span></div>
                <input id="profileOutlookHorizon" class="form-range" type="range" min="0" max="15" step="1" value="3">
                <div class="d-flex justify-content-between small text-muted number-tabular">
                    <span>Latest</span><span>+15 yrs</span>
                </div>
                <p class="kpi-note mt-2">Dashed lines extend average historical slopes. Directional context, not a forecast.</p>
            </div>
            <div class="peer-sidebar-section">
                <div class="form-label mb-2">Benchmarks</div>
                <div class="d-grid gap-2">
                    <div class="form-check">
                        <input class="form-check-input profile-benchmark-toggle" type="checkbox" value="R1" id="profileBenchmarkR1" checked>
                        <label class="form-check-label fw-semibold" for="profileBenchmarkR1">R1 average</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input profile-benchmark-toggle" type="checkbox" value="R2" id="profileBenchmarkR2">
                        <label class="form-check-label fw-semibold" for="profileBenchmarkR2">R2 average</label>
                    </div>
                </div>
                <p class="kpi-note mt-2">Share metrics use average; faculty counts use median.</p>
            </div>
        </div>

        {{-- Compare-mode controls --}}
        <div id="compareControls">
            <div class="peer-sidebar-section">
                <div class="peer-sidebar-heading">Comparison Setup</div>
                <p class="kpi-note">UConn is fixed. Add institutions and benchmarks to compare against it.</p>
            </div>
            <div class="peer-sidebar-section">
                <label for="comparisonMode" class="form-label">Institution source</label>
                <select id="comparisonMode" class="form-select">
                    <option value="ranked">Use ranked set</option>
                    <option value="custom">Choose institutions</option>
                </select>
            </div>
            <div class="peer-sidebar-section" id="rankedSetControl">
                <label for="workspaceSet" class="form-label">Ranked set</label>
                <select id="workspaceSet" class="form-select"></select>
            </div>
            <div class="peer-sidebar-section d-none" id="rankBandControl">
                <label for="rankBandSlider" class="form-label">Rank band</label>
                <div class="outlook-slider-value"><span id="rankBandLabel">±10 ranks</span></div>
                <input id="rankBandSlider" class="form-range" type="range" min="1" max="30" step="1" value="10">
                <div class="text-muted small number-tabular mt-1" id="rankBandRange">—</div>
                <p class="kpi-note mt-2">Shows institutions within this window of UConn's US News rank.</p>
            </div>
            <div class="peer-sidebar-section" id="rankedFocusControl">
                <label for="workspacePeer" class="form-label">Focus institution</label>
                <select id="workspacePeer" class="form-select" data-search-select data-search-placeholder="Search focus institutions"></select>
            </div>
            <div class="peer-sidebar-section d-none" id="customControls">
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
                    <div class="form-check">
                        <input class="form-check-input benchmark-toggle" type="checkbox" value="R1" id="benchmarkR1" checked>
                        <label class="form-check-label fw-semibold" for="benchmarkR1">R1 average</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input benchmark-toggle" type="checkbox" value="R2" id="benchmarkR2">
                        <label class="form-check-label fw-semibold" for="benchmarkR2">R2 average</label>
                    </div>
                </div>
                <p class="kpi-note mt-2">Share metrics use average; faculty counts use median.</p>
            </div>
            <div class="peer-sidebar-section">
                <label for="outlookHorizon" class="form-label">Outlook horizon</label>
                <div class="outlook-slider-value"><span id="outlookHorizonLabel">3 years ahead</span></div>
                <input id="outlookHorizon" class="form-range" type="range" min="0" max="15" step="1" value="3">
                <div class="d-flex justify-content-between small text-muted number-tabular">
                    <span>2025</span><span>+15 yrs</span>
                </div>
                <p class="kpi-note mt-2">Outlook extends the average yearly trend. Not a forecast model.</p>
            </div>
        </div>

        </div>{{-- /context-sidebar-content --}}
    </aside>

    <div class="context-main peer-main">

        {{-- ===== PROFILE MODE ===== --}}
        <div id="profileContent" class="d-none">

            {{-- Snapshot KPI tiles --}}
            <div class="row g-3 mb-4" id="profileKpiRow">
                <div class="col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-label">Total Faculty</div>
                        <div class="kpi-value" id="profileTotalFaculty">—</div>
                        <p class="kpi-note" id="profileTotalFacultyNote">—</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-label">Tenure-System Share</div>
                        <div class="kpi-value" id="profileTenureShare">—</div>
                        <p class="kpi-note" id="profileTenureNote">—</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="kpi-card">
                        <div class="kpi-label">Non-Tenure Share</div>
                        <div class="kpi-value" id="profileNonTenureShare">—</div>
                        <p class="kpi-note" id="profileNonTenureNote">—</p>
                    </div>
                </div>
            </div>

            {{-- Institutional profile strip --}}
            <div class="card mb-4">
                <div class="card-header card-header-brand">
                    <div class="fw-semibold">Institutional Profile
                        <span id="profileRankBadge" class="rank-badge ms-2 d-none"></span>
                    </div>
                    <div class="small text-muted">US News 2026 outcome metrics.</div>
                </div>
                <div id="profileI3Body">
                    <div class="card-body">
                        <p class="text-muted small mb-0">Select an institution to view outcome metrics.</p>
                    </div>
                </div>
            </div>

            {{-- Profile composition chart --}}
            <div class="card chart-panel mb-4">
                <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-center">
                    <div>
                        <div class="fw-semibold"><span id="profileChartTitle">Faculty Composition Over Time</span></div>
                        <div class="text-muted small">Historical tenure-system and non-tenure trends. Dashed lines extend average slopes.</div>
                    </div>
                    <div class="d-flex gap-2 align-items-center flex-wrap">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Chart mode">
                            <button type="button" class="btn btn-outline-primary active" id="profileSharesBtn">Shares</button>
                            <button type="button" class="btn btn-outline-primary" id="profileCountsBtn">Counts</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="profileChart" height="100"></canvas>
                </div>
                <div class="card-footer d-flex flex-wrap gap-3 small text-muted">
                    <span><span style="display:inline-block;width:16px;height:3px;background:#0d6efd;vertical-align:middle"></span> Tenure-System</span>
                    <span><span style="display:inline-block;width:16px;height:3px;background:#dc3545;vertical-align:middle"></span> Non-Tenure</span>
                    <span id="profileTotalFacultyLegend"><span style="display:inline-block;width:16px;height:3px;background:#198754;vertical-align:middle"></span> Total Faculty</span>
                    <span><span style="display:inline-block;width:16px;height:3px;background:#a21caf;border-top:2px dashed #a21caf;vertical-align:middle"></span> R1 avg</span>
                    <span><span style="display:inline-block;width:16px;height:3px;background:#0891b2;border-top:2px dashed #0891b2;vertical-align:middle"></span> R2 avg</span>
                </div>
            </div>

        </div>{{-- /profileContent --}}

        {{-- ===== COMPARE MODE ===== --}}
        <div id="compareContent">

        {{-- Hero chart card --}}
        <div class="card chart-panel mb-4">
            <div class="card-header d-flex flex-column flex-lg-row justify-content-between gap-3 align-items-lg-start">
                <div class="flex-grow-1">
                    <div class="d-flex flex-wrap gap-3 align-items-baseline mb-1">
                        <span class="fw-semibold" id="chartTitleMetric">Faculty Composition Over Time</span>
                        <div class="stat-strip d-flex flex-wrap gap-3" id="compareStatStrip"></div>
                    </div>
                    <div class="benchmark-summary d-flex flex-wrap gap-2" id="benchmarkSummary"></div>
                    <div class="outlook-chart-note mt-1" id="chartOutlookNote">Move the outlook slider to extend average slopes beyond the latest actual year.</div>
                </div>
                <div class="chart-measure-control trend-measure-control flex-shrink-0">
                    <label for="workspaceMetric" class="form-label">Metric</label>
                    <select id="workspaceMetric" class="form-select form-select-sm"></select>
                    <div class="metric-definition mt-1" id="metricDefinition">—</div>
                </div>
            </div>
            <div class="card-body">
                <canvas id="peerTrendLineChart" height="105"></canvas>
            </div>
            <div class="card-footer d-flex flex-wrap gap-3 small text-muted justify-content-end align-items-center">
                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#outlookTablePanel" aria-expanded="false" aria-controls="outlookTablePanel">
                    Projection table
                </button>
            </div>
            <div class="collapse" id="outlookTablePanel">
                <div class="table-responsive">
                    <table class="table table-sm table-hover table-custom mb-0">
                        <thead>
                            <tr>
                                <th>Target</th>
                                <th class="text-end">Latest</th>
                                <th class="text-end">Avg yearly change</th>
                                <th class="text-end" id="projectedHeader">Projected</th>
                                <th class="text-end">Gap vs UConn</th>
                            </tr>
                        </thead>
                        <tbody id="outlookBody"></tbody>
                    </table>
                </div>
                <div class="panel-note">Directional context only — not a forecast.</div>
            </div>
        </div>

        {{-- Outcomes comparison --}}
        <div class="card mb-4">
            <div class="card-header card-header-brand">
                <div class="fw-semibold" id="outcomesCardTitle">UConn vs Peers — Institutional Outcomes</div>
                <div class="text-muted small" id="outcomesDescription">Select a focus institution to compare US News outcome metrics side by side.</div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover table-custom mb-0">
                    <thead id="outcomesHead"></thead>
                    <tbody id="outcomesBody"></tbody>
                </table>
            </div>
        </div>

        {{-- Comparison set table --}}
        <div class="card mb-4">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center">
                <div>
                    <div class="fw-semibold">Comparison Set</div>
                    <div class="text-muted small" id="comparisonSetDescription">—</div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Institution</th>
                            <th>Sector</th>
                            <th class="text-end">US News</th>
                            <th class="text-end" id="selectedMetricHeader">Metric</th>
                            <th class="text-end">Slope</th>
                        </tr>
                    </thead>
                    <tbody id="comparisonSetBody"></tbody>
                </table>
            </div>
        </div>

        {{-- Composition vs Outcomes scatter --}}
        <div class="card mb-4">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center">
                <div>
                    <div class="fw-semibold">Composition vs Outcomes</div>
                    <div class="text-muted small">Plot faculty composition metrics against institutional outcomes for the comparison set. Bubble size reflects total faculty.</div>
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
                                <div class="fw-semibold">Faculty Composition vs Institutional Outcome</div>
                                <div class="text-muted small">Does composition mix correlate with institutional outcomes across peers?</div>
                            </div>
                            <div class="card-body">
                                <div class="row g-2 mb-3">
                                    <div class="col-md-6">
                                        <label for="scatterXMetric" class="form-label">X-axis — Faculty metric</label>
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
                                <div class="text-muted small">X-axis is the latest value; Y-axis is the average yearly slope for the selected metric.</div>
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

        {{-- Detailed statistics (collapsed) --}}
        <div class="card mb-4">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between gap-2 align-items-md-center">
                <div>
                    <div class="fw-semibold">Detailed Statistics</div>
                    <div class="text-muted small">UConn trend statistics and trajectory similarity rankings.</div>
                </div>
                <button class="btn btn-sm btn-outline-primary collapse-toggle collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#detailedStatsPanel" aria-expanded="false" aria-controls="detailedStatsPanel">
                    Tables
                </button>
            </div>
            <div class="collapse" id="detailedStatsPanel">

            <div class="px-3 pt-3">
                <div class="fw-semibold mb-1">UConn Trend Statistics</div>
                <div class="text-muted small mb-2">Historical trend statistics for UConn across all tracked metrics.</div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Metric</th>
                            <th class="text-center">Years</th>
                            <th class="text-end">First</th>
                            <th class="text-end">Last</th>
                            <th class="text-end">Change</th>
                            <th class="text-end">% Change</th>
                            <th class="text-end">Slope</th>
                            <th class="text-end">R²</th>
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
                            <td class="text-end number-tabular {{ (float)($trend->absolute_change ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ fmtAbs($trend->metric, $trend->absolute_change) }}</td>
                            <td class="text-end number-tabular {{ (float)($trend->percent_change ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">{{ fmtPct($trend->percent_change) }}</td>
                            <td class="text-end number-tabular">{{ fmtSlope($trend->metric, $trend->slope) }}</td>
                            <td class="text-end number-tabular">{{ $trend->r_squared !== null ? number_format((float)$trend->r_squared, 3) : '—' }}</td>
                            <td class="text-end number-tabular">{{ $trend->p_value !== null ? number_format((float)$trend->p_value, 4) : '—' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($trajectories->isNotEmpty())
            <div class="px-3 pt-4">
                <div class="fw-semibold mb-1">Trajectory Similarity Detail</div>
                <div class="text-muted small mb-2">Institutions ranked by similarity in direction and rate of change.</div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-hover table-custom mb-0">
                    <thead>
                        <tr>
                            <th>Traj. Rank</th>
                            <th>Institution</th>
                            <th>Sector</th>
                            <th class="text-end">US News</th>
                            <th class="text-end">Distance</th>
                            <th class="text-end">Shared</th>
                            <th class="text-end">NTT Slope</th>
                            <th class="text-end">TS Slope</th>
                            <th class="text-end">Faculty Growth</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($trajectories as $row)
                        <tr>
                            <td class="number-tabular">{{ $row->trajectory_similarity_rank }}</td>
                            <td>{{ $row->institution }}</td>
                            <td>{{ $row->sector }}</td>
                            <td class="text-end number-tabular">{{ $usNewsRanks->get($row->unitid) ?? '—' }}</td>
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
            @endif

            </div>{{-- /detailedStatsPanel --}}
        </div>

        </div>{{-- /compareContent --}}

    </div>{{-- /context-main --}}
</div>{{-- /context-workspace --}}

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

// ── DOM refs ────────────────────────────────────────────────────────────────
const modeSelect = document.getElementById('comparisonMode');
const metricSelect = document.getElementById('workspaceMetric');
const setSelect = document.getElementById('workspaceSet');
const peerSelect = document.getElementById('workspacePeer');
const rankedSetControl = document.getElementById('rankedSetControl');
const rankedFocusControl = document.getElementById('rankedFocusControl');
const rankBandControl = document.getElementById('rankBandControl');
const rankBandSlider = document.getElementById('rankBandSlider');
const rankBandLabel = document.getElementById('rankBandLabel');
const rankBandRange = document.getElementById('rankBandRange');
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
const chartOutlookNote = document.getElementById('chartOutlookNote');
const allInstitutionMap = new Map((peerTrendData.allInstitutions || []).map((row) => [row.institution, row]));
const institutionProfiles = peerTrendData.institutionProfiles || {};
const uconnRank = peerTrendData.uconnRank || null;

// ── Workspace mode ────────────────────────────────────────────────────────
let workspaceMode = 'compare';
const profileContent = document.getElementById('profileContent');
const compareContent = document.getElementById('compareContent');
const profileControls = document.getElementById('profileControls');
const compareControlsEl = document.getElementById('compareControls');

document.querySelectorAll('[data-workspace-mode]').forEach((btn) => {
    btn.addEventListener('click', () => {
        workspaceMode = btn.dataset.workspaceMode;
        document.querySelectorAll('[data-workspace-mode]').forEach((b) => b.classList.toggle('active', b.dataset.workspaceMode === workspaceMode));
        profileContent.classList.toggle('d-none', workspaceMode !== 'profile');
        compareContent.classList.toggle('d-none', workspaceMode !== 'compare');
        profileControls.classList.toggle('d-none', workspaceMode !== 'profile');
        compareControlsEl.classList.toggle('d-none', workspaceMode !== 'compare');
        if (workspaceMode === 'compare') renderWorkspace();
        else renderProfileMode();
    });
});

// ── Charts ────────────────────────────────────────────────────────────────
let lineChart;
let profileChart;
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
    const set = peerTrendData.sets?.[setSelect.value] || Object.values(peerTrendData.sets || {})[0] || { institutions: [] };
    if (set.isRankBand) {
        const band = Number(rankBandSlider?.value || 10);
        if (!uconnRank) return { ...set, institutions: [] };
        const minRank = uconnRank - band;
        const maxRank = uconnRank + band;
        const institutions = (peerTrendData.allInstitutions || [])
            .filter((row) => row.usNewsRank !== null && row.usNewsRank !== undefined && row.usNewsRank >= minRank && row.usNewsRank <= maxRank && row.institution !== peerTrendData.uconn)
            .sort((a, b) => (a.usNewsRank || 0) - (b.usNewsRank || 0))
            .map((row) => ({
                institution: row.institution,
                rank: row.usNewsRank,
                source: 'rank_band',
                sector: row.sector,
                carnegie: row.carnegie,
            }));
        return { ...set, institutions };
    }
    return set;
}

function updateRankBandLabel() {
    if (!rankBandLabel || !rankBandRange) return;
    const band = Number(rankBandSlider?.value || 10);
    rankBandLabel.textContent = `±${band} ranks`;
    if (uconnRank) {
        rankBandRange.textContent = `Rank ${Math.max(1, uconnRank - band)}–${uconnRank + band}`;
    } else {
        rankBandRange.textContent = 'UConn rank unavailable';
    }
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

// ── Outcome metrics for scatter Y-axis ──────────────────────────────────
const outcomeMetricDefs = [
    { key: 'outcome_grad_rate', label: '6-Yr Grad Rate', isPercentMetric: false, isOutcome: true, profileKey: 'grad_rate', unit: '%', changeUnit: '%' },
    { key: 'outcome_grad_rate_pell', label: 'Pell Grad Rate', isPercentMetric: false, isOutcome: true, profileKey: 'grad_rate_pell', unit: '%', changeUnit: '%' },
    { key: 'outcome_retention_rate', label: '1st-Yr Retention', isPercentMetric: false, isOutcome: true, profileKey: 'retention_rate', unit: '%', changeUnit: '%' },
    { key: 'outcome_acceptance_rate', label: 'Acceptance Rate', isPercentMetric: false, isOutcome: true, profileKey: 'acceptance_rate', unit: '%', changeUnit: '%' },
    { key: 'outcome_avg_faculty_salary', label: 'Avg Faculty Salary', isPercentMetric: false, isOutcome: true, profileKey: 'avg_faculty_salary', unit: '$', changeUnit: '$' },
    { key: 'outcome_sfr', label: 'Student/Faculty Ratio', isPercentMetric: false, isOutcome: true, profileKey: 'student_faculty_ratio', unit: '', changeUnit: '' },
];

function getOutcomeValue(institution, outcomeKey) {
    const inst = allInstitutionMap.get(institution);
    if (!inst?.unitid) return null;
    const profile = institutionProfiles[String(inst.unitid)];
    if (!profile) return null;
    const def = outcomeMetricDefs.find((d) => d.key === outcomeKey);
    if (!def) return null;
    const v = profile[def.profileKey];
    return v !== null && v !== undefined ? Number(v) : null;
}

function initializeControls() {
    // Profile institution selector — all institutions alphabetically, UConn first
    const profileInstitutionSelect = document.getElementById('profileInstitutionSelect');
    if (profileInstitutionSelect) {
        const allInsts = [...allInstitutionMap.keys()].sort((a, b) => {
            if (a === peerTrendData.uconn) return -1;
            if (b === peerTrendData.uconn) return 1;
            return a.localeCompare(b);
        });
        allInsts.forEach((name) => {
            const opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;
            if (name === peerTrendData.uconn) opt.selected = true;
            profileInstitutionSelect.appendChild(opt);
        });
        profileInstitutionSelect.addEventListener('change', () => {
            renderProfileMode();
        });
    }

    // Profile year selector
    const profileYearSelect = document.getElementById('profileYearSelect');
    if (profileYearSelect && allYears.length > 0) {
        allYears.slice().reverse().forEach((year) => {
            const opt = document.createElement('option');
            opt.value = year;
            opt.textContent = year;
            profileYearSelect.appendChild(opt);
        });
        profileYearSelect.addEventListener('change', renderProfileKpis);
    }

    // Profile outlook + benchmark listeners
    document.getElementById('profileOutlookHorizon')?.addEventListener('input', () => {
        const h = Number(document.getElementById('profileOutlookHorizon').value);
        const lbl = document.getElementById('profileOutlookLabel');
        if (lbl) lbl.textContent = h === 0 ? 'Latest actual year only' : `${h} year${h === 1 ? '' : 's'} ahead`;
        renderProfileChart();
    });
    document.querySelectorAll('.profile-benchmark-toggle').forEach((i) => i.addEventListener('change', renderProfileChart));

    // Profile chart mode buttons
    document.getElementById('profileSharesBtn')?.addEventListener('click', () => {
        profileMode = 'shares';
        document.getElementById('profileSharesBtn').classList.add('active');
        document.getElementById('profileCountsBtn').classList.remove('active');
        renderProfileChart();
    });
    document.getElementById('profileCountsBtn')?.addEventListener('click', () => {
        profileMode = 'counts';
        document.getElementById('profileCountsBtn').classList.add('active');
        document.getElementById('profileSharesBtn').classList.remove('active');
        renderProfileChart();
    });

    // Compare: metric, set, peer selects
    const metricOptions = metrics().map((metric) => ({ value: metric.key, label: metric.label }));
    fillSelect(metricSelect, metricOptions, peerTrendData.defaultMetric);
    fillSelect(scatterXSelect, metricOptions, 'pct_non_tenure');

    // Y-axis: faculty metrics + institutional outcomes group
    if (scatterYSelect) {
        scatterYSelect.innerHTML = '';
        const facultyGroup = document.createElement('optgroup');
        facultyGroup.label = 'Faculty Composition';
        metricOptions.forEach((opt) => {
            const el = document.createElement('option');
            el.value = opt.value;
            el.textContent = opt.label;
            facultyGroup.appendChild(el);
        });
        scatterYSelect.appendChild(facultyGroup);

        const outcomeGroup = document.createElement('optgroup');
        outcomeGroup.label = 'Institutional Outcomes (I3 2026)';
        outcomeMetricDefs.forEach((def) => {
            const el = document.createElement('option');
            el.value = def.key;
            el.textContent = def.label;
            outcomeGroup.appendChild(el);
        });
        scatterYSelect.appendChild(outcomeGroup);
        // Default to grad rate
        scatterYSelect.value = 'outcome_grad_rate';
    }

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
    const isRankBand = !isCustomMode() && (peerTrendData.sets?.[setSelect.value]?.isRankBand ?? false);
    rankedSetControl.classList.toggle('d-none', isCustomMode());
    rankedFocusControl.classList.toggle('d-none', isCustomMode());
    customControls.classList.toggle('d-none', !isCustomMode());
    if (rankBandControl) rankBandControl.classList.toggle('d-none', !isRankBand);
    updateRankBandLabel();
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

// Shared legend hover helpers — emphasise the hovered series, dim the rest.
function legendHoverHandlers(getSeriesKey = (ds) => ds.seriesKey ?? ds.label) {
    return {
        onHover(event, item, legend) {
            const chart = legend.chart;
            const hoveredKey = getSeriesKey(chart.data.datasets[item.datasetIndex]);
            chart.data.datasets.forEach((ds, i) => {
                const isMatch = getSeriesKey(ds) === hoveredKey;
                ds._savedBorderWidth = ds._savedBorderWidth ?? ds.borderWidth;
                ds._savedBorderAlpha  = ds._savedBorderAlpha  ?? null;
                if (isMatch) {
                    ds.borderWidth = (ds._savedBorderWidth || 2) + 1.5;
                    ds.borderDash = [];
                } else {
                    ds.borderWidth = 1;
                    const orig = ds._savedBorderColor ?? ds.borderColor;
                    ds._savedBorderColor = orig;
                    ds.borderColor = typeof orig === 'string'
                        ? orig.replace(/rgba?\(([^)]+)\)/, (_, g) => {
                              const p = g.split(',');
                              if (p.length === 4) p[3] = ' 0.15';
                              else if (p.length === 3) return `rgba(${g}, 0.15)`;
                              return `rgba(${p.join(',')})`;
                          })
                        : orig;
                }
            });
            event.native.target.style.cursor = 'pointer';
            chart.update('none');
        },
        onLeave(event, item, legend) {
            const chart = legend.chart;
            chart.data.datasets.forEach((ds) => {
                if (ds._savedBorderWidth !== undefined) {
                    ds.borderWidth = ds._savedBorderWidth;
                    delete ds._savedBorderWidth;
                }
                if (ds._savedBorderColor !== undefined) {
                    ds.borderColor = ds._savedBorderColor;
                    delete ds._savedBorderColor;
                }
                if (ds._origBorderDash !== undefined) {
                    ds.borderDash = ds._origBorderDash;
                    delete ds._origBorderDash;
                }
            });
            event.native.target.style.cursor = 'default';
            chart.update('none');
        },
    };
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
                ...legendHoverHandlers((ds) => ds.seriesKey ?? ds.label),
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
                    font: { size: 12 },
                    padding: 16,
                    boxWidth: 24,
                    boxHeight: 3,
                    borderRadius: 2,
                    color: '#374151',
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

function pointForInstitution(institution, xMetric, yMetricOrKey) {
    const latest = latestRow(institution);
    const xVal = latest?.[xMetric.key] ?? null;
    let yVal;
    if (typeof yMetricOrKey === 'string' && yMetricOrKey.startsWith('outcome_')) {
        yVal = getOutcomeValue(institution, yMetricOrKey);
    } else {
        yVal = latest?.[yMetricOrKey.key] ?? null;
    }
    if (!latest || xVal === null || yVal === null) return null;

    return {
        x: xVal,
        y: yVal,
        r: latest.bubbleSize || 6,
        institution,
        label: institution,
    };
}

function scatterDataset(label, institutions, xMetric, yMetricOrKey, color, borderColor) {
    return {
        label,
        data: institutions.map((institution) => pointForInstitution(institution, xMetric, yMetricOrKey)).filter(Boolean),
        backgroundColor: color,
        borderColor,
    };
}

function selectedScatterYDef() {
    const val = scatterYSelect?.value;
    if (!val) return metrics()[1] || selectedMetric();
    const outcomeDef = outcomeMetricDefs.find((d) => d.key === val);
    if (outcomeDef) return outcomeDef;
    return metrics().find((m) => m.key === val) || metrics()[1] || selectedMetric();
}

function renderCurrentScatter() {
    const xMetric = metrics().find((metric) => metric.key === scatterXSelect.value) || selectedMetric();
    const yDef = selectedScatterYDef();
    const yKey = yDef.isOutcome ? yDef.key : yDef;
    const focus = focusInstitution();
    const contextInstitutions = comparisonInstitutions(isCustomMode() ? 4 : 12).filter((institution) => institution !== focus);

    const data = {
        datasets: [
            scatterDataset(isCustomMode() ? 'Selected institutions' : 'Comparison set', contextInstitutions, xMetric, yKey, workspaceColors.context, workspaceColors.contextBorder),
            scatterDataset(focus || 'Focused peer', [focus], xMetric, yKey, workspaceColors.focusFill, workspaceColors.focus),
            scatterDataset('University of Connecticut', [peerTrendData.uconn], xMetric, yKey, workspaceColors.uconnFill, workspaceColors.uconn),
        ],
    };

    const yLabel = yDef.label || (yDef.isPercentMetric ? yDef.label : yDef.label);
    const yIsPercent = yDef.isPercentMetric;
    const options = {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: (context) => {
                        const point = context.raw;
                        const xFmt = xMetric.isPercentMetric ? formatValue(point.x, xMetric) : Number(point.x).toLocaleString();
                        const yFmt = yDef.isOutcome
                            ? (point.y !== null ? point.y.toFixed(1) + (yDef.unit || '') : '—')
                            : formatValue(point.y, yDef);
                        return `${point.institution}: ${xFmt} / ${yFmt}`;
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
                title: { display: true, text: yLabel },
                ticks: { callback: (value) => yDef.isOutcome ? value + (yDef.unit || '') : (yDef.isPercentMetric ? value + '%' : value) },
            },
        },
    };

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
    // Replaced by renderStatStrip() in the compare chart card header
}

function renderStatStrip() {
    const strip = document.getElementById('compareStatStrip');
    if (!strip) return;
    const metric = selectedMetric();
    const focus = focusInstitution();
    const uconnTrend = trendRow(peerTrendData.uconn, metric.key);
    const focusTrend = trendRow(focus, metric.key);
    const uconnLatest = latestRow(peerTrendData.uconn);
    const focusLatest = latestRow(focus);

    const items = [
        { label: 'UConn', value: formatValue(uconnLatest?.[metric.key], metric) },
        { label: 'Change', value: formatChange(uconnTrend?.absoluteChange, metric) },
        { label: 'Slope', value: formatSlope(uconnTrend?.slope, metric) },
    ];
    if (focus) {
        items.push({ label: focus.replace('University of ', '').replace('University at ', ''), value: formatValue(focusLatest?.[metric.key], metric) });
    }

    strip.innerHTML = items.map(({ label, value }) =>
        `<span class="stat-strip-item"><span class="stat-strip-label">${label}</span> <span class="stat-strip-value">${value}</span></span>`
    ).join('');
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
        const inst = allInstitutionMap.get(row.institution) || {};
        const usNewsRank = row.rank && currentSet().isRankBand ? row.rank : (inst.usNewsRank ?? '—');
        const rowClass = row.institution === peerTrendData.uconn
            ? 'comparison-row-uconn'
            : (row.institution === focus ? 'comparison-row-focus' : '');
        const rankLabel = isCustomMode() ? `Custom ${row.customOrder ?? ''}` : (row.rank ?? '—');
        return `
            <tr class="${rowClass}">
                <td class="number-tabular">${rankLabel}</td>
                <td>${row.institution}</td>
                <td>${row.sector ?? '—'}</td>
                <td class="text-end number-tabular">${usNewsRank !== null && usNewsRank !== undefined && usNewsRank !== '—' ? '#' + usNewsRank : '—'}</td>
                <td class="text-end number-tabular">${formatValue(latest?.[metric.key], metric)}</td>
                <td class="text-end number-tabular">${formatSlope(trend?.slope, metric)}</td>
            </tr>
        `;
    }).join('');

    comparisonSetBody.innerHTML = rows || '<tr><td colspan="6" class="text-muted">No institutions available for this comparison set.</td></tr>';
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
    renderStatStrip();
    renderBenchmarkSummary();
    renderLineChart();
    renderCurrentScatter();
    renderChangeScatter();
    renderComparisonTable();
    renderOutlook();
    renderOutcomesPanel();
    const titleEl = document.getElementById('chartTitleMetric');
    if (titleEl) titleEl.textContent = selectedMetric().label + ' Over Time';
}

// ── Profile mode ─────────────────────────────────────────────────────────
let profileMode = 'shares';
const profileData = peerTrendData.profileSeries || { labels: [], shares: [], counts: [], points: [] };
const latestByYear = peerTrendData.latestByYear || {};
const allYears = peerTrendData.allYears || [];

function profileInstitution() {
    const sel = document.getElementById('profileInstitutionSelect');
    return sel?.value || peerTrendData.uconn;
}

function profileSeriesRows(inst) {
    return (peerTrendData.series?.[inst] || []).slice().sort((a, b) => a.year - b.year);
}

function profileYear() {
    const sel = document.getElementById('profileYearSelect');
    return sel ? Number(sel.value) : (allYears[allYears.length - 1] || null);
}

function profileHorizon() {
    const sel = document.getElementById('profileOutlookHorizon');
    return sel ? Number(sel.value) : 3;
}

function enabledProfileBenchmarks() {
    return [...document.querySelectorAll('.profile-benchmark-toggle')]
        .filter((i) => i.checked)
        .map((i) => i.value);
}

function renderProfileKpis() {
    const inst = profileInstitution();
    const year = profileYear();
    const rows = profileSeriesRows(inst);
    const snap = rows.find((r) => r.year === year) || rows[rows.length - 1] || null;
    const first = rows[0] || null;

    const tfEl = document.getElementById('profileTotalFaculty');
    const tfNote = document.getElementById('profileTotalFacultyNote');
    const tsEl = document.getElementById('profileTenureShare');
    const tsNote = document.getElementById('profileTenureNote');
    const ntEl = document.getElementById('profileNonTenureShare');
    const ntNote = document.getElementById('profileNonTenureNote');

    if (!snap) {
        [tfEl, tsEl, ntEl].forEach((el) => { if (el) el.textContent = '—'; });
        [tfNote, tsNote, ntNote].forEach((el) => { if (el) el.textContent = `${year} snapshot`; });
        return;
    }

    const tf = snap.total_faculty !== null && snap.total_faculty !== undefined ? Number(snap.total_faculty) : null;
    const ts = snap.pct_tenure_system !== null && snap.pct_tenure_system !== undefined ? Number(snap.pct_tenure_system) : null;
    const nt = snap.pct_non_tenure !== null && snap.pct_non_tenure !== undefined ? Number(snap.pct_non_tenure) : null;

    if (tfEl) tfEl.textContent = tf !== null ? tf.toLocaleString() : '—';
    if (tfNote) {
        const firstTf = first?.total_faculty !== null && first?.total_faculty !== undefined ? Number(first.total_faculty) : null;
        const change = tf !== null && firstTf !== null && first.year !== snap.year ? tf - firstTf : null;
        tfNote.textContent = change !== null
            ? `${change >= 0 ? '+' : ''}${change.toLocaleString()} since ${first.year}`
            : `${snap.year} snapshot`;
    }
    if (tsEl) tsEl.textContent = ts !== null ? ts.toFixed(1) + '%' : '—';
    if (tsNote) {
        const count = ts !== null && tf !== null ? Math.round(ts / 100 * tf).toLocaleString() + ' faculty' : '';
        tsNote.textContent = count || `${snap.year} snapshot`;
    }
    if (ntEl) ntEl.textContent = nt !== null ? nt.toFixed(1) + '%' : '—';
    if (ntNote) {
        const count = nt !== null && tf !== null ? Math.round(nt / 100 * tf).toLocaleString() + ' faculty' : '';
        ntNote.textContent = count || `${snap.year} snapshot`;
    }
}

function renderProfileChart() {
    const inst = profileInstitution();
    const mode = profileMode;
    const rows = profileSeriesRows(inst);
    const horizon = profileHorizon();
    const enabledBenchmarkKeys = enabledProfileBenchmarks();

    // In shares mode, only show percent-axis series (exclude total faculty count line)
    const allSeriesDefs = profileData[mode] || [];
    const seriesDefs = mode === 'shares' ? allSeriesDefs.filter((d) => d.yAxis !== 'faculty') : allSeriesDefs;

    const datasets = seriesDefs.map((def) => {
        const data = rows.map((r) => ({ x: r.year, y: r[def.key] ?? null })).filter((d) => d.y !== null);
        const latestPoint = data[data.length - 1];
        const slopes = data.map((d) => ({ year: d.x, value: d.y }));
        const slope = slopes.length >= 2 ? linearSlope(slopes) : null;

        const outlookData = horizon > 0 && latestPoint && slope !== null ? (() => {
            const pts = [{ x: latestPoint.x, y: latestPoint.y }];
            for (let i = 1; i <= horizon; i++) pts.push({ x: latestPoint.x + i, y: latestPoint.y + slope * i });
            return pts;
        })() : [];

        const base = {
            label: def.label,
            data,
            borderColor: def.color,
            backgroundColor: def.color,
            borderWidth: 2.5,
            pointRadius: 3,
            tension: 0.25,
            spanGaps: true,
            yAxisID: def.yAxis,
        };
        if (outlookData.length > 1) {
            return [base, { ...base, label: def.label + ' outlook', data: outlookData, borderDash: [4, 6], borderWidth: 2, pointRadius: 0, isOutlook: true }];
        }
        return [base];
    }).flat();

    enabledBenchmarkKeys.forEach((bucket) => {
        const benchmarkColor = bucket === 'R1' ? '#a21caf' : '#0891b2';
        const benchmarkDash = bucket === 'R2' ? [4, 4] : [14, 5];
        const outlookDash = bucket === 'R2' ? [2, 4, 8, 4] : [8, 4, 2, 4];
        seriesDefs.forEach((def) => {
            const rows = peerTrendData.benchmarks?.[bucket]?.series?.[def.key] || [];
            if (!rows.length) return;
            const validRows = rows.filter((r) => r.value !== null && r.value !== undefined).map((r) => ({ year: Number(r.year), value: Number(r.value) }));
            const latest = validRows[validRows.length - 1];
            const bSlope = validRows.length >= 2 ? linearSlope(validRows) : null;
            const historyBase = {
                label: `${bucket} avg (${def.label})`,
                data: validRows.map((r) => ({ x: r.year, y: r.value })),
                borderColor: benchmarkColor,
                backgroundColor: benchmarkColor,
                borderDash: benchmarkDash,
                borderWidth: 2,
                pointRadius: 2,
                tension: 0.25,
                spanGaps: true,
                yAxisID: def.yAxis,
                isBenchmark: true,
            };
            datasets.push(historyBase);
            if (horizon > 0 && latest && bSlope !== null) {
                const outlookData = [{ x: latest.year, y: latest.value }];
                for (let i = 1; i <= horizon; i++) outlookData.push({ x: latest.year + i, y: latest.value + bSlope * i });
                datasets.push({
                    ...historyBase,
                    label: `${bucket} avg (${def.label}) outlook`,
                    data: outlookData,
                    borderDash: outlookDash,
                    borderWidth: 1.5,
                    pointRadius: 0,
                    isOutlook: true,
                });
            }
        });
    });

    const isPercent = mode === 'shares';
    const options = {
        responsive: true,
        parsing: false,
        plugins: {
            legend: {
                position: 'bottom',
                ...legendHoverHandlers(),
                labels: {
                    filter: (item, chart) => !chart.datasets[item.datasetIndex]?.isOutlook,
                    font: { size: 12 },
                    padding: 16,
                    boxWidth: 24,
                    boxHeight: 3,
                    borderRadius: 2,
                    color: '#374151',
                },
            },
            tooltip: {
                callbacks: {
                    title: (items) => items.length > 0 ? String(Math.trunc(items[0].parsed.x)) : '',
                    label: (ctx) => {
                        const v = ctx.parsed.y;
                        const formatted = isPercent && ctx.dataset.yAxisID === 'percent' ? (v !== null ? v.toFixed(1) + '%' : '—') : (v !== null ? Number(v).toLocaleString(undefined, { maximumFractionDigits: 0 }) : '—');
                        return `${ctx.dataset.label.replace(' outlook', '')}: ${formatted}`;
                    },
                },
            },
        },
        scales: {
            x: { type: 'linear', title: { display: true, text: 'Year' }, ticks: { precision: 0, callback: (v) => String(Math.trunc(v)) } },
            percent: { type: 'linear', position: 'left', title: { display: true, text: 'Share of Total Faculty' }, ticks: { callback: (v) => v + '%' }, display: isPercent },
            faculty: { type: 'linear', position: 'left', title: { display: true, text: 'Faculty Count' }, display: !isPercent, grid: { drawOnChartArea: true } },
        },
    };

    if (!profileChart) {
        profileChart = new Chart(document.getElementById('profileChart'), { type: 'line', data: { datasets }, options });
    } else {
        profileChart.data = { datasets };
        profileChart.options = options;
        profileChart.update();
    }

    // Hide total faculty legend swatch in shares mode
    const tfLegend = document.getElementById('profileTotalFacultyLegend');
    if (tfLegend) tfLegend.classList.toggle('d-none', isPercent);
}

function renderProfileI3() {
    const inst = profileInstitution();
    const instInfo = allInstitutionMap.get(inst);
    const unitid = instInfo?.unitid ? String(instInfo.unitid) : null;
    const profile = unitid ? institutionProfiles[unitid] : null;

    // Rank badge
    const rankBadge = document.getElementById('profileRankBadge');
    if (rankBadge) {
        const rank = profile?.rank ?? instInfo?.usNewsRank ?? null;
        if (rank !== null && rank !== undefined) {
            rankBadge.textContent = `#${rank} Best Public Universities`;
            rankBadge.classList.remove('d-none');
        } else {
            rankBadge.classList.add('d-none');
        }
    }

    // I3 body
    const body = document.getElementById('profileI3Body');
    if (!body) return;
    if (!profile) {
        body.innerHTML = '<div class="card-body"><p class="text-muted small mb-0">No I3 outcome data available for this institution.</p></div>';
        return;
    }
    const fmtPct = (v) => v !== null && v !== undefined ? Number(v).toFixed(1) + '%' : '—';
    const fmtSal = (v) => v !== null && v !== undefined ? '$' + Number(v).toLocaleString(undefined, { maximumFractionDigits: 0 }) : '—';
    const fmtSfr = (v) => v !== null && v !== undefined ? Number(v).toFixed(1) : '—';
    body.innerHTML = `
        <div class="card-body">
            <div class="row g-3">
                <div class="col-6 col-md-4 col-xl-2"><div class="kpi-card"><div class="kpi-label">6-Yr Grad Rate</div><div class="kpi-value">${fmtPct(profile.grad_rate)}</div></div></div>
                <div class="col-6 col-md-4 col-xl-2"><div class="kpi-card"><div class="kpi-label">Pell Grad Rate</div><div class="kpi-value">${fmtPct(profile.grad_rate_pell)}</div></div></div>
                <div class="col-6 col-md-4 col-xl-2"><div class="kpi-card"><div class="kpi-label">1st-Yr Retention</div><div class="kpi-value">${fmtPct(profile.retention_rate)}</div></div></div>
                <div class="col-6 col-md-4 col-xl-2"><div class="kpi-card"><div class="kpi-label">Acceptance Rate</div><div class="kpi-value">${fmtPct(profile.acceptance_rate)}</div></div></div>
                <div class="col-6 col-md-4 col-xl-2"><div class="kpi-card"><div class="kpi-label">Avg Faculty Salary</div><div class="kpi-value">${fmtSal(profile.avg_faculty_salary)}</div></div></div>
                <div class="col-6 col-md-4 col-xl-2"><div class="kpi-card"><div class="kpi-label">Student/Faculty</div><div class="kpi-value">${fmtSfr(profile.student_faculty_ratio)}</div></div></div>
            </div>
        </div>`;
}

function renderProfileMode() {
    renderProfileKpis();
    renderProfileChart();
    renderProfileI3();
    const inst = profileInstitution();
    const titleEl = document.getElementById('profileChartTitle');
    if (titleEl) titleEl.textContent = `${inst} — Faculty Composition Over Time`;
    const profileOutlookEl = document.getElementById('profileOutlookLabel');
    const h = profileHorizon();
    if (profileOutlookEl) profileOutlookEl.textContent = h === 0 ? 'Latest actual year only' : `${h} year${h === 1 ? '' : 's'} ahead`;
}

function escapeHtml(str) {
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(String(str)));
    return div.innerHTML;
}

function renderOutcomesPanel() {
    const outcomesBody = document.getElementById('outcomesBody');
    const outcomesHead = document.getElementById('outcomesHead');
    const outcomesDescription = document.getElementById('outcomesDescription');
    if (!outcomesBody || !outcomesHead) return;

    const focus = focusInstitution();
    const uconnUnitid = peerTrendData.uconnUnitid ? String(peerTrendData.uconnUnitid) : null;
    const focusUnitid = focus ? (allInstitutionMap.get(focus)?.unitid ? String(allInstitutionMap.get(focus).unitid) : null) : null;
    const uconnProfile = uconnUnitid ? institutionProfiles[uconnUnitid] : null;
    const focusProfile = focusUnitid ? institutionProfiles[focusUnitid] : null;

    const focusLabel = focus || 'Focus Institution';
    if (outcomesDescription) {
        outcomesDescription.textContent = focus
            ? `Comparing UConn vs ${focus} on US News institutional outcome metrics.`
            : 'Select a focus institution to compare institutional outcomes.';
    }

    outcomesHead.innerHTML = `<tr><th>Metric</th><th class="text-end">UConn</th><th class="text-end">${escapeHtml(focusLabel)}</th></tr>`;

    const pct = (v) => (v !== null && v !== undefined) ? `${formatNumber(v, 1)}%` : '—';
    const dollar = (v) => (v !== null && v !== undefined) ? `$${Number(v).toLocaleString(undefined, { maximumFractionDigits: 0 })}` : '—';
    const num = (v, d = 1) => (v !== null && v !== undefined) ? formatNumber(v, d) : '—';
    const rnk = (v) => (v !== null && v !== undefined) ? `#${v}` : '—';

    const outcomeRows = [
        { label: 'US News Public Rank', uconn: rnk(uconnProfile?.rank), focus: rnk(focusProfile?.rank) },
        { label: '6-Year Graduation Rate', uconn: pct(uconnProfile?.grad_rate), focus: pct(focusProfile?.grad_rate) },
        { label: '6-Year Pell Grad Rate', uconn: pct(uconnProfile?.grad_rate_pell), focus: pct(focusProfile?.grad_rate_pell) },
        { label: 'First-Year Retention Rate', uconn: pct(uconnProfile?.retention_rate), focus: pct(focusProfile?.retention_rate) },
        { label: 'Acceptance Rate', uconn: pct(uconnProfile?.acceptance_rate), focus: pct(focusProfile?.acceptance_rate) },
        { label: 'Avg Faculty Salary', uconn: dollar(uconnProfile?.avg_faculty_salary), focus: dollar(focusProfile?.avg_faculty_salary) },
        { label: 'Student / Faculty Ratio', uconn: num(uconnProfile?.student_faculty_ratio), focus: num(focusProfile?.student_faculty_ratio) },
    ];

    outcomesBody.innerHTML = outcomeRows.map(({ label, uconn: u, focus: f }) =>
        `<tr><td>${label}</td><td class="text-end number-tabular">${u}</td><td class="text-end number-tabular">${f}</td></tr>`
    ).join('');
}

if (metrics().length > 0) {
    initializeControls();
    // Start in compare mode (default), pre-render profile for quick toggle
    renderWorkspace();
    renderProfileMode();

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
        updateModeControls();
        updatePeerOptions();
        renderWorkspace();
    });
    if (rankBandSlider) {
        rankBandSlider.addEventListener('input', () => {
            updateRankBandLabel();
            updatePeerOptions();
            renderWorkspace();
        });
    }
}
</script>
@endpush
@endif
