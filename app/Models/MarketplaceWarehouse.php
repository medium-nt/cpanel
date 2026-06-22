<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceWarehouse extends Model
{
    protected $table = 'marketplace_warehouses';

    protected $fillable = [
        'name',
        'marketplace_id',
        'cluster',
        'warehouse_id',
        'macrolocal_cluster_id',
    ];

    /**
     * Уникальные "кластерные" значения для маркетплейса.
     * OZON (id=1): кластер — поле `cluster` (город-группировка складов).
     * WB (id=2): отдельного cluster нет — кластером служит `name` (склад).
     *
     * @return array<string, string> [значение => значение]
     */
    public static function clustersByMarketplace(int $marketplaceId): array
    {
        $column = $marketplaceId === 1 ? 'cluster' : 'name';

        return static::query()
            ->where('marketplace_id', $marketplaceId)
            ->whereNotNull($column)
            ->where($column, '<>', '')
            ->select($column)
            ->distinct()
            ->orderBy($column)
            ->pluck($column, $column)
            ->toArray();
    }

    /**
     * Список FBO-кластеров для select-настройки приоритета заказов (оба маркетплейса).
     * Возвращает [value => label], где value = "<marketplace_id>|<кластер>",
     * label = "<ИМЯ МП> — <кластер>". OZON — по полю cluster, WB — по полю name.
     *
     * @return array<string, string>
     */
    public static function clusterOptions(): array
    {
        $names = [1 => 'OZON', 2 => 'WB'];
        $options = [];

        foreach ([1, 2] as $marketplaceId) {
            foreach (static::clustersByMarketplace($marketplaceId) as $value => $_) {
                $options["{$marketplaceId}|{$value}"] = ($names[$marketplaceId] ?? "МП{$marketplaceId}").' — '.$value;
            }
        }

        return $options;
    }
}
