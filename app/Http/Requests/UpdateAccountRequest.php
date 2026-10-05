<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            /*
            |--------------------------------------------------------------------------
            | Basic Profile
            |--------------------------------------------------------------------------
            */

            'full_name'                     => [
                'sometimes',
                'required',
                'string',
                'min:2',
                'max:150',
            ],

            /*
            |--------------------------------------------------------------------------
            | Alternate Mobile
            |--------------------------------------------------------------------------
            */

            'alternate_mobile_country_code' => [
                'sometimes',
                'nullable',
                'string',
                'max:10',
            ],

            'alternate_mobile_number'       => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9]{6,20}$/',
            ],

            /*
            |--------------------------------------------------------------------------
            | Address
            |--------------------------------------------------------------------------
            */

            'address_line_1'                => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'address_line_2'                => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],

            'city'                          => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'district'                      => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'state'                         => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

            'postal_code'                   => [
                'sometimes',
                'nullable',
                'string',
                'max:20',
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'full_name.required'            =>
            'Full name is required.',

            'full_name.min'                 =>
            'Full name must contain at least 2 characters.',

            'full_name.max'                 =>
            'Full name cannot exceed 150 characters.',

            'alternate_mobile_number.regex' =>
            'Alternate mobile number must contain only digits.',

            'alternate_mobile_number.max'   =>
            'Alternate mobile number cannot exceed 20 digits.',

            'address_line_1.max'            =>
            'Address line 1 cannot exceed 255 characters.',

            'address_line_2.max'            =>
            'Address line 2 cannot exceed 255 characters.',

            'city.max'                      =>
            'City cannot exceed 100 characters.',

            'district.max'                  =>
            'District cannot exceed 100 characters.',

            'state.max'                     =>
            'State cannot exceed 100 characters.',

            'postal_code.max'               =>
            'Postal code cannot exceed 20 characters.',
        ];
    }

    /**
     * Prepare input before validation.
     */
    protected function prepareForValidation(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Trim String Values
        |--------------------------------------------------------------------------
        */

        $fields = [
            'full_name',
            'alternate_mobile_country_code',
            'alternate_mobile_number',
            'address_line_1',
            'address_line_2',
            'city',
            'district',
            'state',
            'postal_code',
        ];

        $data = [];

        foreach ($fields as $field) {

            if ($this->has($field)) {

                $value = $this->input($field);

                if (is_string($value)) {
                    $data[$field] = trim($value);
                }
            }
        }

        if (! empty($data)) {
            $this->merge($data);
        }
    }
}
