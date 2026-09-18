# ETEC System v3 — Database Design (dbdiagram.io)

Generated from `database/migrations/` (160 migrations, state as of `2026_09_16_000001`).
Dropped tables/columns are **not** included — this is the *current* schema, not the history.

**How to use:** copy the DBML block below and paste it into <https://dbdiagram.io/d>.

---

## Domain overview

| Group | Tables | Purpose |
|---|---|---|
| Auth & Access | `users`, `photos`, `otp_verifications`, `otp_verification_settings`, `auth_audit_logs`, `login_lockout*`, `access_location*`, `location_access_logs`, `dashboard_notifications`, Spatie permission tables | Login, OTP, lockout, geo-fenced route access |
| Facility | `buildings`, `floors`, `rooms` | Physical location hierarchy |
| Catalog | `categories`, `sub_categories`, `course_tracks`, `courses`, `course_lessons` | Course taxonomy |
| Scheduling | `class_type`, `terms`, `times`, `schedules`, `schedule_time`, `holidays`, `course_enroll_configs` | Time slots, day patterns, enrollment windows & pricing |
| Students & Classes | `students`, `study_classes`, `study_class_instructors`, `student_enrollments` | The core operational tables |
| Attendance | `class_sessions`, `attendance_sessions`, `student_attendances`, `attendance_audit_logs`, `pre_attendance_requests`, `instructor_attendance_blocks` | QR + manual attendance, geo/IP verification |
| Leave & Rules | `student_permissions`, `leave_request_sessions`, `official_leaves`, `official_leave_settings`, `attendance_rules`, `attendance_rule_settings`, `student_attendance_block`, `activity_logs` | Permission quotas, absence blocking |
| Grading | `grading_settings`, `student_scores`, `teams`, `team_members` | Score weights, team projects |
| Instructors | `instructor_data`, `instructor_attachments`, `instructor_availabilities`, `instructor_schedule_blocks`, `work_schedules`, `work_schedule_times` | Instructor profile + availability engine |
| Certificates | `class_certificate_requests`, `certificate_class_requests`, `student_certificate_normal`, `certificate_class_free`, `course_custom`, `course_custom_normal` | Certificate issuing workflow |
| CMS / Website | `school_settings`, `pages`, `page_heroes`, `page_hero_images`, `menus`, `news`, `news_images`, `website_videos` | Public site content |
| Legacy | `classes`, `enrollments` | Superseded by `study_classes` / `student_enrollments` — still migrated, no longer written to |
| Framework | `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `password_reset_tokens`, `personal_access_tokens` | Laravel internals (omitted from the diagram below) |

---

## DBML

```dbml
// =====================================================================
// ETEC System v3 — MySQL 8 / Laravel 12
// Paste into https://dbdiagram.io/d
// =====================================================================

// ---------------------------------------------------------------------
// AUTH & ACCESS CONTROL
// ---------------------------------------------------------------------

Table users {
  id bigint [pk, increment]
  created_by bigint [null, note: 'admin who created this account']
  name varchar(255) [null]
  email varchar(255) [unique, not null]
  recovery_email varchar(255) [null]
  recovery_verified boolean [default: false]
  email_verified_at timestamp [null]
  password varchar(255) [not null]
  role varchar(50) [null, note: 'admin | instructor']
  requires_onboarding boolean [default: false]
  onboarding_completed_at timestamp [null]
  status varchar(20) [default: 'active']
  access_expires_at timestamp [null]
  access_renewed_at timestamp [null]
  verified_at timestamp [null]
  remember_token varchar(100) [null]
  last_login_at timestamp [null]
  created_at timestamp
  updated_at timestamp
}

Table photos {
  id bigint [pk, increment]
  user_id bigint [unique, not null, note: 'one avatar per user']
  file_path varchar(255) [not null]
  file_name varchar(255) [null]
  file_mime varchar(255) [null]
  file_size bigint [null]
  created_at timestamp
  updated_at timestamp
}

Table otp_verifications {
  id bigint [pk, increment]
  user_id bigint [not null]
  otp_code varchar(255) [not null, note: 'hashed']
  expires_at timestamp [null]
  verified_at timestamp [null]
  attempts tinyint [default: 0]
  created_by bigint [null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (user_id, verified_at)
  }
}

Table otp_verification_settings {
  id bigint [pk, increment]
  is_enabled boolean [default: true]
  created_at timestamp
  updated_at timestamp
}

Table auth_audit_logs {
  id bigint [pk, increment]
  user_id bigint [null]
  action varchar(80) [not null]
  ip_address varchar(64) [null]
  metadata json [null]
  created_by bigint [null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (user_id, action)
  }
}

Table dashboard_notifications {
  id bigint [pk, increment]
  title varchar(255) [not null]
  message text [not null]
  is_read boolean [default: false]
  type varchar(255) [default: 'general']
  otp_verification_id bigint [null]
  created_at timestamp
  updated_at timestamp
  note: 'renamed from `notifications` in 2026_08_21'
}

Table login_lockouts {
  id bigint [pk, increment]
  login varchar(255) [unique, not null]
  offense_number int [default: 0]
  last_offense_at timestamp [null]
  banned_until timestamp [null]
  is_hard_block boolean [default: false]
  created_at timestamp
  updated_at timestamp
}

Table login_lockout_tiers {
  id bigint [pk, increment]
  offense_number int [unique, not null]
  duration_minutes int [not null]
  created_at timestamp
  updated_at timestamp
}

Table login_lockout_settings {
  id bigint [pk, increment]
  reset_after_hours int [default: 24]
  is_enabled boolean [default: true]
  free_attempts int [default: 5]
  created_at timestamp
  updated_at timestamp
}

Table access_locations {
  id bigint [pk, increment]
  name varchar(255) [not null]
  latitude decimal(10,7) [not null]
  longitude decimal(10,7) [not null]
  radius_meters int [default: 150]
  is_active boolean [default: true]
  description varchar(1000) [null]
  created_by bigint [null]
  created_at timestamp
  updated_at timestamp
}

