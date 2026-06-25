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

    /** Возвращает уникальные кластеры складов для маркетплейса (OZON — по cluster, WB — по name). */
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

    /** Возвращает опции кластеров всех маркетплейсов в формате «marketplaceId|cluster» → подпись. */
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
