<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentPortalSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('student_portal_id')) {
            return redirect()->route('frontend.student-portal.login');
        }

        return $next($request);
    }
}
