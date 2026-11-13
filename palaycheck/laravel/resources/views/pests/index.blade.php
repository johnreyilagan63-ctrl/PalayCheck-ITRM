@extends('layouts.app')

@section('title', 'Pests')

@section('content')
<h1 class="fw-bold">Pest Information</h1>
<form class="mb-3 d-flex gap-2"><input class="form-control" name="q" value="{{ $search }}" placeholder="Search pests..."><button class="btn btn-pc">Search</button></form>
<div class="row g-3">@foreach ($pests as $pest)@include('partials.pest-card', ['pest' => $pest])@endforeach
@if ($pests->isEmpty())<p class="text-muted">No pests found.</p>@endif</div>
@endsection
