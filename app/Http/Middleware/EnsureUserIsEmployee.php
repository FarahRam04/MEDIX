<?php

namespace App\Http\Middleware;

use App\Models\Employee;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsEmployee
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

        // هنا الشرط المهم: هل المستخدم من جدول employee؟
        if (! $user || get_class($user) !== Employee::class) {
            return response()->json(['message' => 'Unauthorized. Employee only.'], 403);
        }

        return $next($request);
    }
}
