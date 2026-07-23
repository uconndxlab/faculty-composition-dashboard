@extends('layouts.app')

@section('title', 'Institutions')

@section('content')
<div class="card mb-4">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
        <div>
            <h1 class="h4 mb-1">Institutions</h1>
            <p class="text-muted mb-0">Manage institutional metadata and curate AAU public membership.</p>
        </div>
        <div class="text-muted small number-tabular">{{ number_format($institutions->total()) }} total</div>
    </div>
    <div class="card-body">
        @if(session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form method="GET" action="{{ route('institutions.index') }}" class="row g-2 mb-3">
            <div class="col-md-8 col-lg-6">
                <label for="institutionSearch" class="visually-hidden">Search institutions</label>
                <input id="institutionSearch" type="text" name="q" value="{{ $search }}" class="form-control" placeholder="Search by name, unitid, state, or sector">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('institutions.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-sm table-hover table-custom mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Unit ID</th>
                        <th>Public/Private</th>
                        <th>AAU Public</th>
                        <th>UConn</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($institutions as $institution)
                        <tr>
                            <td>{{ $institution->name }}</td>
                            <td class="number-tabular">{{ $institution->unitid ?? '—' }}</td>
                            <td>{{ $institution->public_private ?? '—' }}</td>
                            <td>
                                <div class="form-check m-0">
                                    <input
                                        class="form-check-input js-aau-toggle"
                                        type="checkbox"
                                        role="switch"
                                        aria-label="Toggle AAU public for {{ $institution->name }}"
                                        data-url="{{ route('institutions.update-aau-public', $institution) }}"
                                        @checked($institution->is_aau_public)
                                    >
                                </div>
                            </td>
                            <td>{{ $institution->is_uconn ? 'Yes' : 'No' }}</td>
                            <td class="text-end">
                                <a href="{{ route('institutions.edit', $institution) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-muted">No institutions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $institutions->links() }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(() => {
    const csrfToken = '{{ csrf_token() }}';
    const toggles = document.querySelectorAll('.js-aau-toggle');

    toggles.forEach((toggle) => {
        toggle.addEventListener('change', async () => {
            const url = toggle.dataset.url;
            const checked = toggle.checked;

            toggle.disabled = true;

            try {
                const response = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        is_aau_public: checked,
                    }),
                });

                if (!response.ok) {
                    throw new Error('Request failed');
                }
            } catch (error) {
                toggle.checked = !checked;
                window.alert('Could not save AAU public update. Please try again.');
            } finally {
                toggle.disabled = false;
            }
        });
    });
})();
</script>
@endpush
