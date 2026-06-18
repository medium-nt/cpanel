<?php

namespace Database\Seeders;

use App\Models\Sku;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SkuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Обновляет SKU для товаров с реальными штрихкодами
     */
    public function run(): void
    {
        // Получаем ID товаров, у которых нет SKU с непустым значением
        $itemIdsWithoutSku = DB::table('marketplace_items as mi')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('skus as s')
                    ->whereColumn('s.item_id', 'mi.id')
                    ->whereNotNull('s.sku')
                    ->where('s.sku', '!=', '');
            })
            ->limit(10)
            ->pluck('id');

        $marketplaces = [1 => 'Ozon', 2 => 'WB'];

        if ($itemIdsWithoutSku->isEmpty()) {
            $this->command->info('Все товары уже имеют SKU или достигнут лимит.');

            return;
        }

        $updatedCount = 0;

        foreach ($itemIdsWithoutSku as $itemId) {
            // Получаем данные товара для генерации SKU
            $item = DB::table('marketplace_items')->where('id', $itemId)->first();

            foreach ($marketplaces as $marketplaceId => $marketplaceName) {
                // Генерируем SKU и штрихкод
                $sku = $this->generateSku($item, $marketplaceName);
                $barcode = $this->generateBarcode();

                // Проверяем существует ли SKU
                $existingSku = DB::table('skus')
                    ->where('item_id', $itemId)
                    ->where('marketplace_id', $marketplaceId)
                    ->first();

                if ($existingSku) {
                    // Обновляем если пустой
                    if (empty($existingSku->sku) || empty($existingSku->barcode)) {
                        DB::table('skus')
                            ->where('id', $existingSku->id)
                            ->update([
                                'sku' => $sku,
                                'barcode' => $barcode,
                            ]);
                        $updatedCount++;
                    }
                } else {
                    // Создаём новый
                    Sku::query()->create([
                        'item_id' => $itemId,
                        'sku' => $sku,
                        'barcode' => $barcode,
                        'marketplace_id' => $marketplaceId,
                    ]);
                    $updatedCount++;
                }
            }
        }

        $this->command->info("Обновлено SKU: {$updatedCount}");
    }

    /**
     * Генерирует SKU на основе товара и маркетплейса
     */
    private function generateSku($item, string $marketplaceName): string
    {
        $prefix = strtoupper(substr($marketplaceName, 0, 2));
        $width = $item->width;
        $height = $item->height;
        // Используем только латинские символы и цифры для SKU
        $fabricCode = $item->id; // Используем ID вместо названия

        return "{$prefix}-{$fabricCode}-{$width}x{$height}";
    }

    /**
     * Генерирует случайный штрихкод (13 цифр)
     */
    private function generateBarcode(): string
    {
        return (string) rand(1000000000000, 9999999999999);
    }
}
