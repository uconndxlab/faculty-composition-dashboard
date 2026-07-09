@extends('layouts.app')

@section('title', 'Scenario Modeler')

@section('content')

<div class="mb-4">
    <h1 class="h3">Scenario Modeler</h1>
    <p class="text-muted">Future tool for testing hiring, attrition, and faculty composition assumptions.</p>
</div>

<div class="card">
    <div class="card-body">
        <h2 class="h5">Deferred for MVP</h2>
        <p class="text-muted mb-3">
            Long-range forecasting is not part of the first MVP. With only 10 years of institution-level data, this page should eventually support tunable scenarios rather than present predictions as settled forecasts.
        </p>
        <p class="mb-0 text-muted">
            Good first assumptions later: planned hiring, retirements or attrition, tenure-system mix, non-tenure share, and target faculty rank composition.
        </p>
    </div>
</div>

@endsection
