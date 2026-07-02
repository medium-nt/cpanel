<?php

namespace App\Http\Controllers;

use App\Http\Requests\SaveCollectMovementMaterialToWorkshopRequest;
use App\Http\Requests\SaveWriteOffMovementMaterialToWorkshopRequest;
use App\Http\Requests\StoreMovementMaterialToWorkshopRequest;
use App\Models\Material;
use App\Models\Order;
use App\Models\Roll;
use App\Models\Setting;
use App\Models\Shift;
use App\Services\MaxService;
use App\Services\MovementMaterialToWorkshopService;
use App\Services\NotificationService;
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
        $workshop = auth()->user()->currentWorkshop();
        $materials = $workshop
            ? $workshop->allowedMaterials()
                ->where('materials.is_active', true)
                ->where('materials.is_archive', false)
                ->orderBy('title')->get()
            : Material::active()->orderBy('title')->get();

        return view('movements_to_workshop.create', [
            'title' => 'Заказ новых материалов на производство',
            'materials' => $materials,
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
        // Сохраняем URL с фильтрами для возврата после завершения сбора
        if ($referer = request()->headers->get('referer')) {
            $parsedUrl = parse_url($referer);
            if (isset($parsedUrl['path']) && str_ends_with($parsedUrl['path'], '/movements_to_workshop')) {
                session()->put('movements_index_url', $referer);
            }
        }

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

        // Проверка: ткань — не более max_fabric_rolls_per_shift рулонов одного материала на смену
        $limit = (int) (Setting::getValue('max_fabric_rolls_per_shift', $order->shift?->workshop_id) ?? 99);

        $fabricRollsInOrder = $order->movementMaterials
            ->filter(fn ($mm) => $mm->roll && $mm->material->type_id === Material::TYPE_FABRIC);

        $fabricGroups = $fabricRollsInOrder->groupBy('material_id');
        foreach ($fabricGroups as $materialId => $group) {
            $inWorkshop = Roll::query()
                ->where('material_id', $materialId)
                ->where('status', Roll::STATUS_IN_WORKSHOP)
                ->where('shift_id', $order->shift_id)
                ->whereNotIn('id', $group->pluck('roll.id'))
                ->count();

            $total = $inWorkshop + $group->count();
            if ($total > $limit) {
                $needToClose = $total - $limit;

                return redirect()
                    ->back()
                    ->with('error', "Превышен лимит рулонов ткани (максимум {$limit} на смену). Закройте ещё {$needToClose} рулонов, чтобы принять поставку.");
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
        MaxService::sendMessage(config('services.max.admin_id'), $text);

        foreach (UserService::getListSeamstressesWorkingToday() as $user) {
            NotificationService::notify($user, $text);
        }

        foreach (UserService::getListStorekeepersWorkingToday() as $user) {
            NotificationService::notify($user, $text);
        }

        return redirect($this->getFilteredIndexUrl())->with('success', 'Поставка принята');
    }

    public function delete(Order $order)
    {
        if ($order->status != 0) {
            return redirect($this->getFilteredIndexUrl())
                ->with('error', 'Невозможно удалить заказ, так как он уже в работе');
        }

        Log::channel('materials')->warning('Удалена поставка на производство', [
            'order_id' => $order->id,
            'deleted_by' => auth()->id(),
        ]);

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
