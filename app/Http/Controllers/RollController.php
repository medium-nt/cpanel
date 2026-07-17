<?php

namespace App\Http\Controllers;

use App\Http\Requests\RollWriteOffRequest;
use App\Models\Material;
use App\Models\MovementMaterial;
use App\Models\Order;
use App\Models\Roll;
use App\Models\Shift;
use App\Services\RollService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
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
            'shifts' => Shift::active()->get(),
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
        // После POST-редиректа списания/закрытия url()->previous() указывает на
        // write-off/complete endpoint — пропускаем его, чтобы кнопка «Назад» не вела на 405.
        $previous = url()->previous();
        if ($previous && (str_contains($previous, '/rolls/write-off/') || str_contains($previous, '/rolls/complete/'))) {
            $previous = route('rolls.index');
        }

        return view('rolls.show', [
            'title' => 'Рулон '.$roll->roll_code,
            'roll' => Roll::query()
                ->with(['material', 'shift', 'completedBy', 'movementMaterialsNotFromSuppler.order.seamstress', 'movementMaterialsNotFromSuppler.order.cutter', 'movementMaterialsNotFromSuppler.order.user', 'movementMaterialsNotFromSuppler.order.marketplaceOrder.items.item'])
                ->find($roll->id),
            'canDelete' => $roll->status == 'in_storage',
            'backUrl' => session('return_back_url', $previous),
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
            ->whereIn('orders.type_movement', [3, 4, 10])
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
     * Ручное списание метража рулона администратором.
     *
     * Создает order с type_movement=10 и movement_material, уменьшая остаток рулона.
     */
    public function writeOff(RollWriteOffRequest $request, Roll $roll)
    {
        $validated = $request->validated();

        if ($roll->status !== Roll::STATUS_IN_WORKSHOP) {
            return redirect()
                ->route('rolls.show', $roll)
                ->with('error', 'Списание доступно только для рулона в цехе');
        }

        try {
            DB::beginTransaction();

            // Блокируем рулон от параллельного списания и перепроверяем остаток
            // актуальным значением внутри транзакции (защита от race condition).
            $lockedRoll = Roll::lockForUpdate()->find($roll->id);

            if ((float) $validated['quantity'] > (float) $lockedRoll->current_quantity) {
                DB::rollBack();

                return redirect()
                    ->route('rolls.show', $roll)
                    ->with('error', "Количество превышает остаток рулона ({$lockedRoll->current_quantity}).")
                    ->with('return_back_url', $validated['back_url'] ?? route('rolls.index'));
            }

            $order = Order::query()->create([
                'type_movement' => 10,
                'status' => 3,
                'shift_id' => $roll->shift_id,
                'comment' => $validated['comment'] ?? null,
                'storekeeper_id' => auth()->id(),
            ]);

            MovementMaterial::create([
                'material_id' => $roll->material_id,
                'order_id' => $order->id,
                'quantity' => $validated['quantity'],
                'roll_id' => $roll->id,
            ]);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::channel('materials')->error('Ошибка при ручном списании рулона: '.$e->getMessage());

            return redirect()
                ->route('rolls.show', $roll)
                ->with('error', 'Внутренняя ошибка при списании')
                ->with('return_back_url', $validated['back_url'] ?? route('rolls.index'));
        }

        Log::channel('materials')
            ->notice('С рулона "'.$roll->roll_code.'" списано '.$validated['quantity'].' администратором '.auth()->user()->name);

        return redirect()
            ->route('rolls.show', $roll)
            ->with('success', 'Метраж списан')
            ->with('return_back_url', $validated['back_url'] ?? route('rolls.index'));
    }

    /**
     * Закрывает рулон: переводит в STATUS_COMPLETED с фиксацией фактического остатка
     * и расчётом недостачи относительно системного остатка.
     */
    public function complete(Request $request, Roll $roll)
    {
        $validated = $request->validate([
            'actual_remaining' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // Блокируем рулон от параллельного закрытия и перепроверяем статус
            // актуальным значением внутри транзакции (защита от race condition).
            $lockedRoll = Roll::lockForUpdate()->find($roll->id);

            if ($lockedRoll->status !== Roll::STATUS_IN_WORKSHOP) {
                DB::rollBack();

                return redirect()
                    ->route('rolls.show', $roll)
                    ->with('error', 'Рулон нельзя завершить. Статус: '.$lockedRoll->status_name);
            }

            $shortage = round($lockedRoll->current_quantity - $validated['actual_remaining'], 2);

            $lockedRoll->update([
                'status' => Roll::STATUS_COMPLETED,
                'completed_at' => now(),
                'completed_by' => auth()->id(),
                'shortage_quantity' => $shortage,
            ]);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            Log::channel('materials')->error('Ошибка при закрытии рулона: '.$e->getMessage());

            return redirect()
                ->route('rolls.show', $roll)
                ->with('error', 'Внутренняя ошибка при закрытии рулона');
        }

        Log::channel('materials')
            ->notice('Рулон "'.$roll->roll_code.'" завершен сотрудником '.auth()->user()->name
                .'. Недостача: '.$shortage.' '.$roll->material->unit);

        return redirect()
            ->route('rolls.show', $roll)
            ->with('success', 'Рулон '.$roll->roll_code.' успешно закрыт. Недостача: '.$shortage.' '.$roll->material->unit);
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
