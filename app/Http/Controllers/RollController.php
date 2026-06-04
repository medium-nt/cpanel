<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Roll;
use App\Models\Shift;
use App\Services\RollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RollController extends Controller
{
    public function index()
    {
        return view('rolls.index', [
            'title' => 'Рулоны',
            'materials' => Material::all(),
            'shifts' => Shift::all(),
            'rolls' => (request('status') === 'unclosed'
                ? RollService::lowMaterialRollsQuery()->with('shift')
                : Roll::query()
                    ->with(['material', 'shift'])
                    ->when(request('status'), function ($query, $status) {
                        return $query->where('status', $status);
                    })
            )
                ->when(request('search'), function ($query, $search) {
                    return $query->where('roll_code', 'like', '%'.$search.'%');
                })
                ->when(request('material'), function ($query, $material) {
                    return $query->where('material_id', $material);
                })
                ->when(request('shift'), function ($query, $shift) {
                    return $shift === 'none'
                        ? $query->whereNull('shift_id')
                        : $query->where('shift_id', $shift);
                })
                ->orderBy('id', 'desc')
                ->paginate(10)
                ->withQueryString(),
        ]);
    }

    public function show(Roll $roll)
    {
        return view('rolls.show', [
            'title' => 'Рулон '.$roll->roll_code,
            'roll' => Roll::query()
                ->with(['material', 'shift', 'completedBy', 'movementMaterialsNotFromSuppler.order.seamstress', 'movementMaterialsNotFromSuppler.order.cutter', 'movementMaterialsNotFromSuppler.order.marketplaceOrder.items.item'])
                ->find($roll->id),
            'canDelete' => $roll->status == 'in_storage',
            'backUrl' => session('return_back_url', url()->previous()),
        ]);
    }

    public function printRoll(Roll $roll)
    {
        $pdf = PDF::loadView('pdf.roll_sticker', [
            'roll' => $roll,
        ]);

        $roll->update([
            'is_printed' => 1,
        ]);

        return $pdf->setPaper('A4')
            ->download('roll_sticker.pdf');
    }

    public function printOrder(Order $order)
    {
        $rolls = $order->movementMaterials->map->roll;

        if ($rolls->filter()->isEmpty()) {
            echo 'Для этой поставки еще не было добавлено рулонов в систему.';
            exit;
        }

        $pdf = PDF::loadView('pdf.all_rolls_sticker', [
            'rolls' => $rolls,
        ]);

        $rollIds = $order->movementMaterials->pluck('roll_id');
        Roll::whereIn('id', $rollIds)->update([
            'is_printed' => 1,
        ]);

        return $pdf->setPaper('A4')
            ->download('roll_sticker.pdf');
    }

    /**
     * Возвращает неиспользованный рулон из цеха обратно на склад.
     */
    public function returnToStorage(Roll $roll)
    {
        if ($roll->status !== Roll::STATUS_IN_WORKSHOP) {
            return redirect()
                ->route('rolls.show', $roll)
                ->with('error', 'Вернуть можно только рулон в цехе');
        }

        $hasUsage = MovementMaterial::query()
            ->join('orders', 'orders.id', '=', 'movement_materials.order_id')
            ->where('movement_materials.roll_id', $roll->id)
            ->whereIn('orders.type_movement', [3, 4])
            ->exists();

        if ($hasUsage) {
            return redirect()
                ->route('rolls.show', $roll)
                ->with('error', 'Рулон уже использовался, возврат невозможен');
        }

        try {
            DB::beginTransaction();

            $order = Order::query()->create([
                'type_movement' => 9,
                'status' => 3,
                'shift_id' => $roll->shift_id,
                'comment' => 'Возврат рулона #'.$roll->roll_code.' на склад',
            ]);

            MovementMaterial::create([
                'material_id' => $roll->material_id,
                'order_id' => $order->id,
                'quantity' => $roll->initial_quantity,
                'roll_id' => $roll->id,
            ]);

            $roll->update([
                'status' => Roll::STATUS_IN_STORAGE,
            ]);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::channel('materials')->error('Ошибка при возврате рулона: '.$e->getMessage());

            return redirect()
                ->route('rolls.show', $roll)
                ->with('error', 'Внутренняя ошибка');
        }

        Log::channel('materials')
            ->notice('Рулон "'.$roll->roll_code.'" возвращен на склад сотрудником '.auth()->user()->name);

        return redirect()
            ->route('rolls.show', $roll)
            ->with('success', 'Рулон возвращен на склад')
            ->with('return_back_url', route('rolls.index'));
    }

    /**
     * Удаляет рулон со всеми связанными записями движения материалов.
     */
    public function destroy(Roll $roll)
    {
        if ($roll->status != 'in_storage') {
            return redirect()
                ->route('rolls.index')
                ->with('error', 'Рулон уже в работе, не может быть удален');
        }

        try {
            DB::beginTransaction();

            $movementMaterials = MovementMaterial::query()
                ->where('roll_id', $roll->id)
                ->get();

            $orderIds = $movementMaterials->pluck('order_id')->unique();

            MovementMaterial::query()
                ->where('roll_id', $roll->id)
                ->delete();

            foreach ($orderIds as $orderId) {
                $hasMoreMaterials = MovementMaterial::query()
                    ->where('order_id', $orderId)
                    ->exists();

                if (! $hasMoreMaterials) {
                    Order::query()->where('id', $orderId)->delete();
                }
            }

            $rollCode = $roll->roll_code;

            $roll->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::channel('materials')->error('Ошибка при удалении рулона: '.$e->getMessage());

            return redirect()
                ->route('rolls.index')
                ->with('error', 'Внутренняя ошибка при удалении рулона');
        }

        Log::channel('materials')
            ->notice('Рулон "'.$rollCode.'" удален сотрудником '.auth()->user()->name);

        return redirect()
            ->route('rolls.index')
            ->with('success', 'Рулон удален');
    }
}
