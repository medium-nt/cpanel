<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveCollectMovementMaterialToWorkshopRequest;
use App\Http\Requests\SaveWriteOffMovementMaterialToWorkshopRequest;
use App\Http\Requests\StoreMovementMaterialToWorkshopRequest;
use App\Models\Material;
use App\Models\Order;
use App\Models\Roll;
use App\Models\Shift;
use App\Services\MovementMaterialToWorkshopService;
use App\Services\ShiftService;
use App\Services\TgService;
use App\Services\UserService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MovementMaterialToWorkshopController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $paginatedOrders = MovementMaterialToWorkshopService::getOrdersByStatus($request->status, $user)
            ->when(
                ($user->isAdmin() || $user->isStorekeeper()) ? $request->shift_id : null,
                fn ($query, $shiftId) => $query->where('shift_id', $shiftId)
            )
            ->when($request->date_start, fn ($query) => $query->whereDate('created_at', '>=', $request->date_start))
            ->when($request->date_end, fn ($query) => $query->whereDate('created_at', '<=', $request->date_end))
            ->with(['shift', 'movementMaterials.material', 'movementMaterials.roll', 'seamstress', 'cutter', 'user'])
            ->latest()
            ->paginate(10);

        $queryParams = $request->except(['page']);

        session()->put('movements_index_url', $request->fullUrl());

        return view('movements_to_workshop.index', [
            'title' => 'Отгрузка на производство',
            'orders' => $paginatedOrders->appends($queryParams),
            'shifts' => ($user->isAdmin() || $user->isStorekeeper()) ? Shift::active()->get() : collect(),
        ]);
    }

    public function create()
    {
        return view('movements_to_workshop.create', [
            'title' => 'Заказ новых материалов на производство',
            'materials' => Material::query()->get(),
        ]);
    }

    public function store(StoreMovementMaterialToWorkshopRequest $request)
    {
        $result = MovementMaterialToWorkshopService::store($request);

        if ($result instanceof RedirectResponse) {
            return $result;
        }

        if (! $result) {
            return back()->withErrors(['error' => 'Внутренняя ошибка']);
        }

        return redirect($this->getFilteredIndexUrl())
            ->with('success', 'Заказ сформирован и отправлен на склад');
    }

    public function collect(Order $order)
    {
        return view('movements_to_workshop.collect', [
            'title' => 'Сборка поставки',
            'order' => $order,
        ]);
    }

    public function write_off()
    {
        return view('movements_to_workshop.write_off', [
            'title' => 'Сборка списания',
        ]);
    }

    public function save_write_off(SaveWriteOffMovementMaterialToWorkshopRequest $request)
    {
        if (! MovementMaterialToWorkshopService::save_write_off($request)) {
            return back()->withErrors(['error' => 'Внутренняя ошибка']);
        }

        return redirect()
//            ->route('movements_to_workshop.index')
            ->route('inventory.workshop')
            ->with('success', 'Материал списан');
    }

    public function save_collect(SaveCollectMovementMaterialToWorkshopRequest $request, Order $order)
    {
        if (! MovementMaterialToWorkshopService::save_collect($request, $order)) {
            return back()->withErrors(['error' => 'Внутренняя ошибка']);
        }

        return redirect($this->getFilteredIndexUrl())->with('success', 'Отгрузка сформирована');
    }

    public function receive(Order $order)
    {
        return view('movements_to_workshop.receive', [
            'title' => 'Прием поставки',
            'order' => $order,
        ]);
    }

    public function save_receive(Request $request, Order $order)
    {
        $user = auth()->user();

        if (in_array($user->role?->name, ShiftService::SHIFT_ROLES)) {
            $userShift = $user->currentShift();
            if ($userShift && $order->shift_id && $order->shift_id !== $userShift->id) {
                abort(403, 'Этот заказ принадлежит другой смене.');
            }
        }

        // Проверка: упаковочный материал — не более 1 рулона в поставке и в цехе
        $order->load('movementMaterials.roll.material');
        $packagingRollsInOrder = $order->movementMaterials
            ->filter(fn ($mm) => $mm->roll && $mm->material->type_id === Material::TYPE_PACKAGING);

        if ($packagingRollsInOrder->count() > 1) {
            return redirect()
                ->back()
                ->with('error', 'В поставке несколько рулонов упаковочного материала. Допускается только 1 рулон за смену.');
        }

        foreach ($packagingRollsInOrder as $movementMaterial) {
            $alreadyInWorkshop = Roll::query()
                ->where('material_id', $movementMaterial->material_id)
                ->where('status', Roll::STATUS_IN_WORKSHOP)
                ->where('shift_id', $order->shift_id)
                ->where('id', '!=', $movementMaterial->roll->id)
                ->exists();

            if ($alreadyInWorkshop) {
                return redirect()
                    ->back()
                    ->with('error', 'У вашей смены в цехе уже есть рулон с этим материалом! Сначала завершите текущий рулон.');
            }
        }

        $order->update([
            'status' => 3,
            'completed_at' => now(),
        ]);

        foreach ($order->movementMaterials as $movementMaterial) {
            if ($movementMaterial->roll) {
                $movementMaterial->roll->update([
                    'status' => Roll::STATUS_IN_WORKSHOP,
                ]);
            }
        }

        $list = '';
        foreach ($order->movementMaterials as $movementMaterial) {
            $list .= '• '.$movementMaterial->material->title.' '.$movementMaterial->quantity.' '.$movementMaterial->material->unit."\n";
        }

        $text = auth()->user()->name.' приняла поставку в цехе: '."\n".$list;

        Log::channel('tg')
            ->notice('Отправляем сообщение в ТГ админу и работающим швеям и кладовщикам: '.$text);

        TgService::sendMessage(config('telegram.admin_id'), $text);

        foreach (UserService::getListSeamstressesWorkingToday() as $tgId) {
            TgService::sendMessage($tgId, $text);
        }

        foreach (UserService::getListStorekeepersWorkingToday() as $tgId) {
            TgService::sendMessage($tgId, $text);
        }

        return redirect($this->getFilteredIndexUrl())->with('success', 'Поставка принята');
    }

    public function delete(Order $order)
    {
        if ($order->status != 0) {
            return redirect($this->getFilteredIndexUrl())
                ->with('error', 'Невозможно удалить заказ, так как он уже в работе');
        }

        $order->movementMaterials()->delete();

        $order->delete();

        return redirect($this->getFilteredIndexUrl())
            ->with('success', 'Поставка удалена');
    }

    /**
     * Сгенерировать ленту стикеров с названием смены.
     */
    public function printSticker(Order $order)
    {
        $order->load('shift', 'movementMaterials');

        $rollsCount = $order->movementMaterials->whereNotNull('roll_id')->count();

        if ($rollsCount === 0) {
            return back()->with('error', 'Нет рулонов для печати стикера.');
        }

        $pdf = Pdf::loadView('pdf.shift_sticker', [
            'shiftName' => $order->shift?->name ?? 'Без смены',
            'count' => $rollsCount,
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->stream('shift_sticker_order_'.$order->id.'.pdf');
    }

    /**
     * Получить URL индексной страницы с сохранёнными фильтрами из сессии.
     */
    private function getFilteredIndexUrl(): string
    {
        return session('movements_index_url', route('movements_to_workshop.index'));
    }
}
