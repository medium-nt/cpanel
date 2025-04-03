<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatusMovement extends Model
{
    const STATUSES = [
        -1 => 'Отказано',
        0 => 'Новый',
        1 => 'Одобрено',
        2 => 'Отправлено',
        3 => 'Завершено'
    ];

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status_id];
    }
}
