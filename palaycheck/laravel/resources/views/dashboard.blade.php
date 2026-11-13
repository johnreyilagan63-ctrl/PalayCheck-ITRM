@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<h1 class="fw-bold">Good day, {{ $user->full_name }}!</h1><p class="text-muted">Ready to check your rice crops?</p>
<div class="row g-3 mb-4">
@foreach ([[$total, 'Total scans', 'a'], [$pestCount, 'Known pests', 'b'], [$guideCount, 'Learning guides', 'a'], [ucfirst((string) $user->role), 'Your role', 'b']] as $stat)
<div class="col-md-6 col-lg-3"><div class="stat stat-{{ $stat[2] }}"><div class="num">{{ $stat[0] }}</div><div class="lbl">{{ $stat[1] }}</div></div></div>
@endforeach</div>
<div class="d-flex justify-content-between align-items-center"><h4>Recent detections</h4><a class="btn btn-pc" href="{{ route('detection.index') }}"><i class="bi bi-camera"></i> New scan</a></div>
<div class="card p-3 mt-2">
@if ($recent->isNotEmpty())<div class="table-responsive"><table class="table align-middle mb-0"><tbody>@foreach ($recent as $detection)
<tr><td style="width:70px">@if ($detection->image_file)<img src="{{ asset('static/uploads/' . basename($detection->image_file)) }}" class="thumb" alt="">@endif</td>
<td><b>{{ $detection->label }}</b><div class="small text-muted">{{ format_datetime($detection->created_at) }}</div></td>
<td>{{ number_format((float) $detection->confidence * 100, 0) }}%</td><td>@include('partials.severity', ['severity' => $detection->severity])</td>
<td class="text-end"><a class="btn btn-sm btn-outline-pc" href="{{ route('history.show', $detection) }}">View</a></td></tr>@endforeach</tbody></table></div>
@else<div class="empty"><i class="bi bi-camera"></i><p>No detection history yet. Start by scanning a rice plant image.</p></div>@endif</div>
@endsection
