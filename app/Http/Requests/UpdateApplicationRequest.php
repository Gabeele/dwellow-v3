<?php

namespace App\Http\Requests;

use App\Enums\ApplicationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'required', Rule::enum(ApplicationStatus::class)],
            'landlord_notes' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ];
    }
}
