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
        $this->merge([
            'refresh_token' => $this->refresh_token
                ? trim($this->refresh_token)
                : null,

            'device_name'   => $this->device_name
                ? trim($this->device_name)
                : null,
        ]);
    }
}
