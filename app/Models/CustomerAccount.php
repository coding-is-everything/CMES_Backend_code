<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerAccount extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Database table
     */
    protected $table = 'customer_accounts';

    /**
     * Primary key.
     */
    protected $primaryKey = 'id';

    /**
     * Primary key is auto incrementing.
     */
    public $incrementing = true;

    /**
     * Primary key type.
     */
    protected $keyType = 'int';

    /**
     * Database has created_at and updated_at.
     */
    public $timestamps = true;

    /**
     * Fields that can be mass assigned.
     */
    protected $fillable = [
        'customer_code',
        'full_name',
        'mobile_country_code',
        'mobile_number',
        'email',
        'alternate_mobile_country_code',
        'alternate_mobile_number',
        'address_line_1',
        'address_line_2',
        'city',
        'district',
        'state',
        'postal_code',
        'profile_photo',
        'status',
        'email_verified_at',
        'mobile_verified_at',
        'last_login_at',
    ];

    /**
     * Attribute casting.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'mobile_verified_at' => 'datetime',
            'last_login_at'      => 'datetime',
            'created_at'         => 'datetime',
            'updated_at'         => 'datetime',
            'deleted_at'         => 'datetime',
        ];
    }

    /**
     * Check whether mobile number is verified.
     */
    public function isMobileVerified(): bool
    {
        return $this->mobile_verified_at !== null;
    }

    /**
     * Check whether email address is verified.
     */
    public function isEmailVerified(): bool
    {
        return $this->email_verified_at !== null;
    }

    /**
     * Check whether account is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'ACTIVE';
    }

    /**
     * Customer sessions.
     */
    public function sessions()
    {
        return $this->hasMany(
            CustomerSession::class,
            'customer_account_id'
        );
    }
}
