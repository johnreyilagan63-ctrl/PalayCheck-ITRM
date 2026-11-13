@extends('layouts.app')

@section('title', 'Learning')

@section('content')
<h1 class="fw-bold">Learning Guides</h1><div class="row g-3">
@foreach ($items as $item)<div class="col-md-6 col-lg-4"><div class="card p-3 h-100"><span class="badge bg-pc align-self-start">{{ $item->category }}</span><h5 class="mt-2">{{ $item->title }}</h5><p class="small flex-grow-1">{{ $item->summary }}</p>
<a class="btn btn-sm btn-outline-pc align-self-start" href="{{ route('learning.show', $item) }}">Read</a></div></div>@endforeach</div>
@endsection
