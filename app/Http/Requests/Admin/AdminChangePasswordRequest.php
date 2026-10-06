<?php
namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdminChangePasswordRequest extends FormRequest
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
     * Passwords are intentionally NOT trimmed: whitespace may be deliberate.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'current_password' => [
                'required',
                'string',
                'max:255',
            ],

            'password'         => [
                'required',
                'string',
                'min:8',
                'max:255',
                'confirmed',
                'different:current_password',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Current password is required.',
            'current_password.string'   => 'Current password must be a string.',
            'current_password.max'      => 'Current password may not exceed 255 characters.',
            'password.required'         => 'New password is required.',
            'password.string'           => 'New password must be a string.',
            'password.min'              => 'New password must be at least 8 characters.',
            'password.max'              => 'New password may not exceed 255 characters.',
            'password.confirmed'        => 'New password confirmation does not match.',
            'password.different'        => 'New password must be different from the current password.',
        ];
    }
}
