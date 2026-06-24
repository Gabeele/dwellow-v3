<?php

namespace App\Http\Requests;

use App\Screening\DefaultApplicationForm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateApplicationFormRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * The builder only sends which sections are enabled; the controller rebuilds
     * the full schema from the catalog, so we just need a valid list of known,
     * unlockable section keys.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'enabled_sections' => ['present', 'array'],
            'enabled_sections.*' => ['string', Rule::in(DefaultApplicationForm::sectionKeys())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'enabled_sections.*.in' => __('One of the selected sections is not a recognised section.'),
        ];
    }
}
