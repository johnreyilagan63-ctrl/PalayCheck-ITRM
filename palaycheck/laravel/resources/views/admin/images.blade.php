@extends('layouts.admin')

@section('title', 'Images')

@section('content')
<h2 class="fw-bold">Uploaded images</h2><div class="row g-3">
@forelse ($images as $detection)<div class="col-6 col-md-3"><div class="card"><img class="card-img-top" style="height:160px;object-fit:cover" src="{{ asset('static/uploads/' . basename($detection->image_file)) }}" alt="">
<div class="p-2 small"><b>{{ $detection->label }}</b><br>{{ $detection->user?->username }} · {{ format_datetime($detection->created_at, 'M d') }}
<form method="post" action="{{ route('admin.detections.destroy', $detection) }}" onsubmit="return confirm('Delete image and record?')">@csrf<button class="btn btn-sm btn-outline-danger mt-1">Delete</button></form></div></div></div>
@empty<p class="text-muted">No images yet.</p>@endforelse</div>
@endsection
