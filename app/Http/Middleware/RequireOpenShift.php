<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireOpenShift
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ((!$user->isAdmin() && !$user->isStorekeeper() && !$user->shift_is_open)) {
            return redirect()->route('home')
                ->with('error', 'Откройте смену на терминале для доступа к функционалу.');
        }

        return $next($request);
    }
}
