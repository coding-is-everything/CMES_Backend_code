<?php
namespace App\Services\Admin;

use App\Models\AdminUser;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminUserService
{
    /**
     * List and search admin users.
     */
    public function listAdmins(array $filters): LengthAwarePaginator
    {
        $perPage = min(
            max((int) ($filters['per_page'] ?? 20), 1),
            100
        );

        $sortBy = $filters['sort_by'] ?? 'created_at';

        $sortOrder = $filters['sort_order'] ?? 'desc';

        $query = AdminUser::query()
            ->select([
                'id',
                'admin_code',
                'full_name',
                'email',
                'mobile_number',
                'status',
                'last_login_at',
                'created_at',
                'updated_at',
            ])
            ->with([
                'roles:id,role_name,role_code',
            ]);

        /**
         * Search.
         */
        if (! empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('admin_code', 'LIKE', "%{$search}%")
                    ->orWhere('full_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('mobile_number', 'LIKE', "%{$search}%");
            });
        }

        /**
         * Status filter.
         */
        if (! empty($filters['status'])) {
            $query->where(
                'status',
                $filters['status']
            );
        }

        /**
         * Role filter.
         */
        if (! empty($filters['role_id'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where(
                    'roles.id',
                    $filters['role_id']
                );
            });
        }

        /**
         * Sorting.
         */
        $query->orderBy(
            $sortBy,
            $sortOrder
        );

        /**
         * Stable secondary sort.
         */
        if ($sortBy !== 'id') {
            $query->orderBy('id', 'desc');
        }

        return $query
            ->paginate(
                perPage: $perPage,
                page: (int) ($filters['page'] ?? 1)
            )
            ->withQueryString();
    }
}
