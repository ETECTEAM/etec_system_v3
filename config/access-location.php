<?php

// Settings for the location-lock feature: some dashboard routes only open while the
// user's browser GPS puts them inside an admin-approved area. The admin turns the
// whole feature on/off from /dashboard/access-locations (stored as a grading_settings
// row, read through the setting() helper) - the values here are the fallbacks and the
// hard limits around it.
return [

    // Hard kill switch. When false the middleware never runs, no matter what the
    // admin toggle or the access_locations table say - flip this in .env to disable
    // the feature during an incident without touching the database.
    'enabled' => (bool) env('ACCESS_LOCATION_ENABLED', true),

    // The grading_settings key the admin on/off toggle writes to.
    'settings_key' => 'access_location.enabled',

    // How long (seconds) one successful GPS check stays valid before the user is
    // sent back to the location screen to share their position again.
    'session_ttl' => (int) env('ACCESS_LOCATION_SESSION_TTL', 900),

    // Roles that skip location checks entirely, so an admin can never lock themselves
    // out of the screen that manages the feature.
    'bypass_roles' => ['super_admin'],

    // Reject a GPS fix whose reported accuracy radius is worse than the location's
    // own radius or this many metres, whichever is larger - a 2km-accurate fix
    // can't prove someone is inside a 150m building.
    'min_accuracy_slack' => (int) env('ACCESS_LOCATION_MIN_ACCURACY_SLACK', 50),

    // Seconds the active-locations list is cached for. The middleware reads it on
    // every request, and it is busted immediately whenever a location is saved.
    'cache_ttl' => 60,
];
