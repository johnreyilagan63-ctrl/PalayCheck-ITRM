<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return $this->homeFor(Auth::user());
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        if (Auth::check()) {
            return $this->homeFor(Auth::user());
        }

        $validator = Validator::make($request->all(), [
            'identity' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'identity.required' => 'Enter your email or username.',
            'password.required' => 'Enter your password.',
        ]);

        if ($validator->fails()) {
            flash($validator->errors()->first(), 'danger');

            return back()->withInput($request->only('identity'));
        }

        $identity = trim((string) $request->input('identity'));
        $password = (string) $request->input('password');
        $user = User::where('email', $identity)->orWhere('username', $identity)->first();

        if (! $user || ! Hash::check($password, (string) $user->password_hash)) {
            flash('Invalid email/username or password.', 'danger');

            return back()->withInput($request->only('identity'));
        }

        if (! $user->isActive()) {
            flash('Your account is inactive. Contact the administrator.', 'danger');

            return back()->withInput($request->only('identity'));
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        log_activity('login', $user->username);

        $next = (string) $request->query('next', '');
        $localNext = $next !== ''
            && $next[0] === '/'
            && ! str_starts_with($next, '//')
            && ! str_contains($next, '\\')
            && ! preg_match('/[\x00-\x1F]/', $next);

        return redirect()->intended($localNext ? $next : $this->homeFor($user)->getTargetUrl());
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:60', 'unique:users,username'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6', 'same:confirm'],
        ], [
            'full_name.required' => 'Fill in all fields.',
            'username.required' => 'Fill in all fields.',
            'email.required' => 'Fill in all fields.',
            'email.email' => 'Enter a valid email address.',
            'email.unique' => 'Email or username already exists.',
            'username.unique' => 'Email or username already exists.',
            'password.required' => 'Fill in all fields.',
            'password.min' => 'Password must be at least 6 characters.',
            'password.same' => 'Passwords do not match.',
        ]);

        if ($validator->fails()) {
            flash($validator->errors()->first(), 'danger');

            return back()->withInput($request->only('full_name', 'username', 'email'));
        }

        $password = (string) $request->input('password');
        if (strlen($password) > 72) {
            flash('Password must be no more than 72 bytes.', 'danger');

            return back()->withInput($request->only('full_name', 'username', 'email'));
        }

        User::create([
            'full_name' => trim((string) $request->input('full_name')),
            'username' => trim((string) $request->input('username')),
            'email' => strtolower(trim((string) $request->input('email'))),
            'password_hash' => Hash::make($password),
            'role' => 'farmer',
            'status' => 'active',
            'created_at' => now(),
        ]);

        flash('Account created. You can log in now.');

        return redirect()->route('login');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            log_activity('logout', (string) Auth::user()->username);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function homeFor(User $user)
    {
        return redirect($user->isAdmin() ? '/admin/dashboard' : '/dashboard');
    }
}
