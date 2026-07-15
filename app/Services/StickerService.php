<?php

namespace App\Services;

class StickerService
{
    /**
     * Название товара, для которого применяется стикер pdf.fbo_sticker (120×75).
     *
     * Выключатель: чтобы отключить спец-стикер, впишите недостижимое значение,
     * которого не может быть в названии товара. Текущее '__DISABLED__' — стикер выключен.
     */
    private const SPECIAL_STICKER_TITLE = '__DISABLED__';

    /**
     * Определяет шаблон PDF-стикера по названию товара и маркетплейсу.
     */
    public static function resolveTemplate(string $itemTitle, int $marketplaceId): string
    {
        if (str_contains(mb_strtoupper($itemTitle), self::SPECIAL_STICKER_TITLE)) {
            return 'pdf.fbo_sticker';
        }

        return ($marketplaceId == 1) ? 'pdf.fbo_ozon_sticker' : 'pdf.fbo_wb_sticker';
    }

    /**
     * Вычисляет размер шрифта для текста кластера в зависимости от шаблона стикера.
     */
    public static function resolveFontSizeCluster(?string $cluster, string $template): int
    {
        $length = mb_strlen($cluster ?? '');

        return match ($template) {
            'pdf.fbo_sticker' => ($length > 25) ? 6 : (($length > 18) ? 10 : 14),
            'pdf.fbo_ozon_sticker' => ($length > 25) ? 7 : (($length > 18) ? 11 : 14),
            'pdf.fbo_wb_sticker' => ($length > 25) ? 4 : (($length > 18) ? 7 : 10),
            default => 10,
        };
    }
}
