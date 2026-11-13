@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
<p class="text-muted">Monitor and manage the PalayCheck platform.</p>
<div class="row g-3 mb-3">
@foreach ([['people', $users, 'Total users', $activeUsers . ' active'], ['bug', $pests, 'Total pests', $activePests . ' active'], ['upc-scan', $total, 'Total detections', number_format($matched, 0) . ' identified'], ['journal-bookmark', $learning, 'Learning materials', $publishedLearning . ' published']] as $card)
<div class="col-md-6 col-xl-3"><div class="card p-3"><i class="bi bi-{{ $card[0] }} text-pc"></i><div class="num">{{ $card[1] }}</div><div class="lbl">{{ $card[2] }}</div><small class="text-muted">{{ $card[3] }}</small></div></div>
@endforeach</div>
<div class="row g-3 mb-4"><div class="col-lg-4"><div class="card p-3"><i class="bi bi-lightbulb text-pc"></i><div class="num">{{ $adviceCount }}</div><div class="lbl">Total farming advice</div><small class="text-muted">{{ $publishedAdvice }} published</small></div></div>
<div class="col-lg-4"><div class="card p-3"><i class="bi bi-cpu text-pc"></i><div class="num fs-4">{{ $status['ready'] ? 'Ready' : 'Not Configured' }}</div><div class="lbl">AI model status</div><small class="text-muted">{{ number_format($status['threshold'] * 100, 0) }}% threshold · {{ $status['file'] }} · {{ $status['exists'] ? 'found' : 'file Missing' }}</small></div></div>
<div class="col-lg-4"><div class="card p-3"><i class="bi bi-activity text-pc"></i><div class="num">{{ number_format($matchRate, 1) }}%</div><div class="lbl">Detection success rate</div><small class="text-muted">{{ $matched }} of {{ $total }} matched to a pest</small></div></div></div>
<h5>Analytics</h5><div class="row g-3 mb-4"><div class="col-lg-6"><div class="card p-3"><b>Detections by month</b>@include('admin.partials.bars', ['rows' => $byMonth])</div></div>
<div class="col-lg-6"><div class="card p-3"><b>Most detected pests</b>@include('admin.partials.bars', ['rows' => $byPest])</div></div>
<div class="col-lg-6"><div class="card p-3"><b>Severity distribution</b>@include('admin.partials.bars', ['rows' => $bySeverity])</div></div>
<div class="col-lg-6"><div class="card p-3"><b>User registrations</b>@include('admin.partials.bars', ['rows' => $reg])</div></div></div>
<div class="row g-3"><div class="col-lg-6"><div class="d-flex justify-content-between"><h5>Recent Detections</h5><a class="btn btn-sm btn-outline-pc" href="{{ route('admin.detections.index') }}">View all</a></div>
<div class="card p-3">@forelse ($recent as $detection)<div class="small mb-1"><b>{{ $detection->label }}</b> · {{ $detection->user?->username }} · {{ number_format((float) $detection->confidence * 100, 0) }}%</div>@empty<div class="text-center text-muted">No data available yet.</div>@endforelse</div></div>
<div class="col-lg-6"><div class="d-flex justify-content-between"><h5>Recent Users</h5><a class="btn btn-sm btn-outline-pc" href="{{ route('admin.resources.index', 'users') }}">View All Users</a></div>
<div class="card"><table class="table mb-0 small"><thead class="table-pc"><tr><th>Name</th><th>Role</th><th>Status</th><th>Joined</th></tr></thead>
@foreach ($recentUsers as $recentUser)<tr><td>{{ $recentUser->full_name }}<br><small class="text-muted">{{ $recentUser->email }}</small></td><td>{{ strtoupper((string) $recentUser->role) }}</td><td>{{ $recentUser->status }}</td><td>{{ format_datetime($recentUser->created_at, 'M d, Y') }}</td></tr>@endforeach</table></div></div></div>
@endsection
