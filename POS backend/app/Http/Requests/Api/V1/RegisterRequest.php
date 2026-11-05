<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    protected function prepareForValidation(): void
    {
        if (is_string($this->email)) $this->merge(['email' => mb_strtolower(trim($this->email))]);
    }
    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:255'], 'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:12', 'max:255', 'confirmed'], 'business_name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', 'unique:tenants,slug'],
            'currency_code' => ['required', 'regex:/^[A-Z]{3}$/D'], 'timezone' => ['required', 'timezone']];
    }
}
