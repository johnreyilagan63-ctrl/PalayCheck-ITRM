<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Admin — @yield('title') — PalayCheck</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="{{ asset('static/css/style.css') }}" rel="stylesheet">
</head>
<body class="admin">
<aside class="sidebar"><div class="sb-brand">🌾 <b>PALAYCHECK</b><small>ADMIN PANEL</small></div>
@php
  $adminLinks = [
    ['admin.dashboard', 'grid', 'Dashboard'],
    ['admin.resources.index', 'people', 'Users', 'users'],
    ['admin.resources.index', 'bug', 'Pests', 'pests'],
    ['admin.resources.index', 'lightbulb', 'Advice', 'advice'],
    ['admin.resources.index', 'journal-bookmark', 'Learning Materials', 'learning'],
    ['admin.images.index', 'image', 'Images'],
    ['admin.detections.index', 'camera', 'Detections'],
    ['admin.activity.index', 'list-check', 'Activity'],
    ['admin.model.index', 'cpu', 'AI Model Status'],
    ['admin.settings.index', 'gear', 'Settings'],
  ];
@endphp
@foreach ($adminLinks as $link)
  @php
    $route = $link[0];
    $path = isset($link[3]) ? route($route, $link[3]) : route($route);
    $active = request()->url() === $path || str_starts_with(request()->url(), rtrim($path, '/') . '/');
  @endphp
  <a href="{{ $path }}" class="{{ $active ? 'active' : '' }}"><i class="bi bi-{{ $link[1] }}"></i> {{ $link[2] }}</a>
@endforeach
<div class="sb-bottom"><a href="{{ route('home') }}"><i class="bi bi-house"></i> View Farmer Site</a><a href="{{ route('logout') }}"><i class="bi bi-box-arrow-right"></i> Logout</a></div></aside>
<div class="admin-main"><header class="topbar"><div><b>@yield('title')</b><div class="small text-muted">Admin / @yield('title')</div></div>
<div><span class="badge bg-success">ADMIN</span> <b>{{ auth()->user()->username }}</b> <a class="btn btn-sm btn-outline-secondary" href="{{ route('logout') }}">Logout</a></div></header>
<section class="p-4 page-fade">
@foreach (session('flashes', []) as $flash)
  <div class="alert alert-{{ $flash['category'] }} alert-dismissible fade show">{{ $flash['message'] }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
@endforeach
@yield('content')
</section></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('static/js/transitions.js') }}"></script>
@stack('scripts')
</body></html>