Table access_location_routes {
  id bigint [pk, increment]
  access_location_id bigint [not null]
  route_key varchar(255) [not null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (access_location_id, route_key) [unique]
  }
}

Table location_access_logs {
  id bigint [pk, increment]
  user_id bigint [null]
  path varchar(255) [null]
  outcome varchar(32) [not null]
  access_location_id bigint [null]
  latitude decimal(10,7) [null]
  longitude decimal(10,7) [null]
  accuracy decimal(8,2) [null]
  distance_meters int [null]
  ip varchar(45) [null]
  created_at timestamp [null]

  indexes {
    (user_id, created_at)
  }
}

// Spatie laravel-permission (config teams = false)
Table roles {
  id bigint [pk, increment]
  name varchar(255) [not null]
  guard_name varchar(255) [not null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (name, guard_name) [unique]
  }
}

Table permissions {
  id bigint [pk, increment]
  name varchar(255) [not null]
  guard_name varchar(255) [not null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (name, guard_name) [unique]
  }
}

Table model_has_roles {
  role_id bigint [not null]
  model_type varchar(255) [not null]
  model_id bigint [not null]

  indexes {
    (role_id, model_id, model_type) [pk]
  }
}

Table model_has_permissions {
  permission_id bigint [not null]
  model_type varchar(255) [not null]
  model_id bigint [not null]

  indexes {
    (permission_id, model_id, model_type) [pk]
  }
}

Table role_has_permissions {
  permission_id bigint [not null]
  role_id bigint [not null]

  indexes {
    (permission_id, role_id) [pk]
  }
}

// ---------------------------------------------------------------------
// FACILITY
// ---------------------------------------------------------------------

Table buildings {
  id bigint [pk, increment]
  name varchar(255) [unique, not null]
  code varchar(255) [unique, null]
  description text [null]
  created_at timestamp
  updated_at timestamp
}

Table floors {
  id bigint [pk, increment]
  building_id bigint [null]
  name varchar(255) [not null]
  "level" int [null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (building_id, name) [unique]
  }
}

Table rooms {
  id bigint [pk, increment]
  floor_id bigint [null]
  room_number varchar(255) [not null]
  capacity int [null]
  status enum [default: 'available', note: 'available | occupied | maintenance | closed']
  created_at timestamp
  updated_at timestamp

  indexes {
    (floor_id, room_number) [unique]
  }
}

// ---------------------------------------------------------------------
// COURSE CATALOG
// ---------------------------------------------------------------------

Table categories {
  id bigint [pk, increment]
  name varchar(255) [not null]
  status varchar(255) [default: 'active']
  created_at timestamp
  updated_at timestamp
}

Table sub_categories {
  id bigint [pk, increment]
  category_id bigint [not null]
  name varchar(255) [not null]
  slug varchar(255) [unique, not null]
  status varchar(255) [default: 'active']
  created_at timestamp
  updated_at timestamp
}

Table course_tracks {
  id bigint [pk, increment]
  sub_category_id bigint [not null]
  class_type_id bigint [null]
  name varchar(255) [not null]
  slug varchar(255) [unique, not null]
  status varchar(255) [default: 'active']
  created_at timestamp
  updated_at timestamp
}

Table courses {
  id bigint [pk, increment]
  course_track_id bigint [null]
  title varchar(255) [not null]
  slug varchar(255) [unique, not null]
  "level" varchar(255) [null]
  thumbnail varchar(255) [null]
  status varchar(255) [default: 'active']
  enroll_order int [null, note: 'display order on the enroll page']
  created_at timestamp
  updated_at timestamp
  note: 'pricing lives in course_enroll_configs, not here'
}

Table course_lessons {
  id bigint [pk, increment]
  course_id bigint [not null]
  title varchar(255) [not null]
  slug varchar(255) [null]
  description text [null]
  content longtext [null]
  video_url varchar(255) [null]
  duration int [default: 0]
  order_number int [default: 1]
  status varchar(255) [default: 'active']
  created_at timestamp
  updated_at timestamp
}

// ---------------------------------------------------------------------
// SCHEDULING PRIMITIVES
// ---------------------------------------------------------------------

Table class_type {
  class_type_id bigint [pk, increment, note: 'non-standard PK name']
  type_name varchar(100) [unique, not null]
  description varchar(255) [null]
  is_active boolean [default: true]
  created_at timestamp
  updated_at timestamp
}

Table terms {
  id bigint [pk, increment]
  term_name varchar(255) [not null, note: 'day pattern, e.g. "Mon-Wed-Fri"']
  created_at timestamp
  updated_at timestamp
}

Table times {
  id bigint [pk, increment]
  time_name varchar(255) [unique, not null, note: 'slot label, e.g. "Morning (07:00 - 09:00)"']
  created_at timestamp
  updated_at timestamp
}

Table schedules {
  id bigint [pk, increment]
  class_type_id bigint [not null]
  term_id bigint [not null]
  created_at timestamp
  updated_at timestamp
}

Table schedule_time {
  id bigint [pk, increment]
  schedule_id bigint [not null]
  time_id bigint [not null]
  note: 'pivot: schedules <-> times'
}

Table holidays {
  id bigint [pk, increment]
  group_id uuid [null, note: 'groups a multi-day holiday range']
  "date" date [unique, not null]
  name varchar(255) [not null]
  start_date date [null]
  end_date date [null]
  description text [null]
  created_at timestamp
  updated_at timestamp
}

Table course_enroll_configs {
  id bigint [pk, increment]
  course_id bigint [not null]
  schedule_id bigint [null]
  time_id bigint [null]
  status varchar(20) [default: 'open']
  start_date date [null]
  unit_price decimal(10,2) [default: 0]
  course_price decimal(10,2) [default: 0]
  selected_price_type varchar(10) [default: 'course', note: 'course | unit']
  document_price decimal(10,2) [default: 5]
  max_classes smallint [null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (course_id, schedule_id, time_id) [unique]
  }
}

