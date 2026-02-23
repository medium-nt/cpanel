<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Order;
use App\Models\StatusMovement;
use App\Models\Supplier;
use App\Models\TypeMovement;
use App\Models\User;

class MaterialMovementController extends Controller
{
    public function index()
    {
        $filters = request()->only([
            'type_movement',
            'status',
            'seamstress_id',
            'cutter_id',
            'material_id',
            'roll_id',
            'supplier_id',
            'date_from',
            'date_to',
        ]);

        $movements = Order::query()
            ->with([
                'movementMaterials.material',
                'movementMaterials.roll',
                'seamstress',
                'cutter',
                'supplier',
                'user',
            ])
            ->whereHas('movementMaterials')
            ->when($filters['type_movement'] ?? null, fn ($q, $v) => $q->where('type_movement', $v))
            ->when(array_key_exists('status', $filters), fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['seamstress_id'] ?? null, fn ($q, $v) => $q->where('seamstress_id', $v))
            ->when($filters['cutter_id'] ?? null, fn ($q, $v) => $q->where('cutter_id', $v))
            ->when($filters['supplier_id'] ?? null, fn ($q, $v) => $q->where('supplier_id', $v))
            ->when($filters['material_id'] ?? null, fn ($q) => $q->whereHas('movementMaterials', fn ($mq) => $mq->where('material_id', $filters['material_id'])))
            ->when($filters['roll_id'] ?? null, fn ($q) => $q->whereHas('movementMaterials', fn ($mq) => $mq->where('roll_id', $filters['roll_id'])))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        $types = TypeMovement::TYPES;
        $statuses = collect(StatusMovement::STATUSES)->only([-1, 0, 1, 2, 4, 3])->all();
        $seamstresses = User::whereHas('role', fn ($q) => $q->where('name', 'seamstress'))->pluck('name', 'id');
        $cutters = User::whereHas('role', fn ($q) => $q->where('name', 'cutter'))->pluck('name', 'id');
        $materials = Material::orderBy('title')->pluck('title', 'id');
        $suppliers = Supplier::orderBy('title')->pluck('title', 'id');

        return view('material_movements.index', compact(
            'movements',
            'filters',
            'types',
            'statuses',
            'seamstresses',
            'cutters',
            'materials',
            'suppliers',
        ));
    }
}
