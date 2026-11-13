<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Home') — PalayCheck</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="{{ asset('static/css/style.css') }}" rel="stylesheet">
</head>
<body class="site-shell">
<nav class="navbar navbar-expand-lg pc-nav"><div class="container">
  <a class="navbar-brand" href="{{ route('home') }}">🌾 Palay<span>Check</span></a>
  <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nv"><i class="bi bi-list text-white fs-2"></i></button>
  <div class="collapse navbar-collapse" id="nv"><ul class="navbar-nav ms-auto align-items-lg-center">
    <li class="nav-item"><a class="nav-link" href="{{ route('home') }}"><i class="bi bi-house"></i> Home</a></li>
    @auth
      <li class="nav-item"><a class="nav-link" href="{{ route('dashboard') }}"><i class="bi bi-grid"></i> Dashboard</a></li>
      <li class="nav-item"><a class="nav-link" href="{{ route('detection.index') }}"><i class="bi bi-upc-scan"></i> Detect</a></li>
      <li class="nav-item"><a class="nav-link" href="{{ route('pests.index') }}"><i class="bi bi-bug"></i> Pests</a></li>
      <li class="nav-item"><a class="nav-link" href="{{ route('advice.index') }}"><i class="bi bi-lightbulb"></i> Advice</a></li>
      <li class="nav-item"><a class="nav-link" href="{{ route('history.index') }}"><i class="bi bi-clock-history"></i> History</a></li>
      <li class="nav-item"><a class="nav-link" href="{{ route('learning.index') }}"><i class="bi bi-journal-bookmark"></i> Learning</a></li>
      <li class="nav-item dropdown"><a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#"><i class="bi bi-person-circle"></i> {{ auth()->user()->username }}</a>
        <ul class="dropdown-menu dropdown-menu-end">
          @if (auth()->user()->isAdmin())<li><a class="dropdown-item" href="{{ route('admin.dashboard') }}">Admin Panel</a></li>@endif
          <li><a class="dropdown-item" href="{{ route('logout') }}">Logout</a></li></ul></li>
    @else
      <li class="nav-item"><a class="nav-link" href="{{ route('home') }}#about"><i class="bi bi-info-circle"></i> About</a></li>
      <li class="nav-item"><a class="nav-link" href="{{ route('pests.index') }}"><i class="bi bi-bug"></i> Pest Information</a></li>
      <li class="nav-item"><a class="nav-link" href="{{ route('learning.index') }}"><i class="bi bi-journal-bookmark"></i> Learning</a></li>
      <li class="nav-item"><a class="nav-link" href="{{ route('login') }}"><i class="bi bi-box-arrow-in-right"></i> Login</a></li>
      <li class="nav-item"><a class="nav-link" href="{{ route('register') }}"><i class="bi bi-person-plus"></i> Register</a></li>
    @endauth
  </ul></div></div></nav>
<main class="container py-4 page-fade">
  @foreach (session('flashes', []) as $flash)
    <div class="alert alert-{{ $flash['category'] }} alert-dismissible fade show">{{ $flash['message'] }}<button class="btn-close" data-bs-dismiss="alert"></button></div>
  @endforeach
  @yield('content')
</main>
<footer class="pc-footer"><div class="container"><div class="row g-4">
  <div class="col-md-6"><div class="brand">🌾 Palay<span>Check</span></div><p>Rice Pest Detection &amp; Smart Farming Advice</p></div>
  <div class="col-md-3"><h6>Explore</h6><a href="{{ route('home') }}">Home</a><a href="{{ route('home') }}#about">About</a><a href="{{ route('pests.index') }}">Pest Information</a><a href="{{ route('learning.index') }}">Learning</a></div>
  <div class="col-md-3"><h6>Account</h6>
    @auth<a href="{{ route('dashboard') }}">Dashboard</a><a href="{{ route('logout') }}">Logout</a>
    @else<a href="{{ route('login') }}">Login</a><a href="{{ route('register') }}">Register</a>@endauth</div>
  </div><hr><p class="text-center mb-0">© {{ date('Y') }} PalayCheck. All rights reserved.</p></div></footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('static/js/transitions.js') }}"></script>
<script>
document.addEventListener('click', function (event) {
  const button = event.target.closest('[data-toggle-password]');
  if (!button) return;
  const input = document.querySelector(button.getAttribute('data-toggle-password'));
  if (!input) return;
  const show = input.type === 'password';
  input.type = show ? 'text' : 'password';
  const icon = button.querySelector('i');
  if (icon) icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
  button.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
});
</script>
@stack('scripts')
</body>
</html>
