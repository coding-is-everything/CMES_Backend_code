<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

class AdminUser extends Authenticatable
{
    use HasFactory, SoftDeletes;

    protected $table = 'admin_users';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'admin_code',
        'full_name',
        'email',
        'mobile_number',
        'password_hash',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
        'deleted_at'    => 'datetime',
    ];

    /**
     * Admin roles.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'admin_user_roles',
            'admin_user_id',
            'role_id'
        );
    }

    /**
     * Check whether admin account is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'ACTIVE' && $this->deleted_at === null;
    }

    /**
     * Check whether admin account is locked.
     */
    public function isLocked(): bool
    {
        return $this->status === 'LOCKED';
    }

    /**
     * Check whether admin account is inactive.
     */
    public function isInactive(): bool
    {
        return $this->status === 'INACTIVE';
    }
}
