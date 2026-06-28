<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperTicket
 */
class Ticket extends Model
{
    /** @use HasFactory<\Database\Factories\TicketFactory> */
    use HasFactory;

    public const STATUS_NEW = 'new';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_DELETED = 'deleted';

    /** Статусы тикета (значение => русская подпись). */
    public const STATUSES = [
        self::STATUS_NEW => 'Новый',
        self::STATUS_CLOSED => 'Закрыт',
        self::STATUS_DELETED => 'В корзине',
    ];

    /** CSS-классы бейджей для статусов (по образцу StatusMovement). */
    public const BADGE_COLORS = [
        self::STATUS_NEW => 'badge-danger',
        self::STATUS_CLOSED => 'badge-success',
        self::STATUS_DELETED => 'badge-secondary',
    ];

    protected $fillable = [
        'user_id',
        'description',
        'page_url',
        'screenshot',
        'status',
        'closed_at',
    ];

    /**
     * Приведение типов атрибутов.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
        ];
    }

    /**
     * Автор тикета (включая мягко удалённых сотрудников).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Только тикеты сотрудника.
     */
    public function scopeForUser(Builder $query, User $user): void
    {
        $query->where('user_id', $user->id);
    }

    /**
     * Только новые (открытые) тикеты.
     */
    public function scopeOpened(Builder $query): void
    {
        $query->where('status', self::STATUS_NEW);
    }

    /**
     * Обработанные тикеты: закрытые и удалённые в корзину.
     */
    public function scopeProcessed(Builder $query): void
    {
        $query->whereIn('status', [self::STATUS_CLOSED, self::STATUS_DELETED]);
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isDeleted(): bool
    {
        return $this->status === self::STATUS_DELETED;
    }

    /**
     * Закрыть тикет (статус + дата закрытия).
     */
    public function markClosed(): bool
    {
        $this->status = self::STATUS_CLOSED;
        $this->closed_at = now();

        return $this->save();
    }

    /**
     * Отправить тикет в корзину.
     */
    public function markDeleted(): bool
    {
        $this->status = self::STATUS_DELETED;

        return $this->save();
    }
}
