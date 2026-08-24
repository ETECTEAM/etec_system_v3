<?php

namespace App\Modules\Website\Requests;

use App\Models\Course;
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
            'time_id' => ['required', 'integer', 'exists:times,id'],
        ];
    }
}
