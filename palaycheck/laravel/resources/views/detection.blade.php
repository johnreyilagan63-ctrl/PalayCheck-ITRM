@extends('layouts.app')

@section('title', 'Detect Pest')

@section('content')
<h1 class="fw-bold">Scan Rice Pest</h1><p class="text-muted">Point your camera at the pest or damaged plant. Use live scan to find it, then save the result.</p>
@if (! model_configured())<div class="alert alert-warning"><i class="bi bi-exclamation-triangle"></i> Detection is not configured. Add a local inference URL or Roboflow settings to <code>.env</code>.</div>@endif
<div class="alert d-none" id="detectStatus" role="status"></div>
<div class="row g-4"><div class="col-lg-8">
  <div class="card p-3">
    <div class="cam-wrap" id="camWrap"><video id="video" autoplay playsinline muted></video><canvas id="overlay"></canvas>
      <div class="cam-off" id="camOff"><i class="bi bi-camera-video-off"></i><p>Camera is off</p></div></div>
    <div id="liveStatus" class="small text-muted mt-2">&nbsp;</div>
    <div class="d-flex flex-wrap gap-2 mt-2">
      <button class="btn btn-pc" id="btnStart"><i class="bi bi-camera-video"></i> Start camera</button>
      <button class="btn btn-outline-pc" id="btnFlip" disabled><i class="bi bi-arrow-repeat"></i> Switch camera</button>
      <div class="form-check form-switch align-self-center ms-2"><input class="form-check-input" type="checkbox" id="live" disabled><label class="form-check-label" for="live">Live scan</label></div>
      <button class="btn btn-gold ms-auto" id="btnScan" disabled><i class="bi bi-upc-scan"></i> Scan &amp; Save</button></div>
    <hr><label class="form-label small fw-semibold">Or upload a photo instead</label>
    <input class="form-control" type="file" id="file" accept="image/jpeg,image/png,image/webp">
    <div class="form-text">Upload a JPEG, PNG, or WebP photo up to 12 MB.</div>
  </div></div>
  <div class="col-lg-4">
    <div class="card p-3 mb-3"><label class="form-label small fw-semibold">Suspected pest (optional)</label>
      <select class="form-select mb-3" id="hint"><option value="">Auto-detect</option>@foreach ($pests as $pest)<option>{{ $pest->name }}</option>@endforeach</select>
      <label class="form-label small fw-semibold">Severity (optional)</label>
      <select class="form-select" id="severity"><option value="">Auto</option><option>low</option><option>medium</option><option>high</option></select></div>
    <div id="result"></div></div></div>
@endsection

@push('scripts')
<script>const MODEL_READY = @json(model_configured());</script>
<script src="{{ asset('static/js/camera.js') }}"></script>
@endpush
