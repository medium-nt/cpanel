<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $status_id
 *
 * @mixin IdeHelperTypeMovement
 */
class TypeMovement extends Model
{
    const TYPES = [
        1 => 'Поступление от поставщика',
        2 => 'Отгрузка на производство',
        3 => 'Списание по заказу',
        4 => 'Брак на производстве',
        5 => 'Возврат брака поставщику',
        6 => 'Списание недостачи с цеха',
        7 => 'Остатки на производстве',
        8 => 'Утилизированные остатки',
        9 => 'Возврат на склад',
    ];

    /** Название типа движения по status_id из справочника TYPES. */
    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->status_id] ?? '';
    }
}