// ---------------------------------------------------------------------
// STUDENTS & CLASSES  (core)
// ---------------------------------------------------------------------

Table students {
  id bigint [pk, increment]
  user_id bigint [null, note: 'nullable since 2026_08_13 — students may exist without a login']
  course_id bigint [null, note: 'course chosen at registration']
  term_id bigint [null]
  time_id bigint [null]
  fee_amount decimal(12,2) [null]
  unit_price decimal(12,2) [null, note: 'price snapshot']
  document_fee_amount decimal(12,2) [null]
  full_name varchar(255) [null]
  gender enum [not null, note: 'male | female']
  date_of_birth date [null]
  phone varchar(20) [not null]
  attendance_pin_hash varchar(255) [null]
  attendance_code varchar(9) [unique, null]
  email varchar(255) [unique, null]
  recovery_email varchar(255) [unique, null]
  address text [null]
  student_status enum [default: 'active', note: 'active | inactive']
  created_at timestamp
  updated_at timestamp
}

Table study_classes {
  id bigint [pk, increment]
  title varchar(255) [not null]
  slug varchar(255) [unique, null]
  course_id bigint [not null, note: 'RESTRICT on delete']
  lesson_id bigint [null]
  teacher_id bigint [null, note: 'primary instructor -> users']
  room_id bigint [null]
  class_type_id bigint [null]
  term_id bigint [null]
  time_id bigint [null]
  status varchar(20) [default: 'upcoming']
  capacity int [default: 20]
  price decimal(12,2) [default: 0]
  document_price decimal(12,2) [default: 0]
  attendance_latitude decimal(10,7) [null]
  attendance_longitude decimal(10,7) [null]
  attendance_radius_meters int [null]
  allowed_ip_ranges varchar(255) [null]
  attendance_ip_policy varchar(20) [default: 'suspicious']
  enrollment_start_date date [null]
  start_date date [null]
  end_date date [null]
  created_at timestamp
  updated_at timestamp
}

Table study_class_instructors {
  id bigint [pk, increment]
  study_class_id bigint [not null]
  user_id bigint [not null]
  term_id bigint [null]
  time_id bigint [null]
  subject varchar(255) [null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (study_class_id, user_id) [unique]
  }
  note: 'co-teachers, in addition to study_classes.teacher_id'
}

Table student_enrollments {
  id bigint [pk, increment]
  public_token varchar(64) [unique, null, note: 'public receipt link']
  study_class_id bigint [null, note: 'nullable: enrolled but not yet placed in a class']
  course_id bigint [null]
  term_id bigint [null]
  time_id bigint [null]
  student_id bigint [not null, note: '-> students.id (was users.id before 2026_08_13)']
  enrollment_status varchar(20) [default: 'active', note: 'pending | active | completed | cancelled']
  payment_status varchar(20) [default: 'unpaid', note: 'unpaid | partial | paid']
  source varchar(30) [null]
  no_room_and_instructor boolean [default: false]
  no_instructor boolean [default: false]
  no_room boolean [default: false]
  fee_amount decimal(12,2) [default: 0, note: 'price snapshot at enrollment time']
  unit_price decimal(12,2) [null]
  document_fee_amount decimal(12,2) [default: 0]
  amount_paid decimal(12,2) [default: 0]
  enrolled_at timestamp [null]
  paid_at timestamp [null]
  active_enrollment_key varchar(50) [note: 'MySQL VIRTUAL generated column: study_class_id:student_id when active, else NULL']
  created_at timestamp
  updated_at timestamp

  indexes {
    active_enrollment_key [unique, name: 'student_enrollments_active_key_unique']
    (student_id, study_class_id, enrollment_status)
  }
  note: 'unique only while active — re-enrollment after cancel is allowed'
}

// ---------------------------------------------------------------------
// ATTENDANCE
// ---------------------------------------------------------------------

Table class_sessions {
  id bigint [pk, increment]
  study_class_id bigint [not null]
  instructor_id bigint [null]
  session_date date [not null]
  scheduled_start datetime [not null]
  scheduled_end datetime [not null]
  status varchar(20) [default: 'pending']
  recorded_at datetime [null]
  grace_minutes_used int [null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (study_class_id, session_date) [unique]
    (status, scheduled_start)
  }
}

Table attendance_sessions {
  id bigint [pk, increment]
  study_class_id bigint [not null]
  qr_token varchar(128) [unique, not null]
  attendance_date date [not null]
  started_at datetime [null]
  expires_at datetime [null]
  status varchar(20) [default: 'active']
  created_by bigint [null]
  stopped_by bigint [null]
  stopped_at datetime [null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (study_class_id, attendance_date) [unique]
    (status, expires_at)
  }
  note: 'one open QR window per class per day'
}

Table student_attendances {
  id bigint [pk, increment]
  study_class_id bigint [not null]
  student_enrollment_id bigint [not null]
  attendance_session_id bigint [null]
  student_id bigint [not null]
  tracked_by bigint [null]
  attendance_date date [not null]
  latitude decimal(10,7) [null]
  longitude decimal(10,7) [null]
  location_accuracy decimal(10,2) [null]
  distance_from_class decimal(10,2) [null]
  ip_address varchar(45) [null]
  user_agent text [null]
  browser varchar(255) [null]
  operating_system varchar(255) [null]
  device_type varchar(255) [null]
  device_identifier varchar(255) [null]
  present boolean [default: false]
  absent boolean [default: false]
  permission boolean [default: false]
  late boolean [default: false]
  locked boolean [default: false]
  lock_reason varchar(255) [null]
  locked_block_id bigint [null]
  verification_status varchar(20) [default: 'verified']
  verification_reason varchar(255) [null]
  source varchar(20) [default: 'manual', note: 'manual | qr']
  note varchar(255) [null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (study_class_id, student_enrollment_id, attendance_date) [unique, name: 'student_attendance_unique_day']
    (study_class_id, student_id, attendance_date) [unique, name: 'student_attendance_unique_student_day']
    (study_class_id, attendance_date, device_identifier) [unique, name: 'student_attendance_unique_device_day']
    (attendance_session_id, student_id) [unique, name: 'student_attendance_unique_session_student']
    (study_class_id, attendance_date)
  }
  note: 'the old `status` string was replaced by 4 boolean flags in 2026_09_12'
}

