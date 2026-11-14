@extends('layouts.app')

@section('title', 'Register')

@section('content')
<div class="auth-card card p-4 mx-auto">
  <div class="text-center"><div class="brand-dark">🌾 Palay<span>Check</span></div><h3 class="mt-3">Create your account</h3></div>
  <form method="post" action="{{ route('register') }}">@csrf
    <label class="form-label fw-semibold">Full name</label><input class="form-control mb-3" type="text" name="full_name" autocomplete="name" value="{{ old('full_name') }}" required>
    <label class="form-label fw-semibold">Username</label><input class="form-control mb-3" type="text" name="username" autocomplete="username" value="{{ old('username') }}" required>
    <label class="form-label fw-semibold">Email</label><input class="form-control mb-3" type="email" name="email" autocomplete="email" value="{{ old('email') }}" required>
    <label class="form-label fw-semibold">Password</label>
    <div class="input-group mb-3"><input class="form-control" type="password" name="password" id="registerPassword" autocomplete="new-password" required><button class="btn btn-outline-secondary" type="button" data-toggle-password="#registerPassword" aria-label="Show password"><i class="bi bi-eye"></i></button></div>
    <label class="form-label fw-semibold">Confirm password</label>
    <div class="input-group mb-3"><input class="form-control" type="password" name="confirm" id="registerConfirm" autocomplete="new-password" required><button class="btn btn-outline-secondary" type="button" data-toggle-password="#registerConfirm" aria-label="Show password"><i class="bi bi-eye"></i></button></div>
    <button class="btn btn-pc w-100 btn-lg">Register</button></form>
  <p class="text-center mt-3 mb-0">Already registered? <a href="{{ route('login') }}">Login</a></p></div>
@endsection
