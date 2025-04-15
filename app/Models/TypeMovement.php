<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeMovement extends Model
{
    const TYPES = [
        1 => 'Поступление от поставщика',
        2 => 'Отгрузка на производство',
        3 => 'Списание по заказу',
        4 => 'Брак на производстве',
        5 => 'Списание брака',
        6 => 'Списание недосдачи с цеха'
    ];

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->status_id];
    }
}
