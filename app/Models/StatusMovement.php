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

        7 => 'На раскрое',
        8 => 'Раскроено',

        4 => 'В работе',
        5 => 'Стикеровка',
        6 => 'На поставку',
        3 => 'Завершено',

        9 => 'Возврат с маркетплейса',
        10 => 'На разборе',
        11 => 'На хранении',
        12 => 'На проверке',
        13 => 'На сборке',
    ];

    const BADGE_COLORS = [
        -1 => 'badge-danger',
        0 => 'badge-secondary',
        1 => 'badge-success',
        2 => 'badge-warning',
        3 => 'badge-primary',
        4 => 'badge-warning',
        5 => 'badge-info',
        6 => 'badge-info',
        7 => 'badge-info',
        8 => 'badge-success',
        9 => 'badge-info',
        10 => 'badge-warning',
        11 => 'badge-primary',
        12 => 'badge-danger',
        13 => 'badge-info',
    ];
}
