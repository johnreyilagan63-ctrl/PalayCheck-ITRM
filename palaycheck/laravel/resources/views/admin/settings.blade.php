@extends('layouts.admin')

@section('title', 'Settings')

@section('content')
<h2 class="fw-bold">Settings</h2><form method="post" action="{{ route('admin.settings.update') }}" class="card p-4" style="max-width:500px">@csrf
<label class="form-label fw-semibold">Detection confidence threshold (%)</label><input class="form-control mb-3" type="number" min="5" max="99" name="threshold" value="{{ $threshold }}">
<button class="btn btn-pc align-self-start">Save</button></form>
@endsection
