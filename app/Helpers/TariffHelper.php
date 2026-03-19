<?php

namespace App\Helpers;

class TariffHelper
{
    /**
     * Извлекает верхнюю границу из диапазона
     *
     * @param  string  $range  Диапазон в формате "0-10"
     * @return int|null Верхняя граница или null
     */
    public static function getRangeLimit(string $range): ?int
    {
        if (! preg_match('/^\d+-(\d+)$/', $range, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }
}
