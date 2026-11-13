@extends('layouts.admin')

@section('title', 'Activity')

@section('content')
<h2 class="fw-bold">Activity log</h2><div class="card"><table class="table mb-0 small"><thead class="table-pc"><tr><th>Date</th><th>User</th><th>Action</th><th>Details</th></tr></thead>
@forelse ($rows as $row)<tr><td>{{ format_datetime($row->created_at) }}</td><td>{{ $row->user?->username ?? '—' }}</td><td>{{ $row->action }}</td><td>{{ $row->details }}</td></tr>
@empty<tr><td colspan="4" class="text-center text-muted py-4">No activity.</td></tr>@endforelse</table></div>
@endsection
