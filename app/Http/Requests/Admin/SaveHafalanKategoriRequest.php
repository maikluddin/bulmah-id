<?php

namespace App\Http\Requests\Admin;

use App\Enums\HafalanKategoriJenis;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveHafalanKategoriRequest extends FormRequest
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
        $teamId = $this->route('current_team')->id;

        return [
            'jenis' => ['required', Rule::enum(HafalanKategoriJenis::class)],
            'nama' => [
                'required',
                'string',
                'max:255',
                Rule::unique('hafalan_kategoris', 'nama')
                    ->where('team_id', $teamId)
                    ->where('jenis', $this->input('jenis'))
                    ->ignore($this->route('kategori')),
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
            'jenis' => 'jenis kategori',
            'nama' => 'nama kategori',
        ];
    }
}
