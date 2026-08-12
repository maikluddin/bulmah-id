<?php

namespace App\Http\Requests\Admin;

use App\Enums\LembagaJenjang;
use App\Enums\LembagaType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveLembagaRequest extends FormRequest
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
            'type' => ['nullable', Rule::enum(LembagaType::class)],
            'jenjang' => ['nullable', Rule::enum(LembagaJenjang::class)],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'yayasan_id' => ['nullable', 'integer', 'exists:yayasans,id'],
        ];
    }
}
