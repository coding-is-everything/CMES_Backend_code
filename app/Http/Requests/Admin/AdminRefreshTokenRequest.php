<?php
namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdminRefreshTokenRequest extends FormRequest
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
            'refresh_token' => [
                'required',
                'string',
                'max:500',
            ],
            'device_name'   => [
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
            'refresh_token.required' => 'Refresh token is required.',
            'refresh_token.string'   => 'Invalid refresh token format.',
            'device_name.max'        => 'Device name may not exceed 100 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $refreshToken = $this->input('refresh_token');
        $deviceName   = $this->input('device_name');

        $this->merge([
            'refresh_token' => is_string($refreshToken)
                ? (trim($refreshToken) ?: null)
                : $refreshToken,

            'device_name'   => is_string($deviceName)
                ? (trim($deviceName) ?: null)
                : $deviceName,
        ]);
    }
}
