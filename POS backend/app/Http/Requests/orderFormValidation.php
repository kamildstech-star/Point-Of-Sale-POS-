<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class orderFormValidation extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'items' => 'required|array',
            'items.*.school' => 'required|integer|exists:schools,id',
            'items.*.product' => 'required|integer|exists:products,id',
            'items.*.size' => 'required|integer|exists:sizes,id',
            'items.*.price' => 'required|numeric',
            'items.*.quantity' => 'required|numeric|min:0',
        ];
    }
}
