<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        
        // Allow super admins to access everything
        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }
        
        // Ensure regular users have a company assigned
        if ($user && !$user->company_id) {
            abort(403, 'Access denied: No company assigned to user.');
        }
        
        // Ensure the user's company has an active subscription
        if ($user && $user->company && !$user->company->isSubscriptionActive()) {
            abort(403, 'Access denied: Company subscription is not active.');
        }
        
        return $next($request);
    }
}