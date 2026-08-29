<?php

namespace App\Modules\AccessLocation\Support;

/**
 * The catalogue of dashboard areas an admin is allowed to put behind a location
 * lock. Each entry maps a stable key (stored in access_location_routes.route_key)
 * to the URI glob patterns the middleware matches with Request::is() - patterns
 * carry no leading slash and use '*' for wildcards.
 *
 * Add an area here to make it lockable in the admin UI; nothing else changes.
 */
class LockableRoutes
{
    /**
     * @return array<string, array{label: string, description: string, patterns: list<string>}>
     */
    public static function catalog(): array
    {
        return [
            'dashboard_all' => [
                'label' => 'Entire dashboard',
                'description' => 'Every /dashboard screen. The location-lock management pages and the location check itself are always exempt, so this can never lock you out.',
                'patterns' => [
                    'dashboard',
                    'dashboard/*',
                ],
            ],
            'student_register' => [
                'label' => 'Register Student',
                'description' => 'The admin pre-registration form (/dashboard/enroll/students/create) and its submit.',
                'patterns' => [
                    'dashboard/enroll/students',
                    'dashboard/enroll/students/*',
                ],
            ],
            'admin_registrations' => [
                'label' => 'Registration Desk',
                'description' => 'Add / list registrations under /dashboard/admin/registrations.',
                'patterns' => [
                    'dashboard/admin/registrations',
                    'dashboard/admin/registrations/*',
                ],
            ],
            'enrollment' => [
                'label' => 'Enrollment Management (all)',
                'description' => 'Every /dashboard/enroll screen, including class create/edit.',
                'patterns' => [
                    'dashboard/enroll',
                    'dashboard/enroll/*',
                ],
            ],
            'users' => [
                'label' => 'User Management',
                'description' => 'Users, roles and permissions under /dashboard/users.',
                'patterns' => [
                    'dashboard/users',
                    'dashboard/users/*',
                ],
            ],
            'buildings' => [
                'label' => 'Building Management',
                'description' => 'Buildings, floors and rooms.',
                'patterns' => [
                    'dashboard/buildings', 'dashboard/buildings/*',
                    'dashboard/floors', 'dashboard/floors/*',
                    'dashboard/rooms', 'dashboard/rooms/*',
                ],
            ],
            'certificates' => [
                'label' => 'Certificates',
                'description' => 'Certificate issuing screens under /dashboard/certificates.',
                'patterns' => [
                    'dashboard/certificates',
                    'dashboard/certificates/*',
                ],
            ],
            'attendance' => [
                'label' => 'Attendance',
                'description' => 'Attendance screens under /dashboard/attendances.',
                'patterns' => [
                    'dashboard/attendances',
                    'dashboard/attendances/*',
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::catalog());
    }

    /**
     * Every URI pattern for the given keys, de-duplicated. Unknown keys are ignored.
     *
     * @param  iterable<string>  $keys
     * @return list<string>
     */
    public static function patternsFor(iterable $keys): array
    {
        $catalog = self::catalog();
        $patterns = [];

        foreach ($keys as $key) {
            foreach ($catalog[$key]['patterns'] ?? [] as $pattern) {
                $patterns[] = $pattern;
            }
        }

        return array_values(array_unique($patterns));
    }

    /**
     * Shape the catalogue for the admin UI checklist.
     *
     * @return list<array{key: string, label: string, description: string}>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::catalog() as $key => $entry) {
            $options[] = [
                'key' => $key,
                'label' => $entry['label'],
                'description' => $entry['description'],
            ];
        }

        return $options;
    }
}
