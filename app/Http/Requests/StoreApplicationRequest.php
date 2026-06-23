<?php

namespace App\Http\Requests;

use App\Enums\FieldType;
use App\Models\ApplicationLink;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * The link's open state is enforced in the controller (it needs to render a
     * friendly closed page rather than a bare 403), so authorization is open here.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Build the validation rules from the unit's current application-form schema.
     *
     * Each field contributes a rule under `answers.{key}` derived from its
     * `FieldType` and `required` flag, so the form a landlord configured is the
     * single source of truth for what a submission must contain.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        $rules = ['answers' => ['present', 'array']];

        foreach ($this->schemaFields() as $field) {
            $key = $field['key'];
            $type = FieldType::tryFrom($field['type'] ?? '');
            $required = (bool) ($field['required'] ?? false);

            if ($type === null) {
                continue;
            }

            $rules["answers.{$key}"] = $this->rulesForField($type, $required, $field['options'] ?? []);

            if ($type === FieldType::MultiChoice) {
                $rules["answers.{$key}.*"] = ['string', Rule::in($field['options'] ?? [])];
            }
        }

        return $rules;
    }

    /**
     * Build the rule list for a single field.
     *
     * @param  array<int, string>  $options
     * @return array<int, ValidationRule|string>
     */
    private function rulesForField(FieldType $type, bool $required, array $options): array
    {
        $presence = $required ? 'required' : 'nullable';

        return match ($type) {
            FieldType::ShortText => [$presence, 'string', 'max:255'],
            FieldType::LongText => [$presence, 'string', 'max:5000'],
            FieldType::Number => [$presence, 'integer', 'min:0'],
            FieldType::Currency => [$presence, 'numeric', 'min:0'],
            FieldType::Date => [$presence, 'date'],
            FieldType::SingleChoice => [$presence, 'string', Rule::in($options)],
            FieldType::MultiChoice => [$presence, 'array'],
            FieldType::Boolean => [$presence, 'boolean'],
            FieldType::Consent => $required ? ['accepted'] : ['nullable', 'boolean'],
            FieldType::Reference => [$required ? 'required' : 'nullable', 'array'],
            FieldType::File => [$presence, 'file', 'mimes:pdf,jpg,jpeg,png,heic,webp,doc,docx', 'max:10240'],
        };
    }

    /**
     * The current field schema for the unit the link belongs to.
     *
     * @return list<array<string, mixed>>
     */
    private function schemaFields(): array
    {
        $link = $this->route('link');

        if (! $link instanceof ApplicationLink) {
            return [];
        }

        return $link->unit->applicationForm?->fields ?? [];
    }
}
