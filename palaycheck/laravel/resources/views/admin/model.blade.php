@extends('layouts.admin')

@section('title', 'AI Model Status')

@section('content')
<h2 class="fw-bold">AI model status</h2><div class="card p-4 mb-3" style="max-width:700px">
<p><b>Status:</b>
@if ($status['ready'] && ! $status['error'])<span class="badge bg-success">Ready</span>
@elseif ($status['error'])<span class="badge bg-danger">Load error</span>
@else<span class="badge bg-warning text-dark">Not configured</span>@endif</p>
<p><b>Model file:</b> models/{{ $status['file'] }} — {{ $status['exists'] ? 'found' : 'missing' }}</p><p><b>Confidence threshold:</b> {{ number_format($status['threshold'] * 100, 0) }}% (change in Settings)</p>
@if ($status['error'])<div class="alert alert-danger small">{{ $status['error'] }}</div>@endif
<p class="small text-muted mb-0">Local inference: <code>{{ $inferenceUrl ?: 'not configured' }}</code> · Token: {{ $inferenceToken ? 'set' : 'not set' }}<br>
Roboflow key: {{ $roboflowKey ? 'set' : 'not set' }} · {{ count($workflows) }} workflow(s)</p></div>
<form method="post" class="card p-4" style="max-width:700px">@csrf<label class="form-label fw-semibold">Detection provider settings</label>
<p class="small text-muted">Provider URLs, API keys and model files are configured in <code>.env</code> and <code>config/palaycheck.php</code>.</p><p class="small text-muted">Classes: {{ implode(', ', $classes) }}</p><button class="btn btn-pc align-self-start">Refresh</button></form>
@endsection
