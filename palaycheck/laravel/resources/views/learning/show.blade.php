@extends('layouts.app')

@section('title', $item->title)

@section('content')
<a href="{{ route('learning.index') }}" class="small">&larr; Learning</a><article class="card p-4 mt-2"><span class="badge bg-pc align-self-start">{{ $item->category }}</span>
<h1 class="fw-bold mt-2">{{ $item->title }}</h1><p class="lead">{{ $item->summary }}</p><p style="white-space:pre-line">{{ $item->content }}</p></article>
@endsection
