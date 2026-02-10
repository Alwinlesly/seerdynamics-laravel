<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class CheckUserRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login.form');
        }
        $user = Auth::user();
        $userGroups = $user->groups->pluck('name')->toArray();
        foreach ($roles as $role) {
            if (in_array($role, $userGroups)) {
                return $next($request);
            }
        }
        abort(403, 'Access denied.');
    }
}