<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks an instructor's self-service "Add Class" until an admin has
 * approved them - separate from the create-classes permission every
 * instructor gets from their role, which controls the broader ability to
 * manage classes at all (attendance, results, etc. on classes assigned to
 * them), not whether they can create new ones unsupervised.
 */
class EnsureInstructorCanCreateClasses
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->hasRole('instructor') && ! $user->instructorData?->can_create_classes) {
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
