<?php

namespace App\Http\Requests;

use App\Models\ApplicationLink;
use Illuminate\Foundation\Http\FormRequest;

class StoreDraftFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->route('link') instanceof ApplicationLink;
    }

    /**
     * A single uploaded file, validated identically to a final submission so a
     * file saved to a draft can never be rejected later at submit time.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,heic,webp,doc,docx', 'max:10240'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => __('Please choose a file to upload.'),
            'file.file' => __('Please choose a file to upload.'),
            'file.mimes' => __('Use a PDF, image (JPG, PNG, HEIC, WEBP), or Word document.'),
            'file.max' => __('That file is too large — the limit is 10 MB.'),
        ];
    }
}
