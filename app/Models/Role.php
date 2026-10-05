<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $table = 'roles';

    public $timestamps = false;

    protected $fillable = [
        'role_name',
        'role_code',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function adminUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            AdminUser::class,
            'admin_user_roles',
            'role_id',
            'admin_user_id'
        );
    }
}
