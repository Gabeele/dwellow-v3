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
     * We only require a real link here; whether the link is still open is checked
     * in the controller so a link that closed mid-application can send the
     * applicant to a friendly "closed" page rather than a bare 403.
     */
    public function authorize(): bool
    {
        return $this->route('link') instanceof ApplicationLink;
    }

    /**
     * Build the validation rules from the unit's current application-form schema.
     *
     * Each enabled field contributes a rule under `answers.{key}` derived from its
     * `FieldType` and `required` flag, so the form a landlord configured is the
     * single source of truth for what a submission must contain.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        $rules = [
            'answers' => ['present', 'array'],
        ];

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

            // A reference is a structured contact: enforce its sub-fields so we
            // never store a blank name or a malformed email.
            if ($type === FieldType::Reference) {
                $rules["answers.{$key}.name"] = [$required ? 'required' : 'nullable', 'string', 'max:255'];
                $rules["answers.{$key}.email"] = ['nullable', 'email', 'max:255'];
                $rules["answers.{$key}.phone"] = ['nullable', 'string', 'max:50'];
                $rules["answers.{$key}.relationship"] = ['nullable', 'string', 'max:255'];
            }
        }

        return $rules;
    }

    /**
     * Human-readable attribute names so validation messages read naturally
     * ("The first name field is required" rather than "answers.first_name…").
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach ($this->schemaFields() as $field) {
            $key = $field['key'];
            $label = mb_strtolower((string) ($field['label'] ?? $key));

            $attributes["answers.{$key}"] = $label;

            if (($field['type'] ?? null) === FieldType::Reference->value) {
                $attributes["answers.{$key}.name"] = "{$label} name";
                $attributes["answers.{$key}.email"] = "{$label} email";
                $attributes["answers.{$key}.phone"] = "{$label} phone";
                $attributes["answers.{$key}.relationship"] = "{$label} relationship";
            }
        }

        return $attributes;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'answers.*.accepted' => __('Please tick this box to continue.'),
            'answers.*.file' => __('Please choose a file to upload.'),
            'answers.*.mimes' => __('Use a PDF, image (JPG, PNG, HEIC, WEBP), or Word document.'),
            'answers.*.max' => __('That file is too large — the limit is 10 MB.'),
        ];
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
     * The current enabled field schema for the unit the link belongs to.
     *
     * @return list<array<string, mixed>>
     */
    private function schemaFields(): array
    {
        $link = $this->route('link');

        if (! $link instanceof ApplicationLink) {
            return [];
        }

        return $link->unit->applicationForm?->enabledFields() ?? [];
    }
}
