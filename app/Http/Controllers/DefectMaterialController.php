<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveDefectMaterialRequest;
use App\Models\Material;
use App\Models\Order;
use App\Models\Supplier;
use App\Services\DefectMaterialService;
use Illuminate\Http\Request;

class DefectMaterialController extends Controller
{
    public function index()
    {
        return view('defect_materials.index', [
            'title' => 'Передача брака на склад',
            'orders' => Order::query()
                ->where('type_movement', 4)
                ->orderBy('created_at', 'desc')
                ->paginate(10)
        ]);
    }

    public function create()
    {
        return view('defect_materials.create', [
            'title' => 'Добавить новый брак',
            'materials' => Material::query()->get(),
            'suppliers' => Supplier::query()->get(),
        ]);
    }

    public function store(SaveDefectMaterialRequest $request)
    {
        if (!DefectMaterialService::store($request)) {
            return redirect()
                ->route('defect_materials.index')
                ->with('error', 'Внутренняя ошибка');
        }

        return redirect()
            ->route('defect_materials.index')
            ->with('success', 'Брак добавлен');
    }

    public function approve_reject(Order $order)
    {
        return view('defect_materials.approve_reject', [
            'title' => 'Одобрение брака',
            'order' => $order,
            'materials' => Material::query()->get(),
        ]);
    }

    public function pick_up(Order $order)
    {
        return view('defect_materials.pick_up', [
            'title' => 'Забор брака на склад',
            'order' => $order,
            'materials' => Material::query()->get(),
        ]);
    }

    public function save(Order $order, Request $request)
    {
        $result = DefectMaterialService::save($request);

        if (!$result) {
            return redirect()
                ->route('defect_materials.index')
                ->with('error', 'Неверное значение');
        }

        $order->update([
            'status' => $request->status,
        ]);

        return redirect()
            ->route('defect_materials.index')
            ->with($result['status'], 'Брак ' . $result['text']);
    }

}
