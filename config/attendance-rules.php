<?php

// Defaults for the attendance-rules / absence-block feature. Every value here can
// be overridden by a row in attendance_rule_settings (see the migration for the
// seeded keys); this config is the fallback when the table has no row (or the DB
// isn't migrated yet). Read via the attendance_rule_setting() helper.
return [

    // Absences in the current monthly cycle at or above this soft-lock the student.
    'absence_block_threshold' => 3,

    // Extra absences allowed after the first admin approval before a hard lock.
    'post_approval_limit' => 2,

    // Manual permissions per ISO week before extra ones are counted as absence.
    'permission_weekly_limit' => 1,

    // Earliest date any absence cycle can start from.
    'cycle_anchor_date' => '2026-04-01',
];