Table attendance_audit_logs {
  id bigint [pk, increment]
  student_attendance_id bigint [not null]
  changed_by bigint [not null]
  from_status varchar(20) [null]
  to_status varchar(20) [not null]
  from_source varchar(20) [null]
  to_source varchar(20) [not null]
  created_at timestamp
}

Table pre_attendance_requests {
  id bigint [pk, increment]
  study_class_id bigint [not null]
  class_session_id bigint [null]
  requested_by bigint [not null]
  reviewed_by bigint [null]
  session_date date [not null]
  session_status varchar(20) [not null]
  status varchar(20) [default: 'pending']
  note text [null]
  requested_at timestamp [null]
  reviewed_at timestamp [null]
  completed_at timestamp [null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (study_class_id, session_date, requested_by) [unique, name: 'pre_att_request_unique_session_teacher']
    (status, session_date)
  }
  note: 'instructor asks to open attendance before the scheduled session'
}

Table instructor_attendance_blocks {
  id bigint [pk, increment]
  instructor_id bigint [not null, note: '-> users.id']
  triggered_by_session_id bigint [null]
  reason varchar(255) [not null]
  status enum [default: 'active', note: 'active | pending_review | approved_unblock']
  blocked_at timestamp [not null]
  unblock_requested_at timestamp [null]
  reviewed_by bigint [null]
  reviewed_at timestamp [null]
  note text [null]
  created_at timestamp
  updated_at timestamp
}

// ---------------------------------------------------------------------
// LEAVE, PERMISSIONS & BLOCKING RULES
// ---------------------------------------------------------------------

Table student_permissions {
  id bigint [pk, increment]
  student_id bigint [not null]
  study_class_id bigint [null]
  start_date date [not null]
  end_date date [not null]
  reason varchar(255) [null]
  approved_by bigint [null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (student_id, start_date, end_date)
  }
  note: 'instructor-granted permission (quick)'
}

Table leave_request_sessions {
  id bigint [pk, increment]
  student_id bigint [not null]
  created_by bigint [null]
  token_hash varchar(64) [unique, not null]
  expires_at timestamp [not null]
  used_at timestamp [null]
  created_at timestamp
  updated_at timestamp
  note: 'short-lived QR that lets a student file an official leave'
}

Table official_leaves {
  id bigint [pk, increment]
  student_id bigint [not null]
  study_class_id bigint [null]
  start_date date [not null]
  end_date date [not null]
  reason text [not null]
  status varchar(20) [default: 'pending']
  approved_by bigint [null]
  approved_at timestamp [null]
  rejected_by bigint [null]
  rejection_note varchar(255) [null]
  revoked_by bigint [null]
  revoked_at timestamp [null]
  revoked_note varchar(255) [null]
  leave_request_session_id bigint [null]
  created_at timestamp
  updated_at timestamp
  deleted_at timestamp [null, note: 'soft deletes']

  indexes {
    (student_id, status)
    (start_date, end_date)
  }
}

Table official_leave_settings {
  id bigint [pk, increment]
  "key" varchar(255) [unique, not null]
  value varchar(255) [null]
  type varchar(20) [default: 'number']
  label varchar(255) [not null]
  description varchar(255) [null]
  "min" int [null]
  "max" int [null]
  "group" varchar(50) [default: 'official_leave']
  updated_by bigint [null]
  created_at timestamp
  updated_at timestamp
  note: 'keys: monthly_permission_quota, qr_token_ttl_minutes'
}

Table attendance_rules {
  id bigint [pk, increment]
  rule_type enum [not null, note: 'absence | permission']
  limit_count int [not null]
  period_type enum [not null, note: 'week | month | both']
  start_date date [not null]
  is_active boolean [default: true]
  created_by bigint [null]
  created_at timestamp
  updated_at timestamp
}

Table attendance_rule_settings {
  id bigint [pk, increment]
  "key" varchar(255) [unique, not null]
  value varchar(255) [null]
  type varchar(20) [default: 'number']
  label varchar(255) [not null]
  description varchar(255) [null]
  "min" int [null]
  "max" int [null]
  "group" varchar(50) [default: 'attendance_rules']
  updated_by bigint [null]
  created_at timestamp
  updated_at timestamp
  note: 'keys: absence_block_threshold, post_approval_limit, permission_weekly_limit, cycle_anchor_date'
}

Table student_attendance_block {
  id bigint [pk, increment]
  student_id bigint [not null]
  student_tel varchar(20) [not null, note: 'denormalised cycle key']
  course_id bigint [not null]
  study_class_id bigint [null]
  block_type enum [not null, note: 'absence | hard_lock']
  is_approved boolean [default: false]
  blocked_at timestamp [not null]
  approved_at timestamp [null]
  approved_by bigint [null]
  rejected_at timestamp [null]
  admin_comment text [null]
  cycle_start_date date [not null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (student_tel, course_id, block_type, is_approved) [name: 'sab_cycle_key_idx']
    (block_type, is_approved) [name: 'sab_type_approved_idx']
  }
}

Table activity_logs {
  id bigint [pk, increment]
  user_id bigint [null]
  action varchar(255) [not null]
  leave_id bigint [null]
  rule_id bigint [null]
  block_id bigint [null]
  before json [null]
  after json [null]
  ip_address varchar(45) [null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (user_id, action)
  }
}

// ---------------------------------------------------------------------
// GRADING & TEAMS
// ---------------------------------------------------------------------

Table grading_settings {
  id bigint [pk, increment]
  "key" varchar(255) [unique, not null]
  value text [not null]
  type varchar(255) [not null]
  label varchar(255) [not null]
  description text [null]
  "min" decimal(8,2) [null]
  "max" decimal(8,2) [null]
  "group" varchar(255) [not null]
  updated_by bigint [null, note: 'no FK constraint']
  created_at timestamp
  updated_at timestamp
}

