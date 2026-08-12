<?php

namespace App\Http\Requests\Admin;

use App\Enums\SarprasKategori;
use App\Enums\SarprasKondisi;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSarprasItemRequest extends FormRequest
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
                Rule::unique('sarpras_items', 'nama')
                    ->where('team_id', $team->id)
                    ->ignore($this->route('sarpras_item')),
            ],
            'kategori' => ['required', Rule::enum(SarprasKategori::class)],
            'lokasi' => ['nullable', 'string', 'max:255'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:100000'],
            'kondisi' => ['required', Rule::enum(SarprasKondisi::class)],
            'keterangan' => ['nullable', 'string', 'max:2000'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_foto' => ['sometimes', 'boolean'],
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
            'nama' => 'nama inventaris',
            'kategori' => 'kategori',
            'lokasi' => 'lokasi',
            'jumlah' => 'jumlah',
            'kondisi' => 'kondisi',
            'keterangan' => 'keterangan',
            'foto' => 'foto',
        ];
    }
}
