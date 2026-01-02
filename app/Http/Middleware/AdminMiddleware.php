<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     * ตรวจสอบว่าผู้ใช้เป็น admin หรือไม่
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        // ตรวจสอบ is_admin flag หรือ Spatie role
        if (! $this->isAdmin($request->user())) {
            abort(403, 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        }

        return $next($request);
    }

    /**
     * ตรวจสอบว่าผู้ใช้เป็น admin หรือไม่
     */
    private function isAdmin($user): bool
    {
        // ตรวจสอบ is_admin flag
        if (property_exists($user, 'is_admin') || isset($user->is_admin)) {
            if ($user->is_admin) {
                return true;
            }
        }

        // ตรวจสอบ Spatie role (ถ้ามี)
        if (method_exists($user, 'hasRole')) {
            if ($user->hasRole(['admin', 'super-admin'])) {
                return true;
            }
        }

        return false;
    }
}
