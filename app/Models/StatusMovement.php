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
        4 => 'В работе',
        3 => 'Завершено',
        5 => 'Стикеровка',
    ];

    const BADGE_COLORS = [
        -1 => 'badge-danger',
        0 => 'badge-secondary',
        1 => 'badge-success',
        2 => 'badge-warning',
        4 => 'badge-warning',
        3 => 'badge-primary',
        5 => 'badge-info',
    ];
}
