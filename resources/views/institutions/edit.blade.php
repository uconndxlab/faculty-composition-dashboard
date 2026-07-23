@extends('layouts.app')

@section('title', 'Edit Institution')

@section('content')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h1 class="h4 mb-1">Edit Institution</h1>
            <p class="text-muted mb-0">Update metadata and AAU public designation.</p>
        </div>
        <a href="{{ route('institutions.index') }}" class="btn btn-outline-secondary btn-sm">Back</a>
    </div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('institutions.update', $institution) }}" class="row g-3">
            @csrf
            @method('PUT')

            <div class="col-md-6">
                <label for="name" class="form-label">Name</label>
                <input id="name" name="name" type="text" class="form-control" value="{{ old('name', $institution->name) }}" required>
            </div>

            <div class="col-md-3">
                <label for="unitid" class="form-label">Unit ID</label>
                <input id="unitid" name="unitid" type="text" class="form-control" value="{{ old('unitid', $institution->unitid) }}">
            </div>

            <div class="col-md-3">
                <label for="state" class="form-label">State</label>
                <input id="state" name="state" type="text" class="form-control" value="{{ old('state', $institution->state) }}">
            </div>

            <div class="col-md-6">
                <label for="sector" class="form-label">Sector (raw)</label>
                <input id="sector" name="sector" type="text" class="form-control" value="{{ old('sector', $institution->sector) }}">
            </div>

            <div class="col-md-3">
                <label for="public_private" class="form-label">Public/Private</label>
                <select id="public_private" name="public_private" class="form-select">
                    <option value="" @selected(old('public_private', $institution->public_private) === null)>Unknown</option>
                    <option value="Public" @selected(old('public_private', $institution->public_private) === 'Public')>Public</option>
                    <option value="Private" @selected(old('public_private', $institution->public_private) === 'Private')>Private</option>
                </select>
            </div>

            <div class="col-md-3">
                <label for="carnegie_classification" class="form-label">Carnegie Classification</label>
                <input id="carnegie_classification" name="carnegie_classification" type="text" class="form-control" value="{{ old('carnegie_classification', $institution->carnegie_classification) }}">
            </div>

            <div class="col-12">
                <div class="form-check">
                    <input id="is_aau_public" name="is_aau_public" type="checkbox" value="1" class="form-check-input" @checked(old('is_aau_public', $institution->is_aau_public))>
                    <label for="is_aau_public" class="form-check-label">AAU Public institution</label>
                </div>
                <div class="form-check mt-1">
                    <input id="is_uconn" name="is_uconn" type="checkbox" value="1" class="form-check-input" @checked(old('is_uconn', $institution->is_uconn))>
                    <label for="is_uconn" class="form-check-label">Mark as UConn</label>
                </div>
            </div>

            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn btn-primary">Save changes</button>
                <a href="{{ route('institutions.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
