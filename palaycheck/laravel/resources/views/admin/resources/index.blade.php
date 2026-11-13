@extends('layouts.admin')

@section('title', $config['title'])

@section('content')
<div class="d-flex justify-content-between mb-3"><h2 class="fw-bold">{{ $config['title'] }}</h2><a class="btn btn-pc" href="{{ route('admin.resources.create', $resource) }}"><i class="bi bi-plus-lg"></i> Add new</a></div>
<form class="d-flex gap-2 mb-3"><input class="form-control" name="q" value="{{ $search }}" placeholder="Search..."><button class="btn btn-pc">Filter</button></form>
<div class="card"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead class="table-pc"><tr>@foreach ($config['columns'] as $column => $label)<th>{{ $label }}</th>@endforeach<th class="text-end">Actions</th></tr></thead><tbody>
@forelse ($rows as $row)<tr>@foreach ($config['columns'] as $column => $label)<td>{{ data_get($row, $column) }}</td>@endforeach
<td class="text-end"><a class="btn btn-sm btn-outline-pc" href="{{ route('admin.resources.edit', [$resource, $row->id]) }}">Edit</a>
<form method="post" action="{{ route('admin.resources.destroy', [$resource, $row->id]) }}" class="d-inline" onsubmit="return confirm('Delete this record?')">@csrf<button class="btn btn-sm btn-outline-danger">Delete</button></form></td></tr>
@empty<tr><td colspan="{{ count($config['columns']) + 1 }}" class="text-center text-muted py-4">No records.</td></tr>@endforelse</tbody></table></div></div>
@endsection
