<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Services\MarketplaceOrderItemService;
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
use Illuminate\Support\Facades\Log;

/**
 * @mixin IdeHelperUser
 */
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
        'hanger_id',
        'is_cutter',
        'avatar',
        'tg_id',
        'max_id',
        'orders_priority',
        'shift_is_open',
        'start_work_shift',
        'duration_work_shift',
        'max_late_minutes',
        'is_show_finance',
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

    /** Текущая вешалка закройщика (выбирается на странице товаров для пошива). */
    public function hanger(): BelongsTo
    {
        return $this->belongsTo(Hanger::class);
    }

    public function marketplaceOrderItems(): HasMany
    {
        return $this->hasMany(MarketplaceOrderItem::class, 'seamstress_id');
    }

    public function marketplaceOrderItemsByCutter(): HasMany
    {
        return $this->hasMany(MarketplaceOrderItem::class, 'cutter_id');
    }

    public function marketplaceOrderItemsByOtk(): HasMany
    {
        return $this->hasMany(MarketplaceOrderItem::class, 'otk_id');
    }

    /** URL страницы профиля пользователя в AdminLTE. */
    public function adminlte_profile_url(): string
    {
        return '/megatulle/profile';
    }

    /** URL аватара пользователя для AdminLTE user-menu (заглушка, если аватар не задан). */
    public function adminlte_image(): string
    {
        return $this->avatar
            ? asset('storage/'.$this->avatar)
            : asset('img/default-avatar.svg');
    }

    /** Описание пользователя для AdminLTE (переведённое название роли текущего юзера). */
    public function adminlte_desc(): string
    {
        $user = auth()->user();

        if (! $user || ! $user->role) {
            return 'Пользователь';
        }

        return UserService::translateRoleName($user->role->name);
    }

    /** Дата обновления в формате d/m/Y H:i. */
    public function getUpdatedDateAttribute(): string
    {
        return $this->updated_at->format('d/m/Y H:i');
    }

    /** Дата создания в формате d/m/Y H:i. */
    public function getCreatedDateAttribute(): string
    {
        return $this->created_at->format('d/m/Y H:i');
    }

    /** Проверяет, является ли пользователь администратором. */
    public function isAdmin(): bool
    {
        return $this->role?->name === 'admin';
    }

    /** Проверяет, является ли пользователь закройщиком. */
    public function isCutter(): bool
    {
        return $this->role?->name === 'cutter';
    }

    /** Проверяет, является ли пользователь швеёй. */
    public function isSeamstress(): bool
    {
        return $this->role?->name === 'seamstress';
    }

    /** Проверяет, может ли швея выполнять крой (роль seamstress + флаг is_cutter). */
    public function canSeamstressCut(): bool
    {
        return $this->role?->name === 'seamstress' && $this->is_cutter;
    }

    /** Проверяет, что швея НЕ имеет права кроя. */
    public function seamstressNotCut(): bool
    {
        return $this->role?->name === 'seamstress' && ! $this->is_cutter;
    }

    /** Проверяет, является ли пользователь кладовщиком. */
    public function isStorekeeper(): bool
    {
        return $this->role?->name === 'storekeeper';
    }

    /** Проверяет, является ли пользователь сотрудником ОТК. */
    public function isOtk(): bool
    {
        return $this->role?->name === 'otk';
    }

    /** Проверяет, является ли пользователь водителем. */
    public function isDriver(): bool
    {
        return $this->role?->name === 'driver';
    }

    /** Проверяет, является ли пользователь менеджером маркетплейса. */
    public function isManager(): bool
    {
        return $this->role?->name === 'manager';
    }

    /** Проверяет, имеет ли пользователь роль «уборщица». */
    public function isCleaner(): bool
    {
        return $this->role?->name === 'cleaner';
    }

    /** Время окончания рабочей смены (фактическое начало + длительность). */
    public function getEndWorkShiftAttribute(): Carbon
    {
        $interval = CarbonInterval::createFromFormat('H:i:s', $this->duration_work_shift);

        return Carbon::parse($this->actual_start_work_shift)->add($interval);
    }

    public function materials(): BelongsToMany
    {
        return $this->belongsToMany(Material::class);
    }

    public function shifts(): BelongsToMany
    {
        return $this->belongsToMany(Shift::class, 'shift_user')
            ->withPivot('effective_from')
            ->withTimestamps();
    }

    /** Текущий цех пользователя (через текущую смену). */
    public function currentWorkshop(): ?Workshop
    {
        return $this->currentShift()?->workshop;
    }

    /** Текущая (действующая на сегодня) смена пользователя. */
    public function currentShift(): ?Shift
    {
        return $this->shifts()
            ->wherePivot('effective_from', '<=', Carbon::today()->toDateString())
            ->orderByPivot('effective_from', 'desc')
            ->first();
    }

    public function userTariffs(): HasMany
    {
        return $this->hasMany(UserTariff::class);
    }

    /** Краткое ФИО вида «Фамилия И.О.». */
    public function getShortNameAttribute(): string
    {
        $parts = explode(' ', $this->name);

        if (count($parts) < 2) {
            return $this->name;
        }

        $lastName = $parts[0];
        $firstInitial = mb_substr($parts[1], 0, 1);
        $middleInitial = isset($parts[2]) ? mb_substr($parts[2], 0, 1) : '';

        return $lastName.' '.$firstInitial.'.'.($middleInitial ? $middleInitial.'.' : '');
    }

    /** Проверяет, достигнут ли дневной лимит метража для роли (швея/закройщик) в текущем цехе. */
    public function dailyLimitReached(): bool
    {
        $user = $this;

        $meters = MarketplaceOrderItemService::getMetersTodayByUser($user) / 100;

        $currentWorkshopId = $user->currentWorkshop()?->id;

        $dailyLimit = match ($user->role->name) {
            'seamstress' => Setting::getValue('seamstress_daily_limit', $currentWorkshopId),
            'cutter' => Setting::getValue('cutter_daily_limit', $currentWorkshopId),
            default => 0,
        };

        Log::channel('work_shift')
            ->info('Пользователь '.$user->name.' (id: '.$user->id.') смотрел свои смену. Метраж (готовый и в работе): '.
                $meters.', при лимите в '.$dailyLimit);

        if ($meters >= $dailyLimit) {
            return true;
        }

        return false;
    }
}
