<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceItem;
use App\Models\Material;
use App\Models\Setting;
use App\Models\Workshop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class WorkshopController extends Controller
{
    public function index(): View
    {
        // Загружаем цеха с количеством смен и сотрудников
        $workshops = Workshop::withCount('shifts')
            ->with(['shifts' => function ($query) {
                $query->withCount('users');
            }])
            ->get();

        return view('workshops.index', compact('workshops'));
    }

    public function create(): View
    {
        return view('workshops.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|min:2|max:255',
        ]);

        $workshop = Workshop::create([
            'title' => $validated['title'],
            'status' => Workshop::STATUS_ACTIVE,
        ]);

        Log::channel('system')->info('Создан цех', [
            'workshop_id' => $workshop->id,
            'title' => $workshop->title,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('workshops.index')->with('success', 'Цех создан');
    }

    public function edit(Workshop $workshop): View
    {
        // Загружаем смены этого цеха с количеством сотрудников
        $workshop->load(['shifts' => function ($query) {
            $query->withCount('users');
        }]);

        // Группируем товары маркетплейсов по названию материала (без ширин и высот)
        $materialTitles = MarketplaceItem::query()
            ->selectRaw('title, COUNT(*) as items_count')
            ->groupBy('title')
            ->orderBy('title')
            ->get();

        // ID разрешённых товаров в этом цехе
        $allowedItemIds = $workshop->allowedItems()->pluck('marketplace_items.id')->toArray();

        // Определяем какие НАЗВАНИЯ материалов полностью разрешены
        // Название считается разрешённым, если ВСЕ товары с этим названием есть в allowedItemIds
        $allowedTitles = [];
        foreach ($materialTitles as $material) {
            $allIdsForTitle = MarketplaceItem::query()
                ->where('title', $material->title)
                ->pluck('id')
                ->toArray();
            // Разрешён если хотя бы один товар с этим названием разрешён
            if (count(array_intersect($allIdsForTitle, $allowedItemIds)) > 0) {
                $allowedTitles[] = $material->title;
            }
        }

        // Маппинг ключей настроек на русские названия и опции для select-полей
        $settingLabels = self::getSettingLabels();
        $settingOptions = self::getSettingOptions();

        // Глобальные настройки (baseline) и цеховые переопределения
        $globalSettings = Setting::query()
            ->whereNull('workshop_id')
            ->pluck('value', 'name');

        $workshopSettings = Setting::query()
            ->where('workshop_id', $workshop->id)
            ->pluck('value', 'name');

        // Сырьевые материалы (ткани, фурнитура) — доступные для заказа в цехе
        $rawMaterials = Material::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get();

        $allowedMaterialIds = $workshop->allowedMaterials()->pluck('materials.id')->toArray();

        return view('workshops.edit', compact('workshop', 'materialTitles', 'allowedTitles', 'globalSettings', 'workshopSettings', 'settingLabels', 'settingOptions', 'rawMaterials', 'allowedMaterialIds'));
    }

    /**
     * Возвращает маппинг ключей настроек на русские названия.
     */
    private static function getSettingLabels(): array
    {
        return [
            'working_day_start' => 'Начало рабочего дня',
            'working_day_end' => 'Конец рабочего дня',
            'is_enabled_work_schedule' => 'Расписание включено?',
            'is_enabled_work_shift' => 'Функционал смен включен?',
            'late_opened_shift_penalty' => 'Штраф за опоздание',
            'unclosed_shift_penalty' => 'Штраф за не закрытую смену',
            'cancel_order_penalty' => 'Штраф за отмену заказа',
            'api_key_wb' => 'WB api key',
            'seller_id_ozon' => 'OZON seller id',
            'api_key_ozon' => 'OZON api key',
            'max_quantity_orders_to_cutter' => 'Макс. кол-во заказов у закройщика',
            'cutter_daily_limit' => 'Метраж в день у закройщика',
            'max_quantity_orders_to_seamstress' => 'Макс. кол-во заказов у швеи',
            'seamstress_daily_limit' => 'Метраж в день у швеи',
            'orders_priority' => 'Порядок заказов',
            'orders_filter' => 'Фильтр заказов',
            'max_quantity_orders_without_timeout' => 'Макс. кол-во заказов без таймаута',
            'timeout_200' => 'Таймаут на 200',
            'timeout_300' => 'Таймаут на 300',
            'timeout_400' => 'Таймаут на 400',
            'timeout_500' => 'Таймаут на 500',
            'timeout_600' => 'Таймаут на 600',
            'timeout_700' => 'Таймаут на 700',
            'timeout_800' => 'Таймаут на 800',
            'print_qr_cutting' => 'QR-код на листе закройщика',
            'sticking_otk' => 'Стикеровка упаковщиком',
            'sticking_seamstress' => 'Стикеровка швеей',
        ];
    }

    /**
     * Возвращает опции для select-полей настроек.
     * Ключ = имя настройки, значение = массив [value => label].
     * Если настройки нет в этом массиве — она рендерится как input.
     */
    private static function getSettingOptions(): array
    {
        return [
            'is_enabled_work_schedule' => ['1' => 'Да', '0' => 'Нет'],
            'is_enabled_work_shift' => ['1' => 'Да', '0' => 'Нет'],
            'orders_priority' => ['ozon' => 'Сначала OZON', 'wb' => 'Сначала WB', 'by_date' => 'По дате заказа'],
            'orders_filter' => ['all' => 'Все', 'fbo' => 'Только FBO', 'fbs' => 'Только FBS'],
            'print_qr_cutting' => ['1' => 'Включен', '0' => 'Выключен'],
            'sticking_otk' => ['qr' => 'Сканером по QR-коду', 'filter' => 'В ручную по фильтру', 'disabled' => 'Запрещена'],
            'sticking_seamstress' => ['qr' => 'Сканером по QR-коду', 'filter' => 'В ручную по фильтру', 'disabled' => 'Запрещена'],
        ];
    }

    public function update(Request $request, Workshop $workshop): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|min:2|max:255',
            'status' => 'required|in:active,inactive',
            'allowed_materials' => 'nullable|array',
            'allowed_materials.*' => 'string',
            'allowed_raw_materials' => 'nullable|array',
            'allowed_raw_materials.*' => 'exists:materials,id',
            'settings' => 'nullable|array',
        ]);

        // Проверка: нельзя деактивировать цех, у которого есть смены
        if ($validated['status'] === Workshop::STATUS_INACTIVE && $workshop->shifts()->exists()) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Нельзя деактивировать цех, у которого есть смены. Сначала перенесите или удалите смены.');
        }

        $workshop->update([
            'title' => $validated['title'],
            'status' => $validated['status'],
        ]);

        // Синхронизация разрешённых товаров по названиям материалов
        // Получаем все ID товаров для отмеченных названий
        $allowedTitles = $validated['allowed_materials'] ?? [];
        $itemIds = MarketplaceItem::query()
            ->whereIn('title', $allowedTitles)
            ->pluck('id')
            ->toArray();
        $workshop->allowedItems()->sync($itemIds);

        // Синхронизация разрешённых сырьевых материалов (ткани, фурнитура)
        $workshop->allowedMaterials()->sync($validated['allowed_raw_materials'] ?? []);

        // Сохранение цеховых переопределений настроек
        $settings = $validated['settings'] ?? [];
        foreach ($settings as $name => $value) {
            if ($value !== null && $value !== '') {
                // Есть значение → создаём/обновляем цеховую настройку
                Setting::query()->updateOrCreate(
                    ['name' => $name, 'workshop_id' => $workshop->id],
                    ['value' => $value],
                );
            } else {
                // Пустое значение → удаляем цеховую настройку (fallback на глобальную)
                Setting::query()
                    ->where('name', $name)
                    ->where('workshop_id', $workshop->id)
                    ->delete();
            }
        }

        Log::channel('system')->info('Обновлён цех', [
            'workshop_id' => $workshop->id,
            'changed' => collect($workshop->getChanges())->except(['updated_at'])->keys(),
            'allowed_items_count' => count($itemIds),
            'allowed_materials_count' => count($validated['allowed_raw_materials'] ?? []),
            'settings_count' => count($validated['settings'] ?? []),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('workshops.index')->with('success', 'Цех обновлён');
    }

    public function destroy(Workshop $workshop): RedirectResponse
    {
        $shiftsCount = $workshop->shifts()->count();
        if ($shiftsCount > 0) {
            return redirect()->route('workshops.index')
                ->with('error', 'Нельзя деактивировать цех, у которого есть смены. Сначала перенесите или удалите смены.');
        }

        Log::channel('system')->info('Деактивирован цех', [
            'workshop_id' => $workshop->id,
            'title' => $workshop->title,
            'deactivated_by' => auth()->id(),
        ]);

        $workshop->update(['status' => Workshop::STATUS_INACTIVE]);

        return redirect()->route('workshops.index')->with('success', 'Цех деактивирован');
    }
}
