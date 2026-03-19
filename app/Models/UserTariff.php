<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserTariff extends Model
{
    public const ROLE_ACTIONS = [
        'storekeeper' => ['Сканировка'],
        'seamstress' => ['Пошив'],
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
}
