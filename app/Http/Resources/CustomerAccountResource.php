<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerAccountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(
        Request $request
    ): array {

        return [
            'id'               => $this->id,

            'customer_code'    => $this->customer_code,

            'full_name'        => $this->full_name,

            'mobile'           => [
                'country_code' => $this->mobile_country_code,
                'number'       => $this->mobile_number,
                'verified'     => ! is_null(
                    $this->mobile_verified_at
                ),
            ],

            'email'            => [
                'address'  => $this->email,
                'verified' => ! is_null(
                    $this->email_verified_at
                ),
            ],

            'alternate_mobile' => [
                'country_code' =>
                $this->alternate_mobile_country_code,

                'number'       =>
                $this->alternate_mobile_number,
            ],

            'address'          => [
                'line_1'      => $this->address_line_1,
                'line_2'      => $this->address_line_2,
                'city'        => $this->city,
                'district'    => $this->district,
                'state'       => $this->state,
                'postal_code' => $this->postal_code,
            ],

            'profile_photo'    => $this->profile_photo,

            'status'           => $this->status,

            'created_at'       =>
            $this->created_at?->toISOString(),

            'updated_at'       =>
            $this->updated_at?->toISOString(),
        ];
    }
}
