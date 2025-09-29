<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Services\UserService;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role_id',
        'is_cutter',
        'salary_rate',
        'avatar',
        'tg_id',
        'orders_priority',
        'shift_is_open',
        'start_work_shift',
        'duration_work_shift',
        'max_late_minutes',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function marketplaceOrderItems(): HasMany
    {
        return $this->hasMany(MarketplaceOrderItem::class, 'seamstress_id');
    }

    public function marketplaceOrderItemsByCutter(): HasMany
    {
        return $this->hasMany(MarketplaceOrderItem::class, 'cutter_id');
    }

    public function adminlte_profile_url(): string
    {
        return '/megatulle/profile';
    }

    public function adminlte_desc(): string
    {
        return UserService::translateRoleName(auth()->user()->role->name);
    }

    public function getUpdatedDateAttribute()
    {
        return $this->updated_at->format('d/m/Y H:i');
    }

    public function getCreatedDateAttribute()
    {
        return $this->updated_at->format('d/m/Y H:i');
    }

    public function isAdmin(): bool
    {
        return $this->role?->name === 'admin';
    }

    public function isCutter(): bool
    {
        return $this->role?->name === 'cutter';
    }

    public function isSeamstress(): bool
    {
        return $this->role?->name === 'seamstress';
    }

    public function isStorekeeper(): bool
    {
        return $this->role?->name === 'storekeeper';
    }

    public function getEndWorkShiftAttribute(): Carbon
    {
        $interval = CarbonInterval::createFromFormat('H:i:s', $this->duration_work_shift);

        return Carbon::parse($this->actual_start_work_shift)->add($interval);
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class);
    }
}
