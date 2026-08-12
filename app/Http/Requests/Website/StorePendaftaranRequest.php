<?php

namespace App\Http\Requests\Website;

use App\Enums\SantriGender;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePendaftaranRequest extends FormRequest
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
        $teamId = $this->route('lembaga')?->id;

        return [
            'nisn' => ['nullable', 'string', 'max:50', Rule::unique('santris', 'nisn')
                ->where('team_id', $teamId)],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(SantriGender::class)],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'address_province' => ['required', 'string', 'max:255'],
            'address_city' => ['required', 'string', 'max:255'],
            'address_district' => ['required', 'string', 'max:255'],
            'address_village' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'wali_name' => ['required', 'string', 'max:255'],
            'wali_phone' => ['required', 'string', 'max:50'],
        ];
    }
}
