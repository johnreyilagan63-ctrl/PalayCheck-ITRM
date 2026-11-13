@extends('layouts.app')

@section('title', 'History')

@section('content')
<h1 class="fw-bold">Detection History</h1>
<div class="card p-3">@if ($items->isNotEmpty())<div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th></th><th>Pest</th><th>Date</th><th>Confidence</th><th>Severity</th><th></th></tr></thead><tbody>
@foreach ($items as $detection)<tr><td>@if ($detection->image_file)<img class="thumb" src="{{ asset('static/uploads/' . basename($detection->image_file)) }}" alt="">@endif</td><td><b>{{ $detection->label }}</b></td>
<td>{{ format_datetime($detection->created_at) }}</td><td>{{ number_format((float) $detection->confidence * 100, 0) }}%</td><td>@include('partials.severity', ['severity' => $detection->severity])</td>
<td class="text-end"><a class="btn btn-sm btn-outline-pc" href="{{ route('history.show', $detection) }}">View</a>
<form method="post" action="{{ route('history.destroy', $detection) }}" class="d-inline" onsubmit="return confirm('Delete this scan?')">@csrf<button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form></td></tr>@endforeach</tbody></table></div>
@else<div class="empty"><i class="bi bi-clock-history"></i><p>No scans yet.</p></div>@endif</div>
@endsection
