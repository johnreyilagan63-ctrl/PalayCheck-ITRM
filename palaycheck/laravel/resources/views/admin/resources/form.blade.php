@extends('layouts.admin')

@section('title', $config['title'])

@section('content')
<h2 class="fw-bold">{{ $isNew ? 'New' : 'Edit' }} — {{ $config['title'] }}</h2>
<form method="post" action="{{ $isNew ? route('admin.resources.store', $resource) : route('admin.resources.update', [$resource, $record->id]) }}" class="card p-4" style="max-width:760px">@csrf
@foreach ($config['fields'] as $name => $field)
  @php($current = $field['type'] === 'password' ? '' : old($name, $record->{$name} ?? ''))
  <label class="form-label fw-semibold">{{ $field['label'] }}</label>
  @if ($field['type'] === 'textarea')
    <textarea class="form-control mb-3" rows="4" name="{{ $name }}" @required($field['required'] ?? false)>{{ $current }}</textarea>
  @elseif ($field['type'] === 'pest')
    <select class="form-select mb-3" name="{{ $name }}" required>@foreach ($pests as $pest)<option value="{{ $pest->id }}" @selected((string) $current === (string) $pest->id)>{{ $pest->name }}</option>@endforeach</select>
  @elseif ($field['type'] === 'select')
    <select class="form-select mb-3" name="{{ $name }}" @required($field['required'] ?? false)>@foreach ($field['options'] as $option)<option @selected((string) $current === (string) $option)>{{ $option }}</option>@endforeach</select>
  @else
    <input class="form-control mb-3" type="{{ $field['type'] }}" name="{{ $name }}" value="{{ $current }}" @required(($field['required'] ?? false) && $field['type'] !== 'password')>
  @endif
@endforeach
<div><button class="btn btn-pc">Save</button> <a class="btn btn-outline-secondary" href="{{ route('admin.resources.index', $resource) }}">Cancel</a></div></form>
@endsection
