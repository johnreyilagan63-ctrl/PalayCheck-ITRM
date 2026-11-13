@extends('layouts.admin')

@section('title', 'Detection Monitoring')

@section('content')
<h2 class="fw-bold">Detection monitoring</h2><p class="text-muted">{{ $rows->count() }} records. Monitoring only — AI results are never edited by hand.</p>
<form class="d-flex gap-2 mb-3"><input class="form-control" name="q" value="{{ $search }}" placeholder="Search pest or username..."><select class="form-select" name="severity" style="max-width:300px"><option value="">All severities</option>@foreach (['low', 'medium', 'high'] as $severity)<option @selected($severityFilter === $severity)>{{ $severity }}</option>@endforeach</select><button class="btn btn-pc">Filter</button></form>
<div class="card"><table class="table align-middle mb-0"><thead class="table-pc"><tr><th>Date</th><th>User</th><th>Pest</th><th>Confidence</th><th>Severity</th><th class="text-end">Actions</th></tr></thead><tbody>
@forelse ($rows as $detection)<tr><td>{{ format_datetime($detection->created_at) }}</td><td>{{ $detection->user?->username }}</td><td>{{ $detection->label }}</td><td>{{ number_format((float) $detection->confidence * 100, 0) }}%</td><td>@include('partials.severity', ['severity' => $detection->severity])</td>
<td class="text-end"><a class="btn btn-sm btn-outline-pc" target="_blank" href="{{ route('history.show', $detection) }}">View</a>
<form method="post" action="{{ route('admin.detections.destroy', $detection) }}" class="d-inline" onsubmit="return confirm('Delete?')">@csrf<button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr>
@empty<tr><td colspan="6" class="text-center text-muted py-4">No detections yet.</td></tr>@endforelse</tbody></table></div>
@endsection
