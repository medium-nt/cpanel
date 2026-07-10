<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * JSON-контракт мотивационной доски рейтинга.
 *
 * Данные уже собраны сервисом в нужную структуру — ресурс проксирует их как есть.
 */
class RatingBoardResource extends JsonResource
{
    /**
     * Без обёртки {"data": ...} — фронт доски ждёт ключи (leaders, podium, ...) на верхнем уровне.
     */
    public static $wrap = null;

    /**
     * Возвращает массив данных доски без преобразований.
     *
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return is_array($this->resource) ? $this->resource : [];
    }
}
