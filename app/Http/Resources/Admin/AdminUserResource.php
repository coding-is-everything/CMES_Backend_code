<?php
namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'admin_code'    => $this->admin_code,
            'full_name'     => $this->full_name,
            'email'         => $this->email,
            'mobile_number' => $this->mobile_number,
            'status'        => $this->status,
            'last_login_at' => $this->last_login_at?->toISOString(),
            'roles'         => $this->whenLoaded('roles', function () {
                return $this->roles->map(function ($role) {
                    return [
                        'id'        => $role->id,
                        'role_code' => $role->role_code,
                        'role_name' => $role->role_name,
                    ];
                })->values();
            }),
            'created_at'    => $this->created_at?->toISOString(),
            'updated_at'    => $this->updated_at?->toISOString(),
        ];
    }
}
