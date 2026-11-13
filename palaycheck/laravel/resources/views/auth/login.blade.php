@extends('layouts.app')

@section('title', 'Login')

@section('content')
<div class="auth-card card p-4 mx-auto">
  <div class="text-center"><div class="brand-dark">🌾 Palay<span>Check</span></div><h3 class="mt-3">Welcome back</h3><p class="text-muted">Log in to check your rice crops.</p></div>
  <form method="post" action="{{ route('login') }}">@csrf
    <label class="form-label fw-semibold">Email or username</label><input class="form-control mb-3" name="identity" value="{{ old('identity') }}" autocomplete="username" required autofocus>
    <label class="form-label fw-semibold">Password</label>
    <div class="input-group mb-3"><input class="form-control" type="password" name="password" id="loginPassword" autocomplete="current-password" required><button class="btn btn-outline-secondary" type="button" data-toggle-password="#loginPassword" aria-label="Show password"><i class="bi bi-eye"></i></button></div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="remember" value="1" id="remember"><label class="form-check-label" for="remember">Remember me <span class="text-muted small">(keep me signed in for 30 days)</span></label></div>
    <button class="btn btn-pc w-100 btn-lg">Login</button></form>
  <p class="text-center mt-3 mb-0">No account yet? <a href="{{ route('register') }}">Register</a></p></div>
@endsection
