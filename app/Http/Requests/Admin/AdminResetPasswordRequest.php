<?php
namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdminResetPasswordRequest extends FormRequest
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
            'email'    => [
                'required',
                'string',
                'email',
                'max:150',
            ],

            'token'    => [
                'required',
                'string',
                'max:255',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'max:255',
                'confirmed',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'      => 'Email address is required.',
            'email.string'        => 'Please enter a valid email address.',
            'email.email'         => 'Please enter a valid email address.',
            'email.max'           => 'Email address may not exceed 150 characters.',
            'token.required'      => 'Password reset token is required.',
            'token.string'        => 'Invalid password reset token format.',
            'token.max'           => 'Invalid password reset token format.',
            'password.required'   => 'Password is required.',
            'password.string'     => 'Password must be a string.',
            'password.min'        => 'Password must be at least 8 characters.',
            'password.max'        => 'Password may not exceed 255 characters.',
            'password.confirmed'  => 'Password confirmation does not match.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $email = $this->input('email');
        $token = $this->input('token');

        // The password is intentionally NOT trimmed: whitespace may be deliberate.
        $this->merge([
            'email' => is_string($email)
                ? (strtolower(trim($email)) ?: null)
                : $email,

            'token' => is_string($token)
                ? (trim($token) ?: null)
                : $token,
        ]);
    }
}
