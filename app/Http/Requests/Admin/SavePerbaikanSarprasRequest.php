<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePerbaikanSarprasRequest extends FormRequest
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
        $team = $this->route('current_team');

        return [
            'sarpras_item_id' => [
                'required',
                'integer',
                Rule::exists('sarpras_items', 'id')->where('team_id', $team->id),
            ],
            'deskripsi' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'sarpras_item_id' => 'inventaris',
            'deskripsi' => 'deskripsi kerusakan',
        ];
    }
}
