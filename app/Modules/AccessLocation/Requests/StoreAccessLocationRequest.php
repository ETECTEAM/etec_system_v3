<?php

namespace App\Modules\AccessLocation\Requests;

use App\Modules\AccessLocation\Support\LockableRoutes;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccessLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:20000'],
            'is_active' => ['boolean'],
            'description' => ['nullable', 'string', 'max:1000'],
            'route_keys' => ['array'],
            'route_keys.*' => ['string', Rule::in(LockableRoutes::keys())],
        ];
    }
}