Table student_scores {
  id bigint [pk, increment]
  student_enrollment_id bigint [unique, not null]
  study_class_id bigint [not null]
  student_id bigint [not null]
  attendance_score decimal(5,2) [default: 0]
  activity_score decimal(5,2) [default: 0]
  exam_score decimal(5,2) [default: 0]
  created_at timestamp
  updated_at timestamp

  indexes {
    (study_class_id, student_id)
  }
}

Table teams {
  id bigint [pk, increment]
  group_id bigint [not null, note: '-> study_classes.id']
  team_name varchar(255) [not null]
  project_topic varchar(255) [null]
  created_by bigint [null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (group_id, team_name) [unique]
  }
}

Table team_members {
  id bigint [pk, increment]
  team_id bigint [not null]
  group_id bigint [not null, note: '-> study_classes.id']
  student_id bigint [not null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (group_id, student_id) [unique, note: 'a student joins one team per class']
    (team_id, student_id)
  }
}

// ---------------------------------------------------------------------
// INSTRUCTORS
// ---------------------------------------------------------------------

Table instructor_data {
  id bigint [pk, increment]
  user_id bigint [null]
  full_name varchar(255) [null]
  instructor_code varchar(255) [unique, null]
  phone varchar(30) [null]
  specialization varchar(255) [null, note: 'JSON-encoded array of strings']
  employment_type varchar(255) [null]
  shift_group varchar(255) [null]
  available_for_class boolean [default: true]
  can_create_classes boolean [default: false]
  status boolean [default: true]
  headline varchar(255) [null]
  bio text [null]
  date_of_birth date [null]
  gender varchar(255) [null]
  address text [null]
  telegram varchar(255) [null]
  linkedin varchar(255) [null]
  github varchar(255) [null]
  portfolio_url varchar(255) [null]
  work_schedule_id bigint [null]
  created_at timestamp
  updated_at timestamp
}

Table instructor_attachments {
  id bigint [pk, increment]
  instructor_id bigint [not null]
  type varchar(255) [not null]
  title varchar(255) [null]
  file_name varchar(255) [null]
  file_path varchar(255) [not null]
  file_mime varchar(255) [null]
  file_size bigint [null]
  is_primary boolean [default: false]
  created_at timestamp
  updated_at timestamp

  indexes {
    (instructor_id, type)
  }
}

Table instructor_availabilities {
  id bigint [pk, increment]
  instructor_id bigint [not null]
  day_of_week tinyint [not null]
  employment_type varchar(255) [not null]
  shift_group varchar(255) [not null]
  period varchar(255) [not null]
  start_time time [not null]
  end_time time [not null]
  is_active boolean [default: true]
  source varchar(20) [default: 'schedule']
  created_at timestamp
  updated_at timestamp

  indexes {
    (instructor_id, day_of_week)
  }
}

Table instructor_schedule_blocks {
  id bigint [pk, increment]
  instructor_id bigint [not null]
  day_of_week tinyint [not null]
  time_id bigint [not null]
  reason varchar(255) [null]
  created_by bigint [null]
  status varchar(20) [default: 'active']
  created_at timestamp
  updated_at timestamp

  indexes {
    (instructor_id, day_of_week, time_id, status) [name: 'instructor_schedule_blocks_lookup_idx']
  }
}

Table work_schedules {
  id bigint [pk, increment]
  name varchar(255) [not null]
  code varchar(255) [unique, not null]
  description varchar(255) [null]
  is_active boolean [default: true]
  created_at timestamp
  updated_at timestamp
}

Table work_schedule_times {
  id bigint [pk, increment]
  work_schedule_id bigint [not null]
  day_of_week tinyint [not null]
  time_id bigint [not null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (work_schedule_id, day_of_week, time_id) [unique]
  }
}

// ---------------------------------------------------------------------
// CERTIFICATES
// ---------------------------------------------------------------------

Table class_certificate_requests {
  id bigint [pk, increment]
  study_class_id bigint [unique, not null]
  requested_by bigint [null]
  reviewed_by bigint [null]
  certificate_type varchar(30) [default: 'normal']
  status varchar(20) [default: 'pending']
  student_count int [default: 0]
  requested_student_ids json [null]
  note text [null]
  requested_at timestamp [null]
  reviewed_at timestamp [null]
  created_at timestamp
  updated_at timestamp
}

Table certificate_class_requests {
  id bigint [pk, increment]
  study_class_id bigint [not null]
  requested_by bigint [null]
  certificate_type varchar(30) [not null]
  status varchar(20) [default: 'pending']
  requested_at timestamp [null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (study_class_id, certificate_type) [unique, name: 'certificate_class_requests_unique']
  }
}

Table student_certificate_normal {
  id bigint [pk, increment]
  student_id bigint [not null]
  study_class_id bigint [not null]
  certificate_type varchar(30) [default: 'normal']
  student_name varchar(100) [not null]
  course varchar(100) [not null]
  granted_date varchar(50) [not null]
  certificate_id varchar(50) [not null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (student_id, study_class_id, certificate_type) [name: 'student_certificate_normal_lookup_idx']
  }
}

Table certificate_class_free {
  id bigint [pk, increment]
  student_name varchar(100) [not null]
  course varchar(100) [not null]
  end_date date [not null]
  status varchar(20) [default: 'done']
  certificate_code varchar(50) [null]
  created_at timestamp
  updated_at timestamp
  note: 'standalone: free/short-course certificates, no FK to students'
}

Table course_custom {
  id bigint [pk, increment]
  course_name varchar(100) [unique, not null]
  created_at timestamp
  updated_at timestamp
}

Table course_custom_normal {
  id bigint [pk, increment]
  course_name varchar(100) [unique, not null]
  created_at timestamp
  updated_at timestamp
}

// ---------------------------------------------------------------------
// CMS / PUBLIC WEBSITE
// ---------------------------------------------------------------------

