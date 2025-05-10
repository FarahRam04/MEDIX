<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // تحقق إن المستخدم Authenticated
        $user = $request->user();

        // هنا الشرط المهم: هل المستخدم من جدول الإدمنز؟
        if (! $user || get_class($user) !== Admin::class) {
            return response()->json(['message' => 'Unauthorized. Admins only.'], 403);
        }

        return $next($request);
    }
}
