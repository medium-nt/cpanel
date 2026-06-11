<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperSetting
 */
class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'value',
        'workshop_id',
    ];

    // Связь с цехом (null = глобальная настройка).
    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public static function getValue(string $name, ?int $workshopId = null): ?string
    {
        // Если указан цех — сначала ищем цеховую настройку
        if ($workshopId !== null) {
            $value = static::query()
                ->where('name', $name)
                ->where('workshop_id', $workshopId)
                ->value('value');

            if ($value !== null) {
                return $value;
            }
        }

        // Fallback на глобальную настройку
        return static::query()
            ->where('name', $name)
            ->whereNull('workshop_id')
            ->value('value');
    }

    public static function getValues(array $names, ?int $workshopId = null): array
    {
        $result = [];

        // Глобальные значения (baseline)
        $globals = static::query()
            ->whereIn('name', $names)
            ->whereNull('workshop_id')
            ->pluck('value', 'name')
            ->toArray();

        // Если указан цех — получаем цеховые переопределения
        if ($workshopId !== null) {
            $overrides = static::query()
                ->whereIn('name', $names)
                ->where('workshop_id', $workshopId)
                ->pluck('value', 'name')
                ->toArray();

            // Цеховые переопределения имеют приоритет над глобальными
            foreach ($names as $name) {
                $result[$name] = $overrides[$name] ?? $globals[$name] ?? null;
            }
        } else {
            $result = $globals;
        }

        return $result;
    }
}