Table school_settings {
  id bigint [pk, increment]
  school_name varchar(255) [not null]
  school_logo varchar(255) [null]
  created_at timestamp
  updated_at timestamp
}

Table pages {
  id bigint [pk, increment]
  title varchar(255) [not null]
  slug varchar(255) [unique, not null]
  content longtext [null]
  is_active boolean [default: true]
  created_at timestamp
  updated_at timestamp
}

Table page_heroes {
  id bigint [pk, increment]
  page_id bigint [unique, not null]
  title varchar(255) [null]
  subtitle varchar(255) [null]
  description text [null]
  background_image varchar(255) [null]
  primary_button_text varchar(255) [null]
  primary_button_url varchar(255) [null]
  secondary_button_text varchar(255) [null]
  secondary_button_url varchar(255) [null]
  overlay_opacity tinyint [default: 50]
  text_alignment varchar(20) [default: 'center']
  is_active boolean [default: false]
  created_at timestamp
  updated_at timestamp
}

Table page_hero_images {
  id bigint [pk, increment]
  page_hero_id bigint [not null]
  image varchar(255) [not null]
  position int [default: 1]
  is_active boolean [default: true]
  created_at timestamp
  updated_at timestamp
}

Table menus {
  id bigint [pk, increment]
  parent_id bigint [null, note: 'self-referencing for dropdowns']
  name varchar(255) [not null]
  page_id bigint [not null]
  position int [default: 1]
  is_active boolean [default: true]
  created_at timestamp
  updated_at timestamp
}

Table news {
  id bigint [pk, increment]
  title varchar(255) [not null]
  slug varchar(255) [unique, not null]
  user_id bigint [null, note: 'author']
  excerpt varchar(500) [null]
  description longtext [null]
  published_at date [null]
  sort_order int [default: 0]
  is_featured boolean [default: false]
  is_active boolean [default: true]
  created_at timestamp
  updated_at timestamp
}

Table news_images {
  id bigint [pk, increment]
  news_id bigint [not null]
  image varchar(255) [not null]
  position int [default: 0]
  is_active boolean [default: true]
  created_at timestamp
  updated_at timestamp
}

Table website_videos {
  id bigint [pk, increment]
  title varchar(255) [not null]
  slug varchar(255) [unique, null]
  description text [null]
  video_path varchar(255) [not null]
  thumbnail_path varchar(255) [null]
  duration varchar(255) [null]
  views_count int [default: 0]
  sort_order int [default: 0]
  is_featured boolean [default: false]
  is_active boolean [default: true]
  created_at timestamp
  updated_at timestamp
}

// ---------------------------------------------------------------------
// LEGACY (superseded — still migrated, not written to by current code)
// ---------------------------------------------------------------------

Table classes {
  id bigint [pk, increment]
  title varchar(100) [not null]
  course_id bigint [null]
  lesson_id bigint [null]
  building_id bigint [null]
  floor_id bigint [null]
  room_id bigint [null]
  term_id bigint [null]
  time_id bigint [not null]
  capacity int [not null]
  status varchar(50) [default: 'active']
  created_at timestamp
  updated_at timestamp
  note: 'legacy — replaced by study_classes'
}

Table enrollments {
  id bigint [pk, increment]
  student_id bigint [not null]
  class_id bigint [not null]
  status enum [default: 'active', note: 'active | cancelled | completed']
  enrollment_date date [null]
  note text [null]
  created_at timestamp
  updated_at timestamp

  indexes {
    (student_id, class_id) [unique]
  }
  note: 'legacy — replaced by student_enrollments'
}

// =====================================================================
// RELATIONSHIPS
// =====================================================================

// --- auth ---
Ref: users.created_by > users.id                                   // [delete: set null]
Ref: photos.user_id - users.id                                     // [delete: cascade]
Ref: otp_verifications.user_id > users.id                          // [delete: cascade]
Ref: otp_verifications.created_by > users.id                       // [delete: set null]
Ref: auth_audit_logs.user_id > users.id                            // [delete: set null]
Ref: auth_audit_logs.created_by > users.id                         // [delete: set null]
Ref: dashboard_notifications.otp_verification_id > otp_verifications.id
Ref: access_locations.created_by > users.id
Ref: access_location_routes.access_location_id > access_locations.id // [delete: cascade]
Ref: location_access_logs.user_id > users.id
Ref: location_access_logs.access_location_id > access_locations.id
Ref: model_has_roles.role_id > roles.id                            // [delete: cascade]
Ref: model_has_permissions.permission_id > permissions.id          // [delete: cascade]
Ref: role_has_permissions.role_id > roles.id                       // [delete: cascade]
Ref: role_has_permissions.permission_id > permissions.id           // [delete: cascade]

// --- facility ---
Ref: floors.building_id > buildings.id                             // [delete: set null]
Ref: rooms.floor_id > floors.id                                    // [delete: set null]

// --- catalog ---
Ref: sub_categories.category_id > categories.id                    // [delete: cascade]
Ref: course_tracks.sub_category_id > sub_categories.id             // [delete: cascade]
Ref: course_tracks.class_type_id > class_type.class_type_id        // [delete: set null]
Ref: courses.course_track_id > course_tracks.id                    // [delete: cascade]
Ref: course_lessons.course_id > courses.id                         // [delete: cascade]

// --- scheduling ---
Ref: schedules.class_type_id > class_type.class_type_id            // [delete: cascade]
Ref: schedules.term_id > terms.id                                  // [delete: cascade]
Ref: schedule_time.schedule_id > schedules.id                      // [delete: cascade]
Ref: schedule_time.time_id > times.id                              // [delete: cascade]
Ref: course_enroll_configs.course_id > courses.id                  // [delete: cascade]
Ref: course_enroll_configs.schedule_id > schedules.id              // [delete: cascade]
Ref: course_enroll_configs.time_id > times.id                      // [delete: set null]

// --- students & classes ---
Ref: students.user_id > users.id                                   // [delete: cascade]
Ref: students.course_id > courses.id                               // [delete: set null]
Ref: students.term_id > terms.id                                   // [delete: set null]
Ref: students.time_id > times.id                                   // [delete: set null]

