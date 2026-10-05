<?php
namespace App\Services;

use App\Models\CustomerAccount;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class AccountService
{
    /**
     * Get authenticated customer account.
     *
     * @param int $customerAccountId
     * @return CustomerAccount
     *
     * @throws ModelNotFoundException
     */
    public function getAccount(
        int $customerAccountId
    ): CustomerAccount {

        return CustomerAccount::query()
            ->where('id', $customerAccountId)
            ->where('status', 'ACTIVE')
            ->firstOrFail();
    }

    /**
     * Update authenticated customer account.
     */
    public function updateAccount(
        int $customerAccountId,
        array $data
    ): CustomerAccount {
        return DB::transaction(function () use (
            $customerAccountId,
            $data
        ) {
            $customer = CustomerAccount::query()
                ->where('id', $customerAccountId)
                ->where('status', 'ACTIVE')
                ->lockForUpdate()
                ->firstOrFail();

            $allowedFields = [
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

            $updateData = [];

            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateData[$field] = $data[$field];
                }
            }

            if (! empty($updateData)) {
                $customer->update($updateData);
            }

            $customer->refresh();

            return $customer;
        });
    }
}
