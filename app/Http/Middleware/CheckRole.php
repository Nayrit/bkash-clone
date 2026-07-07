<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Guard 1: Ensure user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Guard 2: Ensure the user's explicit role property matches the route requirements
        if (auth()->user()->role !== $role) {
            abort(403, 'Unauthorized. Access Denied.');
        }

        return $next($request);
    }
}