<?php

namespace App\Modules\User\Requests;

use App\Modules\User\Data\UpdateUserData;
use App\Modules\User\Services\UserService;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends StoreUserRequest
{
    public function rules(): array
    {
        $target = $this->route('user');
        $roles = $this->user() ? app(UserService::class)->assignableRolesFor($this->user()) : [];
        $rules = $this->profileRules($roles);
        $rules['email'] = ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target?->id)];
        $rules['password'] = ['nullable', 'string', 'min:8', 'confirmed'];
        $rules['student_code'][4] = Rule::unique('student_data', 'student_code')->ignore($target?->studentData?->id);
        $rules['instructor_code'][4] = Rule::unique('instructor_data', 'instructor_code')->ignore($target?->instructorData?->id);
        return $rules;
    }

    public function toData(): UpdateUserData
    {
        $data = $this->validated();
        return new UpdateUserData($this->displayName($data), $data['email'], $data['password'] ?? null, $data['role'], (bool) $data['account_status'], $this->file('avatar'), $this->studentData($data), $this->instructorData($data));
    }
}
