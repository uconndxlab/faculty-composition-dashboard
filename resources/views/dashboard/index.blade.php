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
    <div class="card-header">
        <div class="fw-semibold">{{ $selectedInstitution }} Faculty Composition Over Time</div>
        <div class="text-muted small">Shows whether the institution is shifting toward or away from tenure-system, non-tenure, senior-rank, and assistant-rank faculty over time.</div>
    </div>
    <div class="card-body">
        <canvas id="compositionChart" height="80"></canvas>
    </div>
</section>

@if($isUconnSelected && $peers->isNotEmpty())
<section class="card mb-4">
    <div class="card-header">
        <div class="fw-semibold">Most Similar Institutions by Faculty Composition</div>
        <div class="text-muted small">
            These institutions most closely resemble UConn&rsquo;s {{ $selectedYear }} faculty composition. Composite rank blends tenure-status mix, faculty-rank mix, and the full rank-by-tenure profile. Lower rank means more similar; this is not an overall institutional peer ranking.
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover table-custom mb-0">
            <thead>
                <tr>
                    <th>Composite Rank</th>
                    <th>Institution</th>
                    <th>Sector</th>
                    <th class="text-end">Total Faculty</th>
                    <th class="text-end">Tenure-System</th>
                    <th class="text-end">Non-Tenure</th>
                    <th class="text-end">Assistant</th>
                    <th class="text-end">Senior</th>
                </tr>
            </thead>
            <tbody>
                @foreach($peers as $peer)
                <tr>
                    <td class="number-tabular">{{ $peer['rank'] }}</td>
                    <td>{{ $peer['institution'] }}</td>
                    <td>{{ $peer['sector'] }}</td>
                    <td class="text-end number-tabular">{{ $peer['total_faculty'] }}</td>
                    <td class="text-end number-tabular">{{ $peer['tenure_system_share'] }}</td>
                    <td class="text-end number-tabular">{{ $peer['non_tenure_share'] }}</td>
                    <td class="text-end number-tabular">{{ $peer['assistant_share'] }}</td>
                    <td class="text-end number-tabular">{{ $peer['senior_share'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="panel-note">
        The share columns are visible composition measures from the selected-year faculty summary data. They help explain the ranking, but the exact distance values are not included in the imported similarity file.
    </div>
</section>
@elseif(! $isUconnSelected)
<div class="alert alert-light border small mb-4">
    Peer similarity rankings are currently UConn-centered and are shown only when University of Connecticut is selected.
</div>
@endif

@endif

@endsection

@if($latest)
@push('scripts')
<script>
const chartData = @json($chartData);

new Chart(document.getElementById('compositionChart'), {
    type: 'line',
    data: chartData,
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { position: 'top' },
        },
        scales: {
            x: {
                title: { display: true, text: 'Year' },
            },
            y: {
                title: { display: true, text: 'Percent (%)' },
                ticks: { callback: (v) => v + '%' },
            },
        },
    },
});
</script>
@endpush
@endif
