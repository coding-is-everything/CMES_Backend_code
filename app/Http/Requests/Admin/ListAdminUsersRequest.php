<?php
namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAdminUsersRequest extends FormRequest
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
            'page'       => [
                'sometimes',
                'integer',
                'min:1',
            ],

            'per_page'   => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],

            'search'     => [
                'sometimes',
                'nullable',
                'string',
                'max:150',
            ],

            'status'     => [
                'sometimes',
                'nullable',
                Rule::in([
                    'ACTIVE',
                    'INACTIVE',
                    'LOCKED',
                ]),
            ],

            'role_id'    => [
                'sometimes',
                'nullable',
                'integer',
                'exists:roles,id',
            ],

            'sort_by'    => [
                'sometimes',
                Rule::in([
                    'id',
                    'admin_code',
                    'full_name',
                    'email',
                    'status',
                    'last_login_at',
                    'created_at',
                    'updated_at',
                ]),
            ],

            'sort_order' => [
                'sometimes',
                Rule::in([
                    'asc',
                    'desc',
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.max'   => 'The maximum number of records per page is 100.',
            'status.in'      => 'Invalid admin status.',
            'role_id.exists' => 'The selected role does not exists.',
            'sort_by.in'     => 'Invalid sorting field.',
            'sort_order.in'  => 'Sort order must be either asc or desc.',
        ];
    }
}
