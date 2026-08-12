<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveWaliRequest extends FormRequest
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
        $user = $this->route('user');
        $scope = $this->user()->contentScope($team);
        $genderScope = $scope['isAggregate'] ? null : $this->user()->santriGenderFor($team);

        $santriExists = Rule::exists('santris', 'id')
            ->whereIn('team_id', $scope['teamIds'])
            ->when($genderScope !== null, fn ($rule) => $rule->where('gender', $genderScope));

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')
                ->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'password' => $user === null
                ? ['required', 'string', 'min:8']
                : ['nullable', 'string', 'min:8'],
            'santri_ids' => $user === null
                ? ['required', 'array', 'min:1']
                : ['sometimes', 'array'],
            'santri_ids.*' => ['integer', $santriExists],
        ];

        return $rules;
    }
}
