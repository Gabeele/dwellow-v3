<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ApproveApplicationRequest extends FormRequest
{
    /**
     * Authorization is enforced by the ApplicationPolicy in the controller.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The decision toggles the landlord chose in the approval dialog. Each is
     * optional and read with boolean() so an omitted box simply means "off".
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notify_applicant' => ['sometimes', 'boolean'],
            'decline_others' => ['sometimes', 'boolean'],
            'notify_declined' => ['sometimes', 'boolean'],
        ];
    }
}
