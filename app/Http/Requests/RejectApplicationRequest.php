<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RejectApplicationRequest extends FormRequest
{
    /**
     * Authorization is enforced by the ApplicationPolicy in the controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notify_applicant' => ['sometimes', 'boolean'],
        ];
    }
}
