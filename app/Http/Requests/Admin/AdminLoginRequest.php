<?php
namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdminLoginRequest extends FormRequest
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
            'email'       => [
                'required',
                'email',
                'max:150',
            ],

            'password'    => [
                'required',
                'string',
                'min:8',
                'max:255',
            ],

            'device_name' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => 'Email address is required.',
            'email.email'       => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
            'password.min'      => 'Password must be at least 8 characters.',
            'device_name.max'   => 'Device name may not exceed 100 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email'       => $this->email
                ? strtolower(trim($this->email))
                : null,

            'device_name' => $this->device_name
                ? trim($this->device_name)
                : null,
        ]);
    }
}
