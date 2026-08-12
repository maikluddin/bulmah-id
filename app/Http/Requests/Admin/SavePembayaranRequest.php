<?php

namespace App\Http\Requests\Admin;

use App\Enums\PembayaranJenis;
use App\Enums\PembayaranMetode;
use App\Enums\PembayaranStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePembayaranRequest extends FormRequest
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
            'jenis' => ['required', Rule::enum(PembayaranJenis::class)],
            'jumlah' => ['required', 'numeric', 'gt:0'],
            'tanggal' => ['required', 'date'],
            'metode' => ['required', Rule::enum(PembayaranMetode::class)],
            'status' => ['required', Rule::enum(PembayaranStatus::class)],
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
            'jenis' => 'jenis pembayaran',
            'jumlah' => 'jumlah',
            'tanggal' => 'tanggal',
            'metode' => 'metode pembayaran',
            'status' => 'status',
            'keterangan' => 'keterangan',
        ];
    }
}
