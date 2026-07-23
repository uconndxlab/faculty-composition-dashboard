@extends('layouts.app')

@section('title', 'Imports')

@section('content')

@if(session('status'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('status') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <div class="fw-semibold mb-1">Upload validation failed.</div>
        <ul class="mb-0 ps-3">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<div class="context-workspace peer-workspace imports-workspace">
    <aside class="context-sidebar peer-sidebar imports-sidebar" aria-label="Import controls">
        <button class="sidebar-collapse-toggle context-sidebar-toggle" type="button" data-context-sidebar-toggle aria-label="Collapse import controls">
            ‹
        </button>
        <div class="context-sidebar-rail-label">Imports</div>
        <div class="context-sidebar-content">
        <div class="context-sidebar-header">
            <div class="page-kicker">Data Operations</div>
            <h1 class="page-title">Imports</h1>
            <p class="page-subtitle">Load IPEDS analytical data from local CSV exports and refresh the comparison workspace.</p>
            <span class="metric-chip">database/data/</span>
        </div>
        <div class="peer-sidebar-section">
            <div class="peer-sidebar-heading">Bulk Refresh</div>
            <p class="kpi-note mb-3">Run every importer against the local export files in <code>database/data/</code> (upload inputs are ignored for this action).</p>
            <form method="POST" action="{{ url('/imports/all') }}">
                @csrf
                <button type="submit" class="btn btn-primary w-100">
                    Import All Datasets
                </button>
            </form>
        </div>
        </div>
    </aside>

    <div class="context-main peer-main imports-main">

<div class="row g-3 mb-4">

    {{-- Faculty Summary --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header card-header-brand d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Faculty Summary</span>
                <span class="badge bg-light text-dark number-tabular">{{ number_format($counts['faculty_summaries']) }} rows</span>
            </div>
            <div class="card-body">
                <p class="small mb-1">Table: <code>faculty_summaries</code></p>
                <p class="small text-muted mb-3">File: <code>Faculty Hiring Policy IPEDS comparison and Model(Faculty Summary).csv</code></p>
                <form method="POST" action="{{ url('/imports/faculty-summary') }}" enctype="multipart/form-data">
                    @csrf
                    <label for="faculty-summary-file" class="form-label small mb-1">Optional upload (.csv)</label>
                    <input id="faculty-summary-file" type="file" name="faculty_summary_file" accept=".csv,.txt,text/csv" class="form-control form-control-sm mb-2">
                    <p class="small text-muted mb-3">If no file is uploaded, the local file will be used.</p>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Import</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Faculty Trends --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header card-header-brand d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Faculty Trends</span>
                <span class="badge bg-light text-dark number-tabular">{{ number_format($counts['faculty_trends']) }} rows</span>
            </div>
            <div class="card-body">
                <p class="small mb-1">Table: <code>faculty_trends</code></p>
                <p class="small text-muted mb-3">File: <code>Faculty Hiring Policy IPEDS comparison and Model(Faculty Trends).csv</code></p>
                <form method="POST" action="{{ url('/imports/faculty-trends') }}" enctype="multipart/form-data">
                    @csrf
                    <label for="faculty-trends-file" class="form-label small mb-1">Optional upload (.csv)</label>
                    <input id="faculty-trends-file" type="file" name="faculty_trends_file" accept=".csv,.txt,text/csv" class="form-control form-control-sm mb-2">
                    <p class="small text-muted mb-3">If no file is uploaded, the local file will be used.</p>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Import</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Similarity Rankings --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header card-header-brand d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Similarity Rankings</span>
                <span class="badge bg-light text-dark number-tabular">{{ number_format($counts['similarity_rankings']) }} rows</span>
            </div>
            <div class="card-body">
                <p class="small mb-1">Table: <code>similarity_rankings</code></p>
                <p class="small text-muted mb-1">File: <code>Faculty Hiring Policy IPEDS comparison and Model(Similarity Ranking).csv</code></p>
                <p class="small text-muted mb-3">Columns <code>9d_similarity_rank</code> &rarr; <code>nine_d_similarity_rank</code>, <code>9d_rank_pct</code> &rarr; <code>nine_d_rank_pct</code></p>
                <form method="POST" action="{{ url('/imports/similarity-rankings') }}" enctype="multipart/form-data">
                    @csrf
                    <label for="similarity-rankings-file" class="form-label small mb-1">Optional upload (.csv)</label>
                    <input id="similarity-rankings-file" type="file" name="similarity_rankings_file" accept=".csv,.txt,text/csv" class="form-control form-control-sm mb-2">
                    <p class="small text-muted mb-3">If no file is uploaded, the local file will be used.</p>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Import</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Trajectory Similarity --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header card-header-brand d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Trajectory Similarity</span>
                <span class="badge bg-light text-dark number-tabular">{{ number_format($counts['trajectory_similarities']) }} rows</span>
            </div>
            <div class="card-body">
                <p class="small mb-1">Table: <code>trajectory_similarities</code></p>
                <p class="small text-muted mb-3">File: <code>Faculty Hiring Policy IPEDS comparison and Model(Trajectory).csv</code></p>
                <form method="POST" action="{{ url('/imports/trajectory-similarities') }}" enctype="multipart/form-data">
                    @csrf
                    <label for="trajectory-similarities-file" class="form-label small mb-1">Optional upload (.csv)</label>
                    <input id="trajectory-similarities-file" type="file" name="trajectory_similarities_file" accept=".csv,.txt,text/csv" class="form-control form-control-sm mb-2">
                    <p class="small text-muted mb-3">If no file is uploaded, the local file will be used.</p>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Import</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Forecasting --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header card-header-brand d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Forecasting</span>
                <span class="badge bg-light text-dark number-tabular">{{ number_format($counts['forecasting_outputs']) }} rows</span>
            </div>
            <div class="card-body">
                <p class="small mb-1">Table: <code>forecasting_outputs</code></p>
                <p class="small text-muted mb-3">File: <code>Faculty Hiring Policy IPEDS comparison and Model(Forecasting).csv</code></p>
                <form method="POST" action="{{ url('/imports/forecasting') }}" enctype="multipart/form-data">
                    @csrf
                    <label for="forecasting-file" class="form-label small mb-1">Optional upload (.csv)</label>
                    <input id="forecasting-file" type="file" name="forecasting_file" accept=".csv,.txt,text/csv" class="form-control form-control-sm mb-2">
                    <p class="small text-muted mb-3">If no file is uploaded, the local file will be used.</p>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Import</button>
                </form>
            </div>
        </div>
    </div>

    {{-- Institutional Rankings --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header card-header-brand d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Institutional Rankings</span>
                <span class="badge bg-light text-dark number-tabular">{{ number_format($counts['institutional_rankings']) }} rows</span>
            </div>
            <div class="card-body">
                <p class="small mb-1">Table: <code>institutional_rankings</code></p>
                <p class="small text-muted mb-1">File: <code>I3_Ranking_2026(Dataset).csv</code></p>
                <p class="small text-muted mb-3">Column <code>ipeds_id</code> &rarr; <code>unitid</code>. Curated subset of metrics imported.</p>
                <form method="POST" action="{{ url('/imports/institutional-rankings') }}" enctype="multipart/form-data">
                    @csrf
                    <label for="institutional-rankings-file" class="form-label small mb-1">Optional upload (.csv)</label>
                    <input id="institutional-rankings-file" type="file" name="institutional_rankings_file" accept=".csv,.txt,text/csv" class="form-control form-control-sm mb-2">
                    <p class="small text-muted mb-3">If no file is uploaded, the local file will be used.</p>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Import</button>
                </form>
            </div>
        </div>
    </div>

</div>

<div class="panel-note rounded border">
    Imported files preserve the IR-provided analytical structure. The Forecasting dataset powers the Modeling page scenario explorer.
</div>

    </div>
</div>

@endsection

