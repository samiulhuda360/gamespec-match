<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function show()
    {
        return view('admin.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $hashedPassword = env('ADMIN_PASSWORD_HASH', '');

        // Check if password is hashed (starts with $2y$ for bcrypt)
        if (empty($hashedPassword) || !str_starts_with($hashedPassword, '$2y$')) {
            return back()->withErrors([
                'password' => 'Admin password not configured properly. Please set ADMIN_PASSWORD_HASH in .env'
            ])->withInput();
        }

        // Verify password using bcrypt
        if (!\Hash::check($request->password, $hashedPassword)) {
            // Add a small delay to prevent timing attacks
            usleep(random_int(100000, 300000)); // 0.1-0.3 seconds

            return back()->withErrors(['password' => 'Invalid password'])->withInput();
        }

        $request->session()->regenerate();
        $request->session()->put('admin_logged_in', true);

        return redirect()->route('admin.dashboard');
    }

    public function logout(Request $request)
    {
        $request->session()->forget('admin_logged_in');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
