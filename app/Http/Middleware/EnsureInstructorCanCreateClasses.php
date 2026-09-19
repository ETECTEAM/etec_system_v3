<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks an instructor's self-service "Add Class" unless they hold the
 * create-classes permission: the "Classes -> Create" tick on the Role &
 * Permission page (for every instructor), or the same permission granted to
 * one instructor on the User & Permission page. The Add Class button on the
 * instructor dashboard reads that same permission, so the two always agree.
 */
class EnsureInstructorCanCreateClasses
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->hasRole('instructor') && ! $user->can('create-classes')) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'An admin needs to approve your account before you can create a class.',
                ], 403);
            }

            return redirect('/dashboard')
                ->with('error', 'An admin needs to approve your account before you can create a class.');
        }

        return $next($request);
    }
}
