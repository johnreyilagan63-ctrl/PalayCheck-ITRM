@extends('layouts.app')

@section('title', 'Home')

@section('content')
<section class="hero"><div class="row align-items-center"><div class="col-lg-7">
  <h1>Smarter Rice Farming Starts with Early Pest Detection</h1>
  <p>PalayCheck uses machine learning to help farmers identify rice pests, understand their effects, and receive practical farming recommendations.</p>
  <div class="hero-actions">
    <a class="btn btn-gold btn-lg" href="{{ auth()->check() ? route('detection.index') : route('login') }}"><i class="bi bi-camera"></i> Detect a Pest</a>
    <a class="btn btn-outline-light btn-lg" href="{{ route('pests.index') }}"><i class="bi bi-bug"></i> Explore Pest Information</a>
  </div></div>
  <div class="col-lg-5 d-none d-lg-block"><div class="hero-art"><img src="{{ asset('static/img/hero-pests.jpg') }}" alt="Common rice pests in the field"></div></div></div></section>
<h2 class="text-center mt-5 fw-bold">Everything You Need for Smarter Rice Pest Management</h2>
<p class="text-center text-muted">One workflow from field image to confident action.</p>
<div class="row g-3">
@foreach ([['camera', 'AI Pest Detection', 'Identify rice pests live with your camera or from uploaded images.'], ['bug', 'Pest Information', 'Learn about pest characteristics, symptoms, and effects on rice plants.'], ['lightbulb', 'Smart Farming Advice', 'Receive PalayCheck-based recommendations for pest management and prevention.'], ['clock-history', 'Detection History', 'Review previous pest detection results and monitor your records.']] as $feature)
<div class="col-md-6 col-lg-3"><div class="card feature h-100 text-center p-3"><div class="f-icon"><i class="bi bi-{{ $feature[0] }}"></i></div><h5>{{ $feature[1] }}</h5><p class="text-muted">{{ $feature[2] }}</p></div></div>
@endforeach</div>
<section id="about" class="card p-4 my-5"><h2 class="fw-bold">About PalayCheck</h2>
  <p>PalayCheck is a web-based intelligent rice farming assistance system designed to support farmers in identifying rice pests and accessing practical farming information.</p>
  <div class="row"><div class="col-md-4"><i class="bi bi-cpu text-pc"></i> Machine learning for rice pest detection</div>
  <div class="col-md-4"><i class="bi bi-bug text-pc"></i> Pest information and identification</div>
  <div class="col-md-4"><i class="bi bi-lightbulb text-pc"></i> Smart farming recommendations</div>
  <div class="col-md-4 mt-2"><i class="bi bi-journal-bookmark text-pc"></i> Learning resources</div></div></section>
<h2 class="text-center fw-bold">Popular Rice Pests</h2><p class="text-center text-muted">From the PalayCheck pest database.</p>
<div class="row g-3">@foreach ($pests as $pest)@include('partials.pest-card', ['pest' => $pest])@endforeach</div>
@endsection
