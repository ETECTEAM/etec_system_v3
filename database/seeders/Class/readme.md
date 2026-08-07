# Class Seeder Documentation

## ClassType
`ClassType` defines the classification of a class session.

### Table
- `class_type`

### Columns
- `class_type_id` - primary key
- `type_name` - name of the class type (example: Physical Class, Hybrid Class, Online Class, Basic IT)
- `description` - a short description of the class type
- `is_active` - active state flag
- `created_at`, `updated_at` - timestamps

### Purpose
The `ClassType` entries are used to categorize class sessions and help the UI filter or label classes by type.

## ClassList
`ClassList` stores scheduled class sessions with related metadata.

### Table
- `class_list`

### Columns
- `teacher_id` - optional teacher reference
- `course_id` - optional course reference
- `lesson_id` - optional lesson reference
- `term_id` - optional term reference
- `time_id` - optional time reference
- `building_id` - optional building reference
- `floor_id` - optional floor reference
- `room_id` - optional room reference
- `class_type_id` - optional class type reference
- `student_count` - number of enrolled students
- `status` - class status (`progress`, `completed`, `cancelled`)
- `created_at`, `updated_at` - timestamps

### Purpose
`ClassList` is used for class management screens and stores each scheduled class instance.
It is displayed in the dashboard list and connects classes to their schedule, location, and type.

## Seeder Notes
- `ClassTypeSeeder` populates the class type table with base categories.

##command to run
#php artisan db:seed