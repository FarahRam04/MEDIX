<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class is_user
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // تحقق إن المستخدم Authenticated
        $user = $request->user();

        // هنا الشرط المهم: هل المستخدم من جدول users؟
        if (! $user || get_class($user) !== User::class) {
            return response()->json(['message' => 'Unauthorized. Users only.'], 403);
        }

        return $next($request);
    }
}
