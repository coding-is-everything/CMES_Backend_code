<?php
namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdminRegisterRequest extends FormRequest
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
     * Email uniqueness is deliberately NOT validated here: it is checked in
     * the service only after the "registration open" gate, so a closed
     * endpoint can never be used to probe which emails are registered.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name'     => [
                'required',
                'string',
                'min:2',
                'max:150',
            ],

            'email'         => [
                'required',
                'string',
                'email',
                'max:150',
            ],

            'mobile_number' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^[0-9]{6,20}$/',
            ],

            'password'      => [
                'required',
                'string',
                'min:8',
                'max:255',
                'confirmed',
            ],

            'setup_key'     => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required'     => 'Full name is required.',
            'full_name.string'       => 'Full name must be a string.',
            'full_name.min'          => 'Full name must be at least 2 characters.',
            'full_name.max'          => 'Full name may not exceed 150 characters.',
            'email.required'         => 'Email address is required.',
            'email.string'           => 'Please enter a valid email address.',
            'email.email'            => 'Please enter a valid email address.',
            'email.max'              => 'Email address may not exceed 150 characters.',
            'mobile_number.string'   => 'Mobile number must contain only digits.',
            'mobile_number.regex'    => 'Mobile number must contain only digits (6 to 20).',
            'password.required'      => 'Password is required.',
            'password.string'        => 'Password must be a string.',
            'password.min'           => 'Password must be at least 8 characters.',
            'password.max'           => 'Password may not exceed 255 characters.',
            'password.confirmed'     => 'Password confirmation does not match.',
            'setup_key.string'       => 'Invalid setup key format.',
            'setup_key.max'          => 'Invalid setup key format.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $fullName = $this->input('full_name');
        $email    = $this->input('email');
        $mobile   = $this->input('mobile_number');

        // The password is intentionally NOT trimmed: whitespace may be deliberate.
        $this->merge([
            'full_name'     => is_string($fullName)
                ? (trim($fullName) ?: null)
                : $fullName,

            'email'         => is_string($email)
                ? (strtolower(trim($email)) ?: null)
                : $email,

            'mobile_number' => is_string($mobile)
                ? (trim($mobile) ?: null)
                : $mobile,
        ]);
    }
}
