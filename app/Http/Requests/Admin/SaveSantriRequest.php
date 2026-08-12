<?php

namespace App\Http\Requests\Admin;

use App\Enums\SantriGender;
use App\Enums\SantriStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSantriRequest extends FormRequest
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
        $teamId = $this->user()?->current_team_id;

        $rules = [
            'nis' => ['nullable', 'string', 'max:50', Rule::unique('santris', 'nis')
                ->where('team_id', $teamId)
                ->ignore($this->route('santri'))],
            'nisn' => ['nullable', 'string', 'max:50', Rule::unique('santris', 'nisn')
                ->where('team_id', $teamId)
                ->ignore($this->route('santri'))],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(SantriGender::class)],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(SantriStatus::class)],
            'kelas_id' => [
                'nullable',
                'integer',
                Rule::exists('kelas', 'id')->where('team_id', $teamId),
            ],
            'wali_name' => ['nullable', 'string', 'max:255'],
            'wali_phone' => ['nullable', 'string', 'max:50'],
            'entry_date' => ['nullable', 'date'],
            'exit_date' => ['nullable', 'date'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['sometimes', 'boolean'],
        ];

        if ($this->input('birth_date') !== null) {
            $rules['name'][] = Rule::unique('santris', 'name')
                ->where('team_id', $teamId)
                ->where('birth_date', $this->input('birth_date'))
                ->ignore($this->route('santri'));
        }

        return $rules;
    }
}
