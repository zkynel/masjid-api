<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Memastikan user yang mengakses endpoint adalah Super Admin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || $request->user()->role !== 'super_admin') {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak. Hanya Super Admin yang dapat mengakses fitur ini.',
            ], 403);
        }

        return $next($request);
    }
}
