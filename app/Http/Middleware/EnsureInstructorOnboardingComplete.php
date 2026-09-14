<?php

namespace App\Http\Middleware;

use App\Modules\Instructor\Services\InstructorOnboardingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Holds newly self-registered instructors out of the dashboard until they
 * finish onboarding. It is only applied to dashboard/functional routes -
 * the onboarding wizard, profile and account-security pages stay reachable
 * so the user can complete the required setup.
 */
class EnsureInstructorOnboardingComplete
{
    public function __construct(
        private readonly InstructorOnboardingService $onboarding,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->hasRole('instructor') && $this->onboarding->isPending($user)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Please complete your instructor onboarding before continuing.',
                    'redirect' => '/dashboard/instructor/onboarding',
                ], 403);
            }

            return redirect('/dashboard/instructor/onboarding')
                ->with('error', 'Please finish the setup steps to access the dashboard.');
        }

        return $next($request);
    }
}
