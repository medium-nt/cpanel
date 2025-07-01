<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $status_id
 */


class TypeMovement extends Model
{
    const TYPES = [
        1 => 'Поступление от поставщика',
        2 => 'Отгрузка на производство',
        3 => 'Списание по заказу',
        4 => 'Брак на производстве',
        5 => 'Возврат брака поставщику',
        6 => 'Списание недосдачи с цеха',
        7 => 'Остатки на производстве',
        8 => 'Утилизированные остатки',
    ];

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->status_id] ?? '';
    }
}
