<?php

namespace App\Http\Requests\Admin;

use App\Enums\HafalanType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveHafalanRequest extends FormRequest
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
            'kategori_id' => [
                'nullable',
                'integer',
                Rule::exists('hafalan_kategoris', 'id')->where('team_id', $this->route('current_team')->id),
            ],
            'type' => ['required', Rule::enum(HafalanType::class)],
            'juz' => ['nullable', 'integer', 'between:1,30'],
            'surat' => ['required', 'string', 'max:255'],
            'ayat_from' => ['nullable', 'integer', 'min:1'],
            'ayat_to' => ['nullable', 'integer', 'min:1', 'gte:ayat_from'],
            'tanggal' => ['required', 'date'],
            'keterangan' => ['nullable', 'string', 'max:1000'],
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
            'kategori_id' => 'kategori hafalan',
            'type' => 'tipe setoran',
            'juz' => 'juz',
            'surat' => 'surat',
            'ayat_from' => 'ayat awal',
            'ayat_to' => 'ayat akhir',
            'tanggal' => 'tanggal setoran',
            'keterangan' => 'keterangan',
        ];
    }
}
