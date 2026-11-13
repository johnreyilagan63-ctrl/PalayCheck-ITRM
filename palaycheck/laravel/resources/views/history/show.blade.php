@extends('layouts.app')

@section('title', 'Scan result')

@section('content')
<a href="{{ route('history.index') }}" class="small">&larr; History</a>
<div class="row g-4 mt-1"><div class="col-lg-6">@if ($detection->image_file)<img class="img-fluid rounded-3 shadow-sm" src="{{ asset('static/uploads/' . basename($detection->image_file)) }}" alt="">@else<div class="empty"><i class="bi bi-image"></i><p>No image was saved for this scan.</p></div>@endif</div>
<div class="col-lg-6"><h1 class="fw-bold">{{ $detection->label }}</h1>
<p>@include('partials.severity', ['severity' => $detection->severity]) &nbsp; Confidence <b>{{ number_format((float) $detection->confidence * 100, 1) }}%</b> &nbsp; {{ (int) $detection->count }} detection(s)</p>
<p class="text-muted small">{{ format_datetime($detection->created_at, 'F d, Y H:i') }} UTC · {{ $detection->source }}</p>
@if ($detection->pest)<p>{{ $detection->pest->description }}</p><p><b>Symptoms:</b> {{ $detection->pest->symptoms }}</p><a href="{{ route('pests.show', $detection->pest) }}" class="btn btn-sm btn-outline-pc">Full pest info</a>
<h5 class="mt-4">Recommended actions</h5>@foreach ($advice as $item)<div class="card p-3 mb-2"><b>{{ $item->title }}</b><span class="small">{{ $item->content }}</span></div>@endforeach
@else<p class="text-muted">This detection is not linked to a pest in the database.</p>@endif</div></div>
@endsection
