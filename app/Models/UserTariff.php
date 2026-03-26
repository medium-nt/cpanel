<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserTariff extends Model
{
    // Типы действий (action)
    public const ACTION_SEWING = 'sewing';

    public const ACTION_CUTTING = 'cutting';

    public const ACTION_REPACKING = 'repacking';

    public const ACTION_STICKING = 'sticking';

    public const ACTION_SCANNING = 'scanning';

    public const ACTION_SALARY_DAILY = 'salary_daily';

    public const ROLE_ACTIONS = [
        'storekeeper' => ['Сканировка', 'Стикеровка'],
        'seamstress' => ['Пошив', 'Стикеровка'],
        'cutter' => ['Закрой'],
        'otk' => ['Перепаковка', 'Стикеровка'],
    ];

    public const COMMON_ACTIONS = ['Оклад'];

    protected $fillable = [
        'user_id',
        'action',
        'type',
        'is_bonus',
    ];

    protected function casts(): array
    {
        return [
            'is_bonus' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tariffs(): HasMany
    {
        return $this->hasMany(Tariff::class);
    }

    public static function getActionsForRole(string $role): array
    {
        $roleActions = self::ROLE_ACTIONS[$role] ?? [];

        return array_merge(self::COMMON_ACTIONS, $roleActions);
    }

    /**
     * Получить русское название действия по английскому коду
     */
    public static function getRussianAction(string $action): string
    {
        return match ($action) {
            self::ACTION_SEWING => 'Пошив',
            self::ACTION_CUTTING => 'Закрой',
            self::ACTION_REPACKING => 'Перепаковка',
            self::ACTION_STICKING => 'Стикеровка',
            self::ACTION_SCANNING => 'Сканировка',
            self::ACTION_SALARY_DAILY => 'Оклад',
            default => $action,
        };
    }
}
