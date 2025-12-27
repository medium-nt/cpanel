<?php

namespace App\Services;

use App\Models\InventoryCheck;
use App\Models\InventoryCheckItem;
use App\Models\MarketplaceOrderItem;
use App\Models\Material;
use App\Models\MovementMaterial;
use Illuminate\Support\Facades\DB;
use Log;

class InventoryService
{
    public static function materialInWorkshop($materialId): float
    {
        $inWorkshop = self::countMaterial($materialId, 2, 3);

        //  на товар
        $holdWorkshopToItems = self::countMaterial($materialId, 3, 4);
        $outWorkshopToItems = self::countMaterial($materialId, 3, 3);

        //  брак
        $holdToDefectMaterials = self::countMaterial($materialId, 4, 0);
        $approvedDefectMaterials = self::countMaterial($materialId, 4, 1);
        $outToDefectMaterials = self::countMaterial($materialId, 4, 3);

        //  остатки
        $holdToWriteOffMaterials = self::countMaterial($materialId, 7, 0);
        $approvedWriteOffMaterials = self::countMaterial($materialId, 7, 1);
        $outToWriteOffMaterials = self::countMaterial($materialId, 7, 3);

        $writeOff = self::countMaterial($materialId, 6, 3);

        $result = $inWorkshop - $holdWorkshopToItems - $outWorkshopToItems
            - $holdToDefectMaterials - $approvedDefectMaterials - $outToDefectMaterials
            - $holdToWriteOffMaterials - $approvedWriteOffMaterials - $outToWriteOffMaterials
            - $writeOff;

        return round($result, 2);
    }

    public static function materialInWarehouse($materialId): float
    {
        $inStock = self::countMaterial($materialId, 1, 3);
        $holdOutStockNew = self::countMaterial($materialId, 2, 0);

        $outStock = MovementMaterial::query()
            ->join('orders', 'orders.id', '=', 'movement_materials.order_id')
            ->where('material_id', $materialId)
            ->where('orders.type_movement', 2)
            ->whereNotIn('orders.status', [-1, 0])
            ->sum('quantity');

        $result = $inStock - $outStock - $holdOutStockNew;

        return round($result, 2);
    }

    public static function materialInWarehouse_outStock($materialId): float
    {
        $outStock = MovementMaterial::query()
            ->join('orders', 'orders.id', '=', 'movement_materials.order_id')
            ->where('material_id', $materialId)
            ->where('orders.type_movement', 2)
            ->whereNotIn('orders.status', [-1, 0])
            ->whereDate('orders.created_at', '>', '2025-12-18')
            ->sum('quantity');

        return round($outStock, 2);
    }

    public static function materialInWarehouse_holdOutStockNew($materialId): float
    {
        $holdOutStockNew = self::countMaterialAfterRolls($materialId, 2, 0);

        return round($holdOutStockNew, 2);
    }

    public static function materialInWarehouse_inStock($materialId): float
    {
        $inStock = self::countMaterialAfterRolls($materialId, 1, 3);

        return round($inStock, 2);
    }

    public static function defectMaterialInWarehouse($materialId): float
    {
        $inStock = self::countMaterial($materialId, 4, 3);
        $outStockNew = self::countMaterial($materialId, 5, 3);

        $result = $inStock - $outStockNew;

        return round($result, 2);
    }

    public static function remnantsMaterialInWarehouse($materialId): float
    {
        $inStock = self::countMaterial($materialId, 7, 3);
        $outStockNew = self::countMaterial($materialId, 8, 3);

        $result = $inStock - $outStockNew;

        return round($result, 2);
    }

    public static function countMaterial($materialId, $type, $status): float
    {
        return MovementMaterial::query()
            ->join('orders', 'orders.id', '=', 'movement_materials.order_id')
            ->where('material_id', $materialId)
            ->where('orders.type_movement', $type)
            ->where('orders.status', $status)
            ->sum('quantity');
    }

    public static function countMaterialAfterRolls($materialId, $type, $status): float
    {
        return MovementMaterial::query()
            ->join('orders', 'orders.id', '=', 'movement_materials.order_id')
            ->where('material_id', $materialId)
            ->where('orders.type_movement', $type)
            ->where('orders.status', $status)
            ->whereDate('orders.created_at', '>', '2025-12-18')
            ->sum('quantity');
    }

    public static function materialsQuantityBy(string $type): array
    {
        $materials = Material::all();

        $materialsQuantity = [];
        foreach ($materials as $material) {
            $quantity = 0;
            match ($type) {
                'warehouse' => $quantity = self::materialInWarehouse($material->id),
                'workhouse' => $quantity = self::materialInWorkshop($material->id),
                'defect_warehouse' => $quantity = self::defectMaterialInWarehouse($material->id),
                default => 0,
            };

            $remnantsQuantity = 0;
            if ($type == 'defect_warehouse') {
                $remnantsQuantity = self::remnantsMaterialInWarehouse($material->id);
            }

            $materialsQuantity[] = [
                'material' => $material,
                'in_stock' => self::materialInWarehouse_inStock($material->id),
                'out_stock' => self::materialInWarehouse_outStock($material->id),
                'hold_out_stock' => self::materialInWarehouse_holdOutStockNew($material->id),
                'quantity' => $quantity,
                'remnants' => $remnantsQuantity,
            ];
        }

        return $materialsQuantity;
    }

    public function createInventory($request): bool
    {
        try {
            DB::beginTransaction();

            $inventory = InventoryCheck::create([
                'comment' => $request->comment,
            ]);

            $marketplaceOrderItem = MarketplaceOrderItem::query()
                ->when($request->inventory_shelf !== 'all', function ($query) use ($request) {
                    $query->where('shelf_id', $request->inventory_shelf);
                })
                ->whereIn('status', [11, 13])
                ->get();

            $data = [];
            foreach ($marketplaceOrderItem as $item) {
                $data[] = [
                    'inventory_check_id' => $inventory->id,
                    'marketplace_order_item_id' => $item->id,
                    'expected_shelf_id' => $item->shelf_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (! empty($data)) {
                InventoryCheckItem::insert($data);
            }

            DB::commit();

            Log::channel('erp')
                ->info('Создана инвентаризация ID: '.$inventory->id.'
                по полке: '.$request->inventory_shelf);
        } catch (\Throwable $th) {
            DB::rollBack();

            Log::channel('erp')
                ->error('Ошибка при создании инвентаризации: '.$th->getMessage());

            return false;
        }

        return true;
    }
}
