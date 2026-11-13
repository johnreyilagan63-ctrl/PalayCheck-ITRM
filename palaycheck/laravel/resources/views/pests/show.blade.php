@extends('layouts.app')

@section('title', $pest->name)

@section('content')
<a href="{{ route('pests.index') }}" class="small">&larr; All pests</a>
<h1 class="fw-bold mt-2">{{ $pest->name }}</h1><em class="text-muted">{{ $pest->scientific_name }}</em>
@php($img = pest_image($pest->name))
@if ($img)<div class="pest-hero mt-3"><img src="{{ $img }}" alt="{{ $pest->name }}"></div>@endif
<div class="row g-3 mt-2">
@foreach ([['About', $pest->description, 'info-circle'], ['Symptoms', $pest->symptoms, 'exclamation-triangle'], ['Effects on rice', $pest->effects, 'graph-down']] as $section)
<div class="col-lg-4"><div class="card p-3 h-100"><h5><i class="bi bi-{{ $section[2] }} text-pc"></i> {{ $section[0] }}</h5><p class="mb-0">{{ $section[1] }}</p></div></div>
@endforeach</div>
<h4 class="mt-4">Farming advice</h4>
<div class="row g-3">@foreach ($advice as $item)<div class="col-md-4"><div class="card p-3 h-100"><span class="badge bg-pc mb-2 align-self-start">{{ $item->category }}</span><h6>{{ $item->title }}</h6><p class="small mb-0">{{ $item->content }}</p></div></div>@endforeach
@if ($advice->isEmpty())<p class="text-muted">No advice yet.</p>@endif</div>
@endsection
