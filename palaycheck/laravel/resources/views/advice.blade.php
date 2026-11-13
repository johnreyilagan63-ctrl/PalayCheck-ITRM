@extends('layouts.app')

@section('title', 'Advice')

@section('content')
<h1 class="fw-bold">Smart Farming Advice</h1>
<form class="mb-3"><select class="form-select" name="pest" onchange="this.form.submit()"><option value="">All pests</option>
@foreach ($pests as $pest)<option value="{{ $pest->id }}" @selected((string) $pest->id === (string) $pestId)>{{ $pest->name }}</option>@endforeach</select></form>
<div class="row g-3">@foreach ($items as $item)<div class="col-md-6 col-lg-4"><div class="card p-3 h-100">
<div class="d-flex justify-content-between"><span class="badge bg-pc">{{ $item->category }}</span><a class="small" href="{{ route('pests.show', $item->pest_id) }}">{{ $item->pest?->name }}</a></div>
<h6 class="mt-2">{{ $item->title }}</h6><p class="small mb-0">{{ $item->content }}</p></div></div>@endforeach
@if ($items->isEmpty())<p class="text-muted">No advice found.</p>@endif</div>
@endsection