Ref: study_classes.course_id > courses.id                          // [delete: restrict]
Ref: study_classes.lesson_id > course_lessons.id                   // [delete: set null]
Ref: study_classes.teacher_id > users.id                           // [delete: set null]
Ref: study_classes.room_id > rooms.id                              // [delete: set null]
Ref: study_classes.class_type_id > class_type.class_type_id        // [delete: set null]
Ref: study_classes.term_id > terms.id                              // [delete: set null]
Ref: study_classes.time_id > times.id                              // [delete: set null]

Ref: study_class_instructors.study_class_id > study_classes.id     // [delete: cascade]
Ref: study_class_instructors.user_id > users.id                    // [delete: cascade]
Ref: study_class_instructors.term_id > terms.id
Ref: study_class_instructors.time_id > times.id

Ref: student_enrollments.study_class_id > study_classes.id         // [delete: cascade]
Ref: student_enrollments.student_id > students.id                  // [delete: cascade]
Ref: student_enrollments.course_id > courses.id                    // [delete: set null]
Ref: student_enrollments.term_id > terms.id                        // [delete: set null]
Ref: student_enrollments.time_id > times.id                        // [delete: set null]

// --- attendance ---
Ref: class_sessions.study_class_id > study_classes.id              // [delete: cascade]
Ref: class_sessions.instructor_id > users.id                       // [delete: set null]
Ref: attendance_sessions.study_class_id > study_classes.id         // [delete: cascade]
Ref: attendance_sessions.created_by > users.id
Ref: attendance_sessions.stopped_by > users.id

Ref: student_attendances.study_class_id > study_classes.id         // [delete: cascade]
Ref: student_attendances.student_enrollment_id > student_enrollments.id // [delete: cascade]
Ref: student_attendances.attendance_session_id > attendance_sessions.id // [delete: set null]
Ref: student_attendances.student_id > students.id                  // [delete: cascade]
Ref: student_attendances.tracked_by > users.id                     // [delete: set null]
Ref: student_attendances.locked_block_id > student_attendance_block.id

Ref: attendance_audit_logs.student_attendance_id > student_attendances.id // [delete: cascade]
Ref: attendance_audit_logs.changed_by > users.id                   // [delete: cascade]

Ref: pre_attendance_requests.study_class_id > study_classes.id     // [delete: cascade]
Ref: pre_attendance_requests.class_session_id > class_sessions.id  // [delete: set null]
Ref: pre_attendance_requests.requested_by > users.id               // [delete: cascade]
Ref: pre_attendance_requests.reviewed_by > users.id                // [delete: set null]

Ref: instructor_attendance_blocks.instructor_id > users.id         // [delete: cascade]
Ref: instructor_attendance_blocks.triggered_by_session_id > class_sessions.id
Ref: instructor_attendance_blocks.reviewed_by > users.id

// --- leave & rules ---
Ref: student_permissions.student_id > students.id                  // [delete: cascade]
Ref: student_permissions.study_class_id > study_classes.id         // [delete: cascade]
Ref: student_permissions.approved_by > users.id                    // [delete: set null]

Ref: leave_request_sessions.student_id > students.id               // [delete: cascade]
Ref: leave_request_sessions.created_by > users.id                  // [delete: set null]

Ref: official_leaves.student_id > students.id                      // [delete: cascade]
Ref: official_leaves.study_class_id > study_classes.id             // [delete: set null]
Ref: official_leaves.approved_by > users.id
Ref: official_leaves.rejected_by > users.id
Ref: official_leaves.revoked_by > users.id
Ref: official_leaves.leave_request_session_id > leave_request_sessions.id
Ref: official_leave_settings.updated_by > users.id

Ref: attendance_rules.created_by > users.id
Ref: attendance_rule_settings.updated_by > users.id

Ref: student_attendance_block.student_id > students.id             // [delete: cascade]
Ref: student_attendance_block.course_id > courses.id               // [delete: cascade]
Ref: student_attendance_block.study_class_id > study_classes.id    // [delete: set null]
Ref: student_attendance_block.approved_by > users.id

Ref: activity_logs.user_id > users.id                              // [delete: cascade]
Ref: activity_logs.leave_id > official_leaves.id                   // [delete: set null]
Ref: activity_logs.rule_id > attendance_rules.id                   // [delete: set null]
Ref: activity_logs.block_id > student_attendance_block.id          // [delete: set null]

// --- grading & teams ---
Ref: student_scores.student_enrollment_id - student_enrollments.id // [delete: cascade]
Ref: student_scores.study_class_id > study_classes.id              // [delete: cascade]
Ref: student_scores.student_id > students.id                       // [delete: cascade]
Ref: teams.group_id > study_classes.id                             // [delete: cascade]
Ref: teams.created_by > users.id
Ref: team_members.team_id > teams.id                               // [delete: cascade]
Ref: team_members.group_id > study_classes.id                      // [delete: cascade]
Ref: team_members.student_id > students.id                         // [delete: cascade]

// --- instructors ---
Ref: instructor_data.user_id > users.id                            // [delete: set null]
Ref: instructor_data.work_schedule_id > work_schedules.id          // [delete: set null]
Ref: instructor_attachments.instructor_id > instructor_data.id     // [delete: cascade]
Ref: instructor_availabilities.instructor_id > instructor_data.id  // [delete: cascade]
Ref: instructor_schedule_blocks.instructor_id > instructor_data.id // [delete: cascade]
Ref: instructor_schedule_blocks.time_id > times.id                 // [delete: cascade]
Ref: instructor_schedule_blocks.created_by > users.id
Ref: work_schedule_times.work_schedule_id > work_schedules.id      // [delete: cascade]
Ref: work_schedule_times.time_id > times.id                        // [delete: cascade]

