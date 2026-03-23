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
        if ($type === 'workhouse') {
            return self::materialsQuantityByWorkshopAggregate();
        }

        if ($type === 'warehouse') {
            return self::materialsQuantityByWarehouseAggregate();
        }

        $materials = Material::all();

        $materialsQuantity = [];
        foreach ($materials as $material) {
            $quantity = 0;
            match ($type) {
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

    private static function materialsQuantityByWorkshopAggregate(): array
    {
        $data = Material::query()
            ->leftJoin('movement_materials', 'movement_materials.material_id', '=', 'materials.id')
            ->leftJoin('orders', 'orders.id', '=', 'movement_materials.order_id')
            ->select(
                'materials.id',
                'materials.title',
                'materials.unit',
                DB::raw('
                    SUM(CASE WHEN orders.type_movement = 2 AND orders.status = 3 THEN movement_materials.quantity ELSE 0 END) as in_workshop
                '),
                DB::raw('
                    SUM(CASE WHEN orders.type_movement = 3 AND orders.status = 4 THEN movement_materials.quantity ELSE 0 END) as hold_workshop_to_items
                '),
                DB::raw('
                    SUM(CASE WHEN orders.type_movement = 3 AND orders.status = 3 THEN movement_materials.quantity ELSE 0 END) as out_workshop_to_items
                '),
                DB::raw('
                    SUM(CASE WHEN orders.type_movement = 4 AND orders.status = 0 THEN movement_materials.quantity ELSE 0 END) as hold_to_defect
                '),
                DB::raw('
                    SUM(CASE WHEN orders.type_movement = 4 AND orders.status = 1 THEN movement_materials.quantity ELSE 0 END) as approved_defect
                '),
                DB::raw('
                    SUM(CASE WHEN orders.type_movement = 4 AND orders.status = 3 THEN movement_materials.quantity ELSE 0 END) as out_to_defect
                '),
                DB::raw('
                    SUM(CASE WHEN orders.type_movement = 7 AND orders.status = 0 THEN movement_materials.quantity ELSE 0 END) as hold_to_write_off
                '),
                DB::raw('
                    SUM(CASE WHEN orders.type_movement = 7 AND orders.status = 1 THEN movement_materials.quantity ELSE 0 END) as approved_write_off
                '),
                DB::raw('
                    SUM(CASE WHEN orders.type_movement = 7 AND orders.status = 3 THEN movement_materials.quantity ELSE 0 END) as out_to_write_off
                '),
                DB::raw('
                    SUM(CASE WHEN orders.type_movement = 6 AND orders.status = 3 THEN movement_materials.quantity ELSE 0 END) as write_off
                ')
            )
            ->groupBy('materials.id', 'materials.title', 'materials.unit')
            ->get();

        $result = [];
        foreach ($data as $row) {
            $quantity = $row->in_workshop
                - $row->hold_workshop_to_items
                - $row->out_workshop_to_items
                - $row->hold_to_defect
                - $row->approved_defect
                - $row->out_to_defect
                - $row->hold_to_write_off
                - $row->approved_write_off
                - $row->out_to_write_off
                - $row->write_off;

            $result[] = [
                'material' => $row,
                'quantity' => round($quantity, 2),
            ];
        }

        return $result;
    }

    private static function materialsQuantityByWarehouseAggregate(): array
    {
        // quantity без фильтра даты (как materialInWarehouse)
        $quantityData = Material::query()
            ->leftJoin('movement_materials', 'movement_materials.material_id', '=', 'materials.id')
            ->leftJoin('orders', 'orders.id', '=', 'movement_materials.order_id')
            ->select(
                'materials.id',
                DB::raw('
                    SUM(CASE WHEN orders.type_movement = 1 AND orders.status = 3 THEN movement_materials.quantity ELSE 0 END) -
                    SUM(CASE WHEN orders.type_movement = 2 AND orders.status NOT IN (-1, 0) THEN movement_materials.quantity ELSE 0 END) -
                    SUM(CASE WHEN orders.type_movement = 2 AND orders.status = 0 THEN movement_materials.quantity ELSE 0 END) as total_quantity
                ')
            )
            ->groupBy('materials.id')
            ->pluck('total_quantity', 'id');

        // in_stock, out_stock, hold_out_stock с фильтром даты (как *_inStock методы)
        $detailedData = Material::query()
            ->leftJoin('movement_materials', 'movement_materials.material_id', '=', 'materials.id')
            ->leftJoin('orders', 'orders.id', '=', 'movement_materials.order_id')
            ->select(
                'materials.id',
                'materials.title',
                'materials.unit',
                DB::raw('
                    SUM(CASE WHEN orders.type_movement = 1 AND orders.status = 3 AND orders.created_at > "2025-12-18" THEN movement_materials.quantity ELSE 0 END) as in_stock
                '),
                DB::raw('
                    SUM(CASE WHEN orders.type_movement = 2 AND orders.status NOT IN (-1, 0) AND orders.created_at > "2025-12-18" THEN movement_materials.quantity ELSE 0 END) as out_stock
                '),
                DB::raw('
                    SUM(CASE WHEN orders.type_movement = 2 AND orders.status = 0 AND orders.created_at > "2025-12-18" THEN movement_materials.quantity ELSE 0 END) as hold_out_stock
                ')
            )
            ->groupBy('materials.id', 'materials.title', 'materials.unit')
            ->get();

        $result = [];
        foreach ($detailedData as $row) {
            $result[] = [
                'material' => $row,
                'quantity' => round($quantityData[$row->id] ?? 0, 2),
                'in_stock' => round($row->in_stock, 2),
                'out_stock' => round($row->out_stock, 2),
                'hold_out_stock' => round($row->hold_out_stock, 2),
            ];
        }

        return $result;
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
