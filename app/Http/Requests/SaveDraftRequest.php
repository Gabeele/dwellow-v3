<?php

namespace App\Http\Requests;

use App\Models\ApplicationLink;
use Illuminate\Foundation\Http\FormRequest;

class SaveDraftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Like the submit request, we only require a real link; whether it is still
     * open is decided in the controller so a closed link is a quiet no-op.
     */
    public function authorize(): bool
    {
        return $this->route('link') instanceof ApplicationLink;
    }

    /**
     * Draft answers are intentionally lenient — a partial form is allowed to be
     * incomplete or invalid. The submit request is the source of truth; here we
     * only guard the envelope so the payload stays a sane shape.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'answers' => ['present', 'array'],
            'current_step' => ['required', 'integer', 'min:0', 'max:255'],
        ];
    }
}
