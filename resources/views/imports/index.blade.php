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
            <span class="metric-chip">storage/app/private/</span>
        </div>
        <div class="peer-sidebar-section">
            <div class="peer-sidebar-heading">Bulk Refresh</div>
            <p class="kpi-note mb-3">Run every importer against the local export files.</p>
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
                <p class="small text-muted mb-3">File: <code>faculty_exports_20260706_200639(faculty_summary).csv</code></p>
                <form method="POST" action="{{ url('/imports/faculty-summary') }}">
                    @csrf
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
                <p class="small text-muted mb-3">File: <code>faculty_exports_20260706_200639(faculty_trends).csv</code></p>
                <form method="POST" action="{{ url('/imports/faculty-trends') }}">
                    @csrf
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
                <p class="small text-muted mb-1">File: <code>faculty_exports_20260706_200639(similarity_ranks).csv</code></p>
                <p class="small text-muted mb-3">Column <code>9d_similarity_rank</code> &rarr; <code>nine_d_similarity_rank</code></p>
                <form method="POST" action="{{ url('/imports/similarity-rankings') }}">
                    @csrf
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
                <p class="small text-muted mb-3">File: <code>faculty_exports_20260706_200639(trajectory_similarity).csv</code></p>
                <form method="POST" action="{{ url('/imports/trajectory-similarities') }}">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary">Import</button>
                </form>
            </div>
        </div>
    </div>

</div>

<div class="panel-note rounded border">
    For MVP, imported files preserve the IR-provided analytical structure. Normalization can happen later if the dashboard needs cross-dataset joins beyond institution/year/metric.
</div>

    </div>
</div>

@endsection

