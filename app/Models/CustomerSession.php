<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerSession extends Model
{
    use HasFactory;

    protected $table = 'customer_sessions';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'customer_account_id',
        'session_token_hash',
        'device_id',
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

    /**
     * Customer account.
     */
    public function customer()
    {
        return $this->belongsTo(
            CustomerAccount::class,
            'customer_account_id'
        );
    }

    /**
     * Check whether session is currently valid.
     */
    public function isValid(): bool
    {
        return $this->status === 'ACTIVE'
        && $this->revoked_at === null
        && $this->expires_at !== null
        && $this->expires_at->isFuture();
    }
}
