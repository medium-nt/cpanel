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

        if (($user->role->name != 'admin' && $user->role->name != 'storekeeper' && !$user->shift_is_open)) {
            return redirect()->route('home')
                ->with('error', 'Откройте смену для доступа к функционалу.');
        }

        return $next($request);
    }
}
