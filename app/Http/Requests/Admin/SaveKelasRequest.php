<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveKelasRequest extends FormRequest
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
            'nama' => [
                'required',
                'string',
                'max:255',
                Rule::unique('kelas', 'nama')
                    ->where('team_id', $team->id)
                    ->ignore($this->route('kelas')),
            ],
            'ustad_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->whereIn('id', $team->members()->pluck('users.id')),
            ],
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
            'nama' => 'nama kelas',
            'ustad_user_id' => 'ustad kelas',
        ];
    }
}
