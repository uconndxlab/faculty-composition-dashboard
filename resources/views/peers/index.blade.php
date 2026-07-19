@extends('layouts.app')

@section('title', 'Peer Comparison')

@section('content')

<div class="mb-4">
    <h1 class="h3">Peer Comparison</h1>
    <p class="text-muted">Who looks like UConn now, and who is changing like UConn over time.</p>
</div>

@if($rankings->flatten()->isEmpty())
    <div class="alert alert-warning">
        No similarity ranking data found. Visit <a href="{{ url('/imports') }}">Imports</a> to load data.
    </div>
@else

<div class="alert alert-info small">
    Lower rank means more similar to UConn. Current similarity compares faculty composition in 2025; trajectory similarity compares direction and rate of change over time.
</div>

@if(! empty($explorerData))
<div class="card mb-4">
    <div class="card-header">
        <div class="fw-semibold">Peer Composition Explorer</div>
        <div class="text-muted small">
            The rankings come from the imported similarity file. The charts use latest-year faculty summary percentages to make the comparison visible; they do not reproduce exact distance calculations because the imported ranking file does not include distance values.
        </div>
    </div>
    <div class="card-body">
        <div class="row g-3 align-items-end mb-4">
            <div class="col-md-4">
                <label for="similarityMode" class="form-label small text-muted">Similarity mode</label>
                <select id="similarityMode" class="form-select">
                    @foreach($rankDimensions as $key => $dimension)
                        @if(isset($explorerData[$key]))
                            <option value="{{ $key }}">{{ $dimension['label'] }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <label for="peerInstitution" class="form-label small text-muted">Compare UConn to</label>
                <select id="peerInstitution" class="form-select" data-search-select data-search-placeholder="Search peer institutions"></select>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Latest year</div>
                <div class="fw-semibold">{{ $latestYear }}</div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-6">
                <div class="border rounded p-3 h-100">
                    <div class="fw-semibold mb-1">Rank Mix Comparison</div>
                    <div class="text-muted small mb-3">X-axis is assistant professor share. Y-axis is senior faculty share. Bubble size reflects total faculty.</div>
                    <canvas id="rankMixChart" height="180"></canvas>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="border rounded p-3 h-100">
                    <div class="fw-semibold mb-1">Tenure-System Composition</div>
                    <div class="text-muted small mb-3">X-axis is tenured share. Y-axis is tenure-track share. Non-tenure share is shown in the bar chart below.</div>
                    <canvas id="tenureMixChart" height="180"></canvas>
                </div>
            </div>
        </div>

        <div class="border rounded p-3">
            <div class="fw-semibold mb-1">UConn vs Selected Institution</div>
            <div class="text-muted small mb-3">Side-by-side comparison of the visible composition measures behind the ranking.</div>
            <canvas id="peerBarChart" height="90"></canvas>
        </div>
    </div>
</div>
@endif

<div class="card mb-4">
    <div class="card-body">
        <h2 class="h5">Current Similarity Dimensions</h2>
        <div class="row g-3">
            @foreach($rankDimensions as $dimension)
                <div class="col-md-6 col-xl-3">
                    <div class="border rounded p-3 h-100">
                        <div class="fw-semibold mb-1">{{ $dimension['label'] }}</div>
                        <p class="small text-muted mb-0">{{ $dimension['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-3" id="peerTabs" role="tablist">
    @foreach($rankDimensions as $key => $dimension)
        <li class="nav-item" role="presentation">
            <button class="nav-link {{ $loop->first ? 'active' : '' }}" id="{{ $key }}-tab" data-bs-toggle="tab" data-bs-target="#{{ $key }}-pane" type="button" role="tab">
                {{ $dimension['label'] }}
            </button>
        </li>
    @endforeach
</ul>

<div class="tab-content mb-4" id="peerTabsContent">
    @foreach($rankDimensions as $key => $dimension)
        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $key }}-pane" role="tabpanel" tabindex="0">
            <div class="card">
                <div class="card-header">Top Institutions by {{ $dimension['label'] }} Rank</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Rank</th>
                                <th>Institution</th>
                                <th>Sector</th>
                                <th>Carnegie Classification</th>
                                <th class="text-end">Total Faculty</th>
                                <th class="text-end">US News Public Rank</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rankings[$key] as $peer)
                                <tr>
                                    <td>{{ $peer->{$dimension['column']} }}</td>
                                    <td>{{ $peer->institution }}</td>
                                    <td>{{ $peer->sector }}</td>
                                    <td>{{ $peer->carnegie_classification }}</td>
                                    <td class="text-end">{{ number_format($peer->total_faculty) }}</td>
                                    <td class="text-end number-tabular">{{ $usNewsRanks->get($peer->unitid) ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endforeach
</div>

@if($trajectories->isNotEmpty())
<div class="card mb-4">
    <div class="card-header">Trajectory Similarity</div>
    <div class="card-body small text-muted border-bottom">
        Trajectory similarity asks which institutions are moving like UConn over time. It compares rates and direction of change, not whether the current faculty mix is identical.
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Rank</th>
                    <th>Institution</th>
                    <th>Sector</th>
                    <th>Carnegie Classification</th>
                    <th class="text-end">US News Public Rank</th>
                    <th class="text-end">Distance</th>
                    <th class="text-end">Shared Metrics</th>
                    <th class="text-end">Non-Tenure Slope</th>
                    <th class="text-end">Tenure-System Slope</th>
                    <th class="text-end">Faculty Growth</th>
                </tr>
            </thead>
            <tbody>
                @foreach($trajectories as $row)
                    <tr>
                        <td>{{ $row->trajectory_similarity_rank }}</td>
                        <td>{{ $row->institution }}</td>
                        <td>{{ $row->sector }}</td>
                        <td>{{ $row->carnegie_classification }}</td>
                        <td class="text-end number-tabular">{{ $usNewsRanks->get($row->unitid) ?? '—' }}</td>
                        <td class="text-end">{{ $row->trajectory_distance_from_uconn !== null ? number_format((float) $row->trajectory_distance_from_uconn, 4) : '—' }}</td>
                        <td class="text-end">{{ $row->n_shared_trajectory_metrics }}</td>
                        <td class="text-end">{{ $row->slope_pct_non_tenure !== null ? number_format((float) $row->slope_pct_non_tenure * 100, 2) . ' pp/yr' : '—' }}</td>
                        <td class="text-end">{{ $row->slope_pct_tenure_system !== null ? number_format((float) $row->slope_pct_tenure_system * 100, 2) . ' pp/yr' : '—' }}</td>
                        <td class="text-end">{{ $row->pct_change_total_faculty !== null ? number_format((float) $row->pct_change_total_faculty * 100, 1) . '%' : '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@endif

@endsection

@if(! empty($explorerData))
@push('scripts')
<script>
const explorerData = @json($explorerData);

const chartColors = {
    uconn: '#000e2f',
    uconnFill: 'rgba(0, 14, 47, 0.85)',
    selected: '#0d6efd',
    selectedFill: 'rgba(13, 110, 253, 0.75)',
    peerFill: 'rgba(108, 117, 125, 0.35)',
    peerBorder: 'rgba(108, 117, 125, 0.65)',
};

const modeSelect = document.getElementById('similarityMode');
const peerSelect = document.getElementById('peerInstitution');

let rankMixChart;
let tenureMixChart;
let peerBarChart;

function currentModeData() {
    return explorerData[modeSelect.value];
}

function currentPeer() {
    const mode = currentModeData();
    return mode.institutions.find((institution) => institution.institution === peerSelect.value) || mode.institutions[0];
}

function pointLabel(row) {
    const rank = row.rank ? `#${row.rank} ` : '';
    return `${rank}${row.institution}`;
}

function bubblePoint(row, xKey, yKey) {
    return {
        x: row[xKey],
        y: row[yKey],
        r: row.bubbleSize,
        label: pointLabel(row),
        institution: row.institution,
    };
}

function validPoint(row, xKey, yKey) {
    return row[xKey] !== null && row[yKey] !== null;
}

function buildBubbleDatasets(mode, selected, xKey, yKey) {
    const peerPoints = mode.institutions
        .filter((row) => row.institution !== selected.institution)
        .filter((row) => validPoint(row, xKey, yKey))
        .map((row) => bubblePoint(row, xKey, yKey));

    const selectedPoint = validPoint(selected, xKey, yKey) ? [bubblePoint(selected, xKey, yKey)] : [];
    const uconnPoint = validPoint(mode.uconn, xKey, yKey) ? [bubblePoint(mode.uconn, xKey, yKey)] : [];

    return [
        {
            label: 'Top-ranked institutions',
            data: peerPoints,
            backgroundColor: chartColors.peerFill,
            borderColor: chartColors.peerBorder,
        },
        {
            label: selected.institution,
            data: selectedPoint,
            backgroundColor: chartColors.selectedFill,
            borderColor: chartColors.selected,
        },
        {
            label: 'University of Connecticut',
            data: uconnPoint,
            backgroundColor: chartColors.uconnFill,
            borderColor: chartColors.uconn,
        },
    ];
}

function bubbleOptions(xTitle, yTitle) {
    return {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            tooltip: {
                callbacks: {
                    label: (context) => {
                        const point = context.raw;
                        return `${point.label}: ${context.parsed.x}% / ${context.parsed.y}%`;
                    },
                },
            },
        },
        scales: {
            x: {
                title: { display: true, text: xTitle },
                ticks: { callback: (v) => v + '%' },
            },
            y: {
                title: { display: true, text: yTitle },
                ticks: { callback: (v) => v + '%' },
            },
        },
    };
}

function buildBarData(mode, selected) {
    const labels = [
        'Tenure-System',
        'Non-Tenure',
        'Assistant',
        'Associate',
        'Full Professor',
        'Senior Faculty',
    ];

    return {
        labels,
        datasets: [
            {
                label: 'University of Connecticut',
                data: [
                    mode.uconn.pctTenureSystem,
                    mode.uconn.pctNonTenure,
                    mode.uconn.pctAssistant,
                    mode.uconn.pctAssociate,
                    mode.uconn.pctFull,
                    mode.uconn.pctSenior,
                ],
                backgroundColor: chartColors.uconnFill,
                borderColor: chartColors.uconn,
                borderWidth: 1,
            },
            {
                label: selected.institution,
                data: [
                    selected.pctTenureSystem,
                    selected.pctNonTenure,
                    selected.pctAssistant,
                    selected.pctAssociate,
                    selected.pctFull,
                    selected.pctSenior,
                ],
                backgroundColor: chartColors.selectedFill,
                borderColor: chartColors.selected,
                borderWidth: 1,
            },
        ],
    };
}

function updatePeerOptions() {
    const mode = currentModeData();
    peerSelect.innerHTML = '';

    mode.institutions.forEach((row) => {
        const option = document.createElement('option');
        option.value = row.institution;
        option.textContent = `${row.rank}. ${row.institution}`;
        peerSelect.appendChild(option);
    });
    window.refreshSearchSelect(peerSelect);
}

function renderCharts() {
    const mode = currentModeData();
    const selected = currentPeer();

    if (! selected) {
        return;
    }

    const rankMixData = {
        datasets: buildBubbleDatasets(mode, selected, 'pctAssistant', 'pctSenior'),
    };
    const tenureMixData = {
        datasets: buildBubbleDatasets(mode, selected, 'pctTenured', 'pctTenureTrack'),
    };
    const barData = buildBarData(mode, selected);

    if (! rankMixChart) {
        rankMixChart = new Chart(document.getElementById('rankMixChart'), {
            type: 'bubble',
            data: rankMixData,
            options: bubbleOptions('Assistant Professor Share', 'Senior Faculty Share'),
        });
    } else {
        rankMixChart.data = rankMixData;
        rankMixChart.update();
    }

    if (! tenureMixChart) {
        tenureMixChart = new Chart(document.getElementById('tenureMixChart'), {
            type: 'bubble',
            data: tenureMixData,
            options: bubbleOptions('Tenured Share', 'Tenure-Track Share'),
        });
    } else {
        tenureMixChart.data = tenureMixData;
        tenureMixChart.update();
    }

    if (! peerBarChart) {
        peerBarChart = new Chart(document.getElementById('peerBarChart'), {
            type: 'bar',
            data: barData,
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: { display: true, text: 'Share of Total Faculty' },
                        ticks: { callback: (v) => v + '%' },
                    },
                },
            },
        });
    } else {
        peerBarChart.data = barData;
        peerBarChart.update();
    }
}

modeSelect.addEventListener('change', () => {
    updatePeerOptions();
    renderCharts();
});
peerSelect.addEventListener('change', () => {
    renderCharts();
});

updatePeerOptions();
renderCharts();
</script>
@endpush
@endif
