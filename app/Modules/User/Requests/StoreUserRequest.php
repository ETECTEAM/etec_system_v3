<?php

namespace App\Modules\User\Requests;

use App\Models\SubCategory;
use App\Modules\User\Data\StoreUserData;
use App\Modules\User\Services\UserService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates dashboard user creation requests and transforms the payload
 * into a StoreUserData DTO consumed by the user creation service.
 */
class StoreUserRequest extends FormRequest
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
     * Build the validation rules for creating a user.
     *
     * Student/instructor fields are all nullable at this layer because only
     * the fields matching the submitted `role` are actually required; the
     * DTO builders below pick out the relevant subset after validation.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $service = app(UserService::class);
        // Roles the requester is allowed to assign depend on their own role/authority.
        $roles = $this->user() ? $service->assignableRolesFor($this->user()) : [];

        // Limit role choices based on the authenticated user's authority.
        return [
            'name' => ['required_unless:role,student,instructor', 'nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'regex:/^[a-zA-Z0-9._%+-]+@etec\.com$/', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in($roles)],
            'account_status' => ['required', 'string', Rule::in(['active', 'inactive'])],
            // FILE: disabled - not using file uploads
            // 'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            // student_* fields are nullable here since they only apply when role === 'student'.
            'student_full_name' => ['nullable', 'string', 'max:255'],
            'student_first_name' => ['nullable', 'string', 'max:255'], 'student_last_name' => ['nullable', 'string', 'max:255'],
            'student_full_name_kh' => ['nullable', 'string', 'max:255'], 'student_gender' => ['nullable', 'string', 'max:20'],
            'student_date_of_birth' => ['nullable', 'date'], 'student_phone' => ['nullable', 'string', 'max:30'],
            'student_email' => ['nullable', 'email', 'max:255', 'regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/'], 'student_class_id' => ['nullable', 'integer'],
            'parent_name' => ['nullable', 'string', 'max:255'], 'parent_phone' => ['nullable', 'string', 'max:30'],
            'student_address' => ['nullable', 'string'], 'student_status' => ['nullable', 'boolean'],
            // instructor_* fields are nullable here since they only apply when role === 'instructor'.
            'instructor_code' => ['nullable', 'string', 'max:255', Rule::unique('instructor_data', 'instructor_code')],
            'instructor_full_name' => ['nullable', 'string', 'max:255'],
            'instructor_first_name' => ['nullable', 'string', 'max:255'], 'instructor_last_name' => ['nullable', 'string', 'max:255'],
            'instructor_full_name_kh' => ['nullable', 'string', 'max:255'], 'instructor_gender' => ['nullable', 'string', 'max:20'],
            'instructor_date_of_birth' => ['nullable', 'date'], 'instructor_phone' => ['nullable', 'string', 'max:30'],
            'instructor_email' => ['nullable', 'email', 'max:255', 'regex:/^[a-zA-Z0-9._%+-]+@gmail\.com$/'], 'specialization' => ['nullable', 'array'],
            'specialization.*' => ['string', Rule::in(SubCategory::where('status', 'active')->pluck('name'))],
            'employment_type' => ['nullable', Rule::in(['full_time', 'part_time'])],
            'shift_preference' => ['nullable', Rule::in(['morning_afternoon', 'morning_evening', 'afternoon_evening_11', 'afternoon_evening_1230'])],
            'available_for_class' => ['nullable', 'boolean'], 'hire_date' => ['nullable', 'date'],
            'instructor_address' => ['nullable', 'string'], 'instructor_status' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Validate the request and map the payload into a StoreUserData DTO.
     *
     * @return StoreUserData
     */
    public function toData(): StoreUserData
    {
        $data = $this->validated();
        
        return new StoreUserData(
            $this->displayName($data), 
            $data['email'], 
            $data['password'], 
            $data['role'], 
            $data['account_status'], 
            null, // FILE: disabled - $this->file('avatar')
            $this->student($data), 
            $this->instructorData($data)
        );
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
            'status' => $data['student_status'] ?? true
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
            'specialization' => $data['specialization'] ?? null,
            'employment_type' => $data['employment_type'] ?? null, 
            'shift_group' => $data['shift_preference'] ?? null, 
            'available_for_class' => $data['available_for_class'] ?? true, 
            'status' => $data['instructor_status'] ?? true
        ]; 
    }
}
