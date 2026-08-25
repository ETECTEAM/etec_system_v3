<?php

// Defaults for the official-leave feature. Every value here can be overridden by a
// row in official_leave_settings (see the migration for the seeded keys); the config
// is the fallback when the table has no row (or the DB isn't migrated yet).
return [

    // Instructor permission quota per student per month.
    'monthly_permission_quota' => 4,

    // Used permissions that convert into one equivalent absence for block counting.
    'permissions_per_absence' => 2,

    // Real absences + converted permissions at or above this block the student.
    'absence_block_threshold' => 3,

    // How long a generated leave-request QR (signed URL) stays valid.
    'qr_token_ttl_minutes' => 15,

    // Maximum date range a single leave request may cover.
    'max_leave_days' => 30,
];
