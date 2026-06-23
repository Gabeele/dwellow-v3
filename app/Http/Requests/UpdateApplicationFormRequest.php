<?php

namespace App\Http\Requests;

use App\Enums\FieldType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateApplicationFormRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'fields' => ['required', 'array', 'min:1'],
            'fields.*.key' => ['required', 'string', 'max:255', 'regex:/^[a-z][a-z0-9_]*$/'],
            'fields.*.type' => ['required', Rule::enum(FieldType::class)],
            'fields.*.label' => ['required', 'string', 'max:255'],
            'fields.*.required' => ['required', 'boolean'],
            // Backward compatible: a field with no `enabled` key is treated as enabled.
            'fields.*.enabled' => ['sometimes', 'boolean'],
            'fields.*.help' => ['nullable', 'string', 'max:1000'],
            'fields.*.options' => ['nullable', 'array'],
            'fields.*.options.*' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * Cross-field rules the per-field rules above can't express: field keys must
     * be unique across the schema, and options may only appear on field types
     * that expect them (and must be present when the type requires them).
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $fields = $this->input('fields', []);

                if (! is_array($fields)) {
                    return;
                }

                $seenKeys = [];

                foreach ($fields as $index => $field) {
                    $key = $field['key'] ?? null;

                    if (is_string($key)) {
                        if (isset($seenKeys[$key])) {
                            $validator->errors()->add("fields.{$index}.key", __('Field keys must be unique.'));
                        }

                        $seenKeys[$key] = true;
                    }

                    $type = FieldType::tryFrom($field['type'] ?? '');

                    if ($type === null) {
                        continue;
                    }

                    $options = $field['options'] ?? null;
                    $hasOptions = is_array($options) && $options !== [];

                    if ($type->expectsOptions() && ! $hasOptions) {
                        $validator->errors()->add("fields.{$index}.options", __('This field type requires at least one option.'));
                    }

                    if (! $type->expectsOptions() && $hasOptions) {
                        $validator->errors()->add("fields.{$index}.options", __('This field type does not accept options.'));
                    }
                }
            },
        ];
    }
}