// --- certificates ---
Ref: class_certificate_requests.study_class_id - study_classes.id  // [delete: cascade]
Ref: class_certificate_requests.requested_by > users.id
Ref: class_certificate_requests.reviewed_by > users.id
Ref: certificate_class_requests.study_class_id > study_classes.id  // [delete: cascade]
Ref: certificate_class_requests.requested_by > users.id
Ref: student_certificate_normal.student_id > students.id           // [delete: cascade]
Ref: student_certificate_normal.study_class_id > study_classes.id  // [delete: cascade]

// --- cms ---
Ref: page_heroes.page_id - pages.id                                // [delete: cascade]
Ref: page_hero_images.page_hero_id > page_heroes.id                // [delete: cascade]
Ref: menus.page_id > pages.id                                      // [delete: cascade]
Ref: menus.parent_id > menus.id                                    // [delete: set null]
Ref: news.user_id > users.id                                       // [delete: set null]
Ref: news_images.news_id > news.id                                 // [delete: cascade]

// --- legacy ---
Ref: classes.course_id > courses.id
Ref: classes.lesson_id > course_lessons.id
Ref: classes.building_id > buildings.id
Ref: classes.floor_id > floors.id
Ref: classes.room_id > rooms.id
Ref: classes.term_id > terms.id
Ref: classes.time_id > times.id                                    // [delete: cascade]
Ref: enrollments.student_id > students.id                          // [delete: cascade]
Ref: enrollments.class_id > classes.id                             // [delete: cascade]

// =====================================================================
// TABLE GROUPS
// =====================================================================

TableGroup "Auth & Access" {
  users
  photos
  otp_verifications
  otp_verification_settings
  auth_audit_logs
  dashboard_notifications
  login_lockouts
  login_lockout_tiers
  login_lockout_settings
  access_locations
  access_location_routes
  location_access_logs
  roles
  permissions
  model_has_roles
  model_has_permissions
  role_has_permissions
}

TableGroup "Facility" {
  buildings
  floors
  rooms
}

TableGroup "Course Catalog" {
  categories
  sub_categories
  course_tracks
  courses
  course_lessons
}

TableGroup "Scheduling" {
  class_type
  terms
  times
  schedules
  schedule_time
  holidays
  course_enroll_configs
}

TableGroup "Students & Classes" {
  students
  study_classes
  study_class_instructors
  student_enrollments
}

TableGroup "Attendance" {
  class_sessions
  attendance_sessions
  student_attendances
  attendance_audit_logs
  pre_attendance_requests
  instructor_attendance_blocks
}

TableGroup "Leave & Rules" {
  student_permissions
  leave_request_sessions
  official_leaves
  official_leave_settings
  attendance_rules
  attendance_rule_settings
  student_attendance_block
  activity_logs
}

TableGroup "Grading & Teams" {
  grading_settings
  student_scores
  teams
  team_members
}

TableGroup "Instructors" {
  instructor_data
  instructor_attachments
  instructor_availabilities
  instructor_schedule_blocks
  work_schedules
  work_schedule_times
}

TableGroup "Certificates" {
  class_certificate_requests
  certificate_class_requests
  student_certificate_normal
  certificate_class_free
  course_custom
  course_custom_normal
}

TableGroup "CMS / Website" {
  school_settings
  pages
  page_heroes
  page_hero_images
  menus
  news
  news_images
  website_videos
}

TableGroup "Legacy" {
  classes
  enrollments
}
```

---

## Things worth knowing before you read the diagram

**1. `students` vs `users` — two different identities.**
`users` holds admin/instructor logins. `students` is its own table and `students.user_id` has been **nullable** since `2026_08_13` — a student record can exist with no login at all. Migration `2026_08_13_000002` rewrote `student_enrollments.student_id` and `student_attendances.student_id` from `users.id` to `students.id`. Any older code or query that joins those columns to `users` is wrong.

**2. `student_enrollments` allows re-enrollment.**
The plain `UNIQUE(study_class_id, student_id)` was dropped. It is replaced by a MySQL **virtual generated column**:

```sql
active_enrollment_key VARCHAR(50)
  GENERATED ALWAYS AS (
    CASE WHEN enrollment_status = 'active'
         THEN CONCAT(study_class_id, ':', student_id)
         ELSE NULL END
  ) VIRTUAL
```

with a unique index on it. So a student can only be *active* once per class, but may re-enroll after cancelling. On PostgreSQL the same rule is a partial unique index instead. `study_class_id` is also nullable — a student can be enrolled before being placed into a class (see the `no_room` / `no_instructor` / `no_room_and_instructor` flags).

**3. Attendance status is four booleans, not a string.**
`2026_09_12_215204` dropped `student_attendances.status` and replaced it with `present` / `absent` / `permission` / `late`. Nothing at the DB level stops two of them being true at once — that invariant lives in application code.

**4. Four unique indexes guard QR attendance.** Per class per day a student can be recorded once by enrollment, once by student, once per device, and once per QR session. `device_identifier` being in a unique index means two students cannot share a phone on the same day for the same class.

**5. Pricing is snapshotted.** Live prices live in `course_enroll_configs` (`unit_price`, `course_price`, `document_price`, with `selected_price_type` choosing which applies). `students` and `student_enrollments` each copy `fee_amount` / `unit_price` / `document_fee_amount` at registration time so later price changes don't rewrite history. `courses` itself has **no** price columns any more.

**6. `class_type` has a non-standard primary key** — `class_type_id`, not `id`. Every FK to it must say `->references('class_type_id')`.

**7. Two parallel certificate-request tables.** `class_certificate_requests` (one per class, with review workflow and `requested_student_ids` JSON) and `certificate_class_requests` (one per class *per certificate type*, lighter). They were added two days apart and both are live.

**8. `teams.group_id` and `team_members.group_id` point at `study_classes`,** not at any table named `groups`. The column name is historical.

**9. Orphan models.** `app/Models/Registration.php` and `app/Models/AuditLog.php` have no corresponding table in any migration — `AuditLog` appears to be an earlier draft of `activity_logs`. They are not in the diagram.

**10. `classes` / `enrollments` are legacy.** They were never dropped and still have live FKs, but current code uses `study_classes` / `student_enrollments`. Treat them as read-only history.
