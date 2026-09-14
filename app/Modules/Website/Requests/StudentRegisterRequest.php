<?php

namespace App\Modules\Website\Requests;

use App\Models\ClassType;
use App\Models\Course;
use App\Models\Schedule;
use App\Modules\Enroll\Queries\GetCourseClassSchedules;
use App\Rules\LatinName;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', new LatinName],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'phone' => ['required', 'string', 'regex:/^[0-9]{9,12}$/'],
            'category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('status', 'active')],
            'course_id' => [
                'required', 'integer', Rule::exists('courses', 'id')->where('status', 'active'),
                function (string $attribute, mixed $value, Closure $fail): void {
                    $belongsToCategory = Course::query()
                        ->whereKey($value)
                        ->whereHas('track.subCategory.category', fn ($query) => $query->whereKey($this->input('category_id')))
                        ->exists();

                    if (! $belongsToCategory) {
                        $fail('Please select a course from the selected category.');
                    }
                },
            ],
            'term_id' => ['required', 'integer', 'exists:terms,id'],
            // Disambiguates schedules that share a term+time (e.g. Physical/Online).
            'class_type_id' => ['required', 'integer', Rule::in(
                ClassType::query()
                    ->whereIn('type_name', GetCourseClassSchedules::AVAILABLE_CLASS_TYPES)
                    ->pluck('class_type_id')
            )],
            'time_id' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $belongsToSchedule = Schedule::query()
                        ->where('term_id', $this->input('term_id'))
                        ->where('class_type_id', $this->input('class_type_id'))
                        ->whereHas('times', fn ($query) => $query->whereKey($value))
                        ->exists();

                    if (! $belongsToSchedule) {
                        $fail('That time slot is not available for the selected schedule.');
                    }
                },
            ],
        ];
    }
}
