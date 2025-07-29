<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveDefectMaterialRequest;
use App\Models\Material;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DefectMaterialService;
use App\Services\InventoryService;
use App\Services\OrderService;
use Illuminate\Http\Request;

class DefectMaterialController extends Controller
{
    public function index(Request $request)
    {
        $orders = OrderService::getFiltered($request);
        $paginatedItems = $orders->paginate(10);
        $queryParams = $request->except(['page']);

        return view('defect_materials.index', [
            'title' => 'Передача брака на склад',
            'materials' => InventoryService::materialsQuantityBy('defect_warehouse'),
            'orders' => $paginatedItems->appends($queryParams),
            'seamstresses' => User::query()->where('role_id', '1')
                ->where('name', 'not like', '%Тест%')->get()
        ]);
    }

    public function create(Request $request)
    {
        return view('defect_materials.create', [
            'title' => ($request->type_movement_id == 4) ? 'Добавить брак' : 'Добавить остаток',
            'materials' => Material::query()->get(),
            'suppliers' => Supplier::query()->get(),
        ]);
    }

    public function store(SaveDefectMaterialRequest $request)
    {
        if (! DefectMaterialService::store($request)) {
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
        $result = DefectMaterialService::save($request, $order);

        if (! $result) {
            return redirect()
                ->route('defect_materials.index')
                ->with('error', 'Неверное значение');
        }

        $order->update([
            'status' => $request->status,
        ]);

        return redirect()
            ->route('defect_materials.index')
            ->with($result['status'], 'Брак '.$result['text']);
    }

    public function delete(Order $order)
    {
        $result = DefectMaterialService::delete($order);

        if (! $result['success']) {
            return redirect()
                ->route('defect_materials.index')
                ->with('error', $result['message']);
        }

        return redirect()
            ->route('defect_materials.index')
            ->with('success', $result['message']);
    }
}
