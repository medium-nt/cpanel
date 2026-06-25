<?php

namespace App\Models;

use Database\Factories\ShiftFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * @mixin IdeHelperShift
 */
class Shift extends Model
{
    /** @use HasFactory<ShiftFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'workshop_id',
        'name',
        'status',
    ];

    public function workshop(): BelongsTo
    {
        return $this->belongsTo(Workshop::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shift_user')
            ->withPivot('effective_from')
            ->withTimestamps();
    }

    public function rolls(): HasMany
    {
        return $this->hasMany(Roll::class);
    }

    /** Фильтрует запрос по активным сменам. */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Получить сотрудников, привязанных к смене на сегодня.
     * Исключает пользователей, переведённых в другую смену с более свежей датой.
     */
    /** Возвращает сотрудников, привязанных к смене на сегодня без будущих переводов. */
    public function getCurrentUsers()
    {
        $today = now()->toDateString();

        return $this->users()
            ->wherePivot('effective_from', '<=', $today)
            ->whereNotExists(function ($query) use ($today) {
                $query->select(DB::raw(1))
                    ->from('shift_user as su2')
                    ->whereColumn('su2.user_id', 'shift_user.user_id')
                    ->where('su2.shift_id', '!=', $this->id)
                    ->where('su2.effective_from', '<=', $today)
                    ->where('su2.effective_from', '>', DB::raw('shift_user.effective_from'));
            })
            ->get()
            ->unique('id');
    }

    /**
     * Сотрудники, которые придут в смену в будущем (запланированные переводы).
     */
    public function getIncomingUsers()
    {
        $today = now()->toDateString();

        return $this->users()
            ->wherePivot('effective_from', '>', $today)
            ->get()
            ->unique('id');
    }

    /**
     * Сотрудники, которые уйдут из смены в будущем.
     * Проверяет и текущих, и incoming сотрудников.
     *
     * @return \Illuminate\Support\Collection каждый элемент: { user, effective_from, to_shift }
     */
    public function getOutgoingUsers()
    {
        $today = now()->toDateString();

        // Объединяем текущих и входящих сотрудников
        $allUsers = $this->getCurrentUsers()->merge($this->getIncomingUsers())->unique('id');

        $outgoing = collect();

        foreach ($allUsers as $user) {
            // Ищем самую раннюю будущую запись в ДРУГОЙ смене
            $futurePivot = $user->shifts()
                ->wherePivot('effective_from', '>', $today)
                ->where('shifts.id', '!=', $this->id)
                ->orderByPivot('effective_from', 'asc')
                ->first();

            if ($futurePivot) {
                $outgoing->push((object) [
                    'user' => $user,
                    'effective_from' => $futurePivot->pivot->effective_from,
                    'to_shift' => $futurePivot,
                ]);
            }
        }

        return $outgoing;
    }

    /**
     * Полная история перемещений сотрудников, связанных со сменой.
     * Включает записи из других смен (переводы).
     */
    public function getUsersHistory()
    {
        // Текущие и incoming сотрудники этой смены
        $userIds = $this->getCurrentUsers()
            ->merge($this->getIncomingUsers())
            ->pluck('id')
            ->unique()
            ->toArray();

        if (empty($userIds)) {
            return collect();
        }

        // Получаем ВСЕ записи shift_user для этих сотрудников
        return DB::table('shift_user')
            ->join('users', 'users.id', '=', 'shift_user.user_id')
            ->join('shifts', 'shifts.id', '=', 'shift_user.shift_id')
            ->whereIn('shift_user.user_id', $userIds)
            ->orderBy('shift_user.effective_from', 'desc')
            ->select('shift_user.*', 'users.name as user_name', 'shifts.name as shift_name')
            ->get();
    }
}
