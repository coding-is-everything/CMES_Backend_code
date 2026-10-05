<?php
namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAccountRequest;
use App\Http\Resources\CustomerAccountResource;
use App\Services\AccountService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AccountController extends Controller
{
    /**
     * Account service.
     */
    public function __construct(
        protected AccountService $accountService
    ) {
    }

    /**
     * Get authenticated customer account.
     *
     * GET /api/v1/account
     */
    public function show(
        Request $request
    ): JsonResponse {

        try {

            $customerAccountId =
            $request->attributes->get(
                'customer_account_id'
            );

            if (! $customerAccountId) {

                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'data'    => null,
                ], 401);
            }

            $customer =
            $this->accountService->getAccount(
                (int) $customerAccountId
            );

            return response()->json([
                'success' => true,
                'message' =>
                'Account details retrieved successfully.',
                'data'    =>
                new CustomerAccountResource($customer),
            ], 200);

        } catch (ModelNotFoundException) {

            return response()->json([
                'success' => false,
                'message' =>
                'Customer account not found or inactive.',
                'data'    => null,
            ], 404);

        } catch (Throwable $exception) {

            report($exception);

            return response()->json([
                'success' => false,
                'message' =>
                'Unable to retrieve account details.',
                'data'    => null,
            ], 500);
        }
    }

    /**
     * Update authenticated customer account.
     *
     * PATCH /api/v1/account
     */
    public function update(
        UpdateAccountRequest $request
    ): JsonResponse {

        try {

            /*
            |--------------------------------------------------------------------------
            | Get Customer ID From Authentication Middleware
            |--------------------------------------------------------------------------
            */

            $customerAccountId =
            $request->attributes->get(
                'customer_account_id'
            );

            if (! $customerAccountId) {

                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                    'data'    => null,
                ], 401);
            }

            /*
            |--------------------------------------------------------------------------
            | Get Validated Data
            |--------------------------------------------------------------------------
            */

            $validatedData =
            $request->validated();

            /*
            |--------------------------------------------------------------------------
            | Update Customer Account
            |--------------------------------------------------------------------------
            */

            $customer =
            $this->accountService->updateAccount(
                (int) $customerAccountId,
                $validatedData
            );

            /*
            |--------------------------------------------------------------------------
            | Success Response
            |--------------------------------------------------------------------------
            */

            return response()->json([
                'success' => true,
                'message' =>
                'Account updated successfully.',
                'data'    =>
                new CustomerAccountResource($customer),
            ], 200);

        } catch (ModelNotFoundException) {

            return response()->json([
                'success' => false,
                'message' =>
                'Customer account not found or inactive.',
                'data'    => null,
            ], 404);

        } catch (Throwable $exception) {

            /*
            |--------------------------------------------------------------------------
            | Log Exception
            |--------------------------------------------------------------------------
            */

            report($exception);

            return response()->json([
                'success' => false,
                'message' =>
                'Unable to update account.',
                'data'    => null,
            ], 500);
        }
    }
}
