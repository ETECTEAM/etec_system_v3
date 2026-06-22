<?php

namespace App\Modules\User\Requests;

use App\Modules\User\Data\StoreUserData;
use App\Modules\User\Services\UserService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool { return $this->user() !== null; }

    public function rules(): array
    {
        $roles = $this->user() ? app(UserService::class)->assignableRolesFor($this->user()) : [];

        return $this->profileRules($roles);
    }

    protected function profileRules(array $roles): array
    {
        $student = $this->input('role') === 'student';
        $instructor = $this->input('role') === 'instructor';

        return [
            'name' => [Rule::requiredIf(! $student && ! $instructor), 'nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', Rule::in($roles)],
            'account_status' => ['required', 'boolean'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'student_code' => [Rule::requiredIf($student), 'nullable', 'string', 'max:255', Rule::unique('student_data', 'student_code')],
            'student_full_name' => [Rule::requiredIf($student), 'nullable', 'string', 'max:255'],
            'student_first_name' => ['nullable', 'string', 'max:255'], 'student_last_name' => ['nullable', 'string', 'max:255'],
            'student_full_name_kh' => ['nullable', 'string', 'max:255'], 'student_gender' => ['nullable', 'string', 'max:20'],
            'student_date_of_birth' => ['nullable', 'date'], 'student_phone' => ['nullable', 'string', 'max:30'],
            'student_email' => ['nullable', 'email', 'max:255'], 'student_class_id' => ['nullable', 'integer'],
            'parent_name' => ['nullable', 'string', 'max:255'], 'parent_phone' => ['nullable', 'string', 'max:30'],
            'student_address' => ['nullable', 'string'], 'student_status' => ['nullable', 'boolean'],
            'instructor_code' => [Rule::requiredIf($instructor), 'nullable', 'string', 'max:255', Rule::unique('instructor_data', 'instructor_code')],
            'instructor_full_name' => [Rule::requiredIf($instructor), 'nullable', 'string', 'max:255'],
            'instructor_first_name' => ['nullable', 'string', 'max:255'], 'instructor_last_name' => ['nullable', 'string', 'max:255'],
            'instructor_full_name_kh' => ['nullable', 'string', 'max:255'], 'instructor_gender' => ['nullable', 'string', 'max:20'],
            'instructor_date_of_birth' => ['nullable', 'date'], 'instructor_phone' => ['nullable', 'string', 'max:30'],
            'instructor_email' => ['nullable', 'email', 'max:255'], 'specialization' => ['nullable', 'string', 'max:255'],
            'employment_type' => [Rule::requiredIf($instructor), 'nullable', Rule::in(['full_time', 'part_time'])],
            'shift_preference' => [Rule::requiredIf($instructor), 'nullable', Rule::in(['morning_evening', 'afternoon_evening', 'morning_afternoon'])],
            'available_for_class' => ['nullable', 'boolean'], 'hire_date' => ['nullable', 'date'],
            'instructor_address' => ['nullable', 'string'], 'instructor_status' => ['nullable', 'boolean'],
        ];
    }

    public function toData(): StoreUserData
    {
        $data = $this->validated();
        return new StoreUserData($this->displayName($data), $data['email'], $data['password'], $data['role'], (bool) $data['account_status'], $this->file('avatar'), $this->studentData($data), $this->instructorData($data));
    }

    protected function displayName(array $data): string { return $data['role'] === 'student' ? $data['student_full_name'] : ($data['role'] === 'instructor' ? $data['instructor_full_name'] : $data['name']); }
    protected function studentData(array $data): array { return ['student_code' => $data['student_code'] ?? null, 'first_name' => $data['student_first_name'] ?? null, 'last_name' => $data['student_last_name'] ?? null, 'full_name' => $data['student_full_name'] ?? null, 'full_name_kh' => $data['student_full_name_kh'] ?? null, 'gender' => $data['student_gender'] ?? null, 'date_of_birth' => $data['student_date_of_birth'] ?? null, 'phone' => $data['student_phone'] ?? null, 'email' => $data['student_email'] ?? null, 'class_id' => $data['student_class_id'] ?? null, 'parent_name' => $data['parent_name'] ?? null, 'parent_phone' => $data['parent_phone'] ?? null, 'address' => $data['student_address'] ?? null, 'status' => $data['student_status'] ?? true]; }
    protected function instructorData(array $data): array { return ['instructor_code' => $data['instructor_code'] ?? null, 'first_name' => $data['instructor_first_name'] ?? null, 'last_name' => $data['instructor_last_name'] ?? null, 'full_name' => $data['instructor_full_name'] ?? null, 'full_name_kh' => $data['instructor_full_name_kh'] ?? null, 'gender' => $data['instructor_gender'] ?? null, 'date_of_birth' => $data['instructor_date_of_birth'] ?? null, 'phone' => $data['instructor_phone'] ?? null, 'email' => $data['instructor_email'] ?? null, 'specialization' => $data['specialization'] ?? null, 'employment_type' => $data['employment_type'] ?? null, 'shift_preference' => $data['shift_preference'] ?? null, 'available_for_class' => $data['available_for_class'] ?? true, 'hire_date' => $data['hire_date'] ?? null, 'address' => $data['instructor_address'] ?? null, 'status' => $data['instructor_status'] ?? true]; }
}
