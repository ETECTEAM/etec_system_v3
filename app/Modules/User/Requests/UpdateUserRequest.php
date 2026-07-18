<?php

namespace App\Modules\User\Requests;

use App\Modules\User\Data\UpdateUserData;
use App\Modules\User\Services\UserService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates dashboard user update requests and transforms the payload
 * into an UpdateUserData DTO consumed by the user update service.
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the current user is allowed to make this request.
     *
     * @return bool True as long as a user is authenticated.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Build the validation rules for updating a user.
     *
     * Starts from the shared profile rules, then overrides the fields that
     * need update-specific behavior: password becomes optional, and unique
     * checks must ignore the record currently being updated.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // The user being updated, used to exclude its own row from unique checks.
        $target = $this->route('user');
        $roles = $this->user() ? app(UserService::class)->assignableRolesFor($this->user()) : [];
        $rules = $this->profileRules($roles);
        $rules['email'] = ['required', 'string', 'email', 'max:255', 'regex:/^[a-zA-Z0-9._%+-]+@etec\.com$/', Rule::unique('users', 'email')->ignore($target?->id)];
        // Unlike creation, password is optional on update (omit to keep the current one).
        $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        // Index 4 is the Rule::unique() slot reserved (but left absent) in profileRules();
        // patched in here since it needs the route-bound target to ignore.
        $rules['student_code'][4] = Rule::unique('students', 'student_code')->ignore($target?->student?->id);
        $rules['instructor_code'][4] = Rule::unique('instructor_data', 'instructor_code')->ignore($target?->instructorData?->id);
        return $rules;
    }

    /**
     * Validate the request and map the payload into an UpdateUserData DTO.
     *
     * @return UpdateUserData
     */
    public function toData(): UpdateUserData
    {
        $data = $this->validated();
        return new UpdateUserData($this->displayName($data), $data['email'], $data['password'] ?? null, $data['role'], $data['account_status'], $this->file('avatar'), $this->student($data), $this->instructorData($data));
    }

    /**
     * Base validation rules shared by the update request, before the
     * email/password/uniqueness overrides in rules() are applied.
     *
     * @param  array<int, string>  $roles  Roles the requester is allowed to assign.
     * @return array<string, mixed>
     */
    protected function profileRules(array $roles): array
    {
        return [
            'name' => ['required_unless:role,student,instructor', 'nullable', 'string', 'max:255'],
            'role' => ['required', 'string', Rule::in($roles)],
            'account_status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            // student_* fields are nullable here since they only apply when role === 'student'.
            'student_code' => ['nullable', 'string', 'max:255'],
            'student_full_name' => ['nullable', 'string', 'max:255'],
            'student_first_name' => ['nullable', 'string', 'max:255'], 'student_last_name' => ['nullable', 'string', 'max:255'],
            'student_full_name_kh' => ['nullable', 'string', 'max:255'], 'student_gender' => ['nullable', 'string', 'max:20'],
            'student_date_of_birth' => ['nullable', 'date'], 'student_phone' => ['nullable', 'string', 'max:30'],
            'student_email' => ['nullable', 'email', 'max:255'], 'student_class_id' => ['nullable', 'integer'],
            'parent_name' => ['nullable', 'string', 'max:255'], 'parent_phone' => ['nullable', 'string', 'max:30'],
            'student_address' => ['nullable', 'string'], 'student_status' => ['nullable', 'boolean'],
            // instructor_* fields are nullable here since they only apply when role === 'instructor'.
            'instructor_code' => ['nullable', 'string', 'max:255'],
            'instructor_full_name' => ['nullable', 'string', 'max:255'],
            'instructor_first_name' => ['nullable', 'string', 'max:255'], 'instructor_last_name' => ['nullable', 'string', 'max:255'],
            'instructor_full_name_kh' => ['nullable', 'string', 'max:255'], 'instructor_gender' => ['nullable', 'string', 'max:20'],
            'instructor_date_of_birth' => ['nullable', 'date'], 'instructor_phone' => ['nullable', 'string', 'max:30'],
            'instructor_email' => ['nullable', 'email', 'max:255'], 'specialization' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', Rule::in(['full_time', 'part_time'])],
            'shift_preference' => ['nullable', Rule::in(['morning_afternoon', 'morning_evening', 'afternoon_evening_11', 'afternoon_evening_1230'])],
            'available_for_class' => ['nullable', 'boolean'], 'hire_date' => ['nullable', 'date'],
            'instructor_address' => ['nullable', 'string'], 'instructor_status' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Resolve the display name from the role-specific full name field,
     * since students/instructors don't submit the generic `name` field.
     *
     * @param  array<string, mixed>  $data  Validated request data.
     * @return string
     */
    protected function displayName(array $data): string
    {
        if ($data['role'] === 'student') {
            return $data['student_full_name'] ?? '';
        }

        if ($data['role'] === 'instructor') {
            return $data['instructor_full_name'] ?? '';
        }

        return $data['name'];
    }

    /**
     * Extract the student-specific fields from the validated data, regardless
     * of the selected role (fields simply stay null when role !== 'student').
     *
     * @param  array<string, mixed>  $data  Validated request data.
     * @return array<string, mixed>
     */
    protected function student(array $data): array
    {
        return [
            'student_code' => $data['student_code'] ?? null,
            'first_name' => $data['student_first_name'] ?? null,
            'last_name' => $data['student_last_name'] ?? null,
            'full_name' => $data['student_full_name'] ?? null,
            'full_name_kh' => $data['student_full_name_kh'] ?? null,
            'gender' => $data['student_gender'] ?? null,
            'date_of_birth' => $data['student_date_of_birth'] ?? null,
            'phone' => $data['student_phone'] ?? null,
            'email' => $data['student_email'] ?? null,
            'class_id' => $data['student_class_id'] ?? null,
            'parent_name' => $data['parent_name'] ?? null,
            'parent_phone' => $data['parent_phone'] ?? null,
            'address' => $data['student_address'] ?? null,
            'status' => $data['student_status'] ?? true,
        ];
    }

    /**
     * Extract the instructor-specific fields from the validated data, regardless
     * of the selected role (fields simply stay null when role !== 'instructor').
     *
     * @param  array<string, mixed>  $data  Validated request data.
     * @return array<string, mixed>
     */
    protected function instructorData(array $data): array
    {
        return [
            'instructor_code' => $data['instructor_code'] ?? null,
            'full_name' => $data['instructor_full_name'] ?? null,
            'phone' => $data['instructor_phone'] ?? null,
            'employment_type' => $data['employment_type'] ?? null,
            'shift_group' => $data['shift_preference'] ?? null,
            'available_for_class' => $data['available_for_class'] ?? true,
            'status' => $data['instructor_status'] ?? true,
        ];
    }
}
