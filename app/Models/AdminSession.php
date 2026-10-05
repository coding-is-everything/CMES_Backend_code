<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminSession extends Model
{
    protected $table = 'admin_sessions';

    public $timestamps = false;

    protected $fillable = [
        'admin_user_id',
        'session_token_hash',
        'ip_address',
        'user_agent',
        'started_at',
        'last_activity_at',
        'expires_at',
        'revoked_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'started_at'       => 'datetime',
            'last_activity_at' => 'datetime',
            'expires_at'       => 'datetime',
            'revoked_at'       => 'datetime',
        ];
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }
}
