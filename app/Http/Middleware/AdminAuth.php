<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Allow the login page + login POST without being logged in
        if ($request->is('admin/login') || ($request->is('admin/login') && $request->isMethod('post'))) {
            return $next($request);
        }

        if (! $request->session()->get('admin_logged_in', false)) {
            return redirect('/admin/login');
        }

        return $next($request);
    }
}
