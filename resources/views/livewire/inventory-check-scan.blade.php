<div class="col-md-12"
     x-data
     @if($inventory->status !== 'closed')
         x-init="$nextTick(() => { $refs.scanInput.focus() })"
     @click="if (!$event.target.closest('select, button, input, textarea, a, [data-no-refocus]')) { $refs.scanInput.focus() }"
    @endif
>
    <div class="card">
        <div class="card-body">
            <div>Всего: <b>{{ $totalItems }}</b></div>
            <div>Найдено: <b>{{ $foundItems }}</b></div>
            <div>Прогресс:
                <b>{{ $totalItems ? round($foundItems/$totalItems*100) : 0 }}
                    %</b></div>

            @if($inventory->status !== 'closed')
                <div class="mt-3">
                    <button
                        onclick="confirm('Вы уверены?') || event.stopImmediatePropagation()"
                        wire:click="closeCheck"
                        class="btn btn-success btn-sm"
                    >
                        Завершить инвентаризацию
                    </button>
                </div>
            @endif
        </div>
    </div>

    @if($inventory->status !== 'closed')
        <div class="card">
            <div class="card-body">
                <select
                    class="border rounded px-3 py-2 w-full"
                    wire:model="selectedShelfId"
                    @change="$nextTick(() => { $refs.scanInput.focus() })"
                    @click.stop
                    @mousedown.stop
                    @focus.stop
                >
                    <option value="">— выбери полку —</option>
                    @foreach($shelves as $shelf)
                        <option
                            value="{{ $shelf->id }}">{{ $shelf->title }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                @if($statusMessage)
                    <div class="alert {{ $statusClass }}" role="alert">
                        {{ $statusMessage }}
                    </div>
                @endif
                <div>
                    <input
                        type="text"
                        id="scan-input"
                        x-ref="scanInput"
                        wire:model.defer="scanCode"
                        wire:keydown.enter.prevent="handleScan"
                        autocomplete="off"
                        class="border rounded px-3 py-2 w-100"
                        placeholder="Введите штрихкод"
                        @if($inventory->status === 'closed') disabled @endif
                    >
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            {{-- таблица найденных товаров --}}
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-dark">
                    <tr>
                        <th>Штрихкод</th>
                        <th>Полка в системе</th>
                        <th>Фактическая полка</th>
                        <th>Время cканирования</th>
                        <th>Действия</th>
                    </tr>
                    </thead>
                    <tbody class="table-hover">
                    @forelse($foundRows as $row)
                        @php
                            $wrong = $row->expected_shelf_id && $row->expected_shelf_id !== $row->founded_shelf_id;
                        @endphp
                        <tr class="{{ $wrong ? 'table-warning' : '' }}">
                            <td>{{ optional($row->marketplaceOrderItem)->storage_barcode }}</td>
                            <td>{{ optional($row->expectedShelf)->title ?? optional($row->expectedShelf)->code }}</td>
                            <td>{{ optional($row->foundedShelf)->title ?? optional($row->foundedShelf)->code }}</td>
                            <td>{{ $row->updated_at?->format('d/m/Y H:i:s') }}</td>
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="confirm('Удалить из найденных?') || event.stopImmediatePropagation()"
                                    wire:click="unmarkFound({{ $row->id }})"
                                    @if($inventory->status === 'closed') disabled @endif
                                >
                                    Удалить
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted">Пока ничего не
                                найдено
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if($inventory->status === 'closed' && $notFoundRows->isNotEmpty())
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Не найденные товары</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead class="table-dark">
                        <tr>
                            <th>Штрихкод</th>
                            <th>Товар</th>
                            <th>Ожидаемая полка</th>
                            <th>Дата возврата</th>
                            <th>Статус</th>
                        </tr>
                        </thead>
                        <tbody class="table-hover">
                        @foreach($notFoundRows as $row)
                            <tr>
                                <td>{{ $row->marketplaceOrderItem->storage_barcode }}</td>
                                <td>
                                    {{ $row->marketplaceOrderItem->item->title }}
                                    {{ $row->marketplaceOrderItem->item->width }}
                                    x{{ $row->marketplaceOrderItem->item->height }}
                                </td>
                                <td>{{ optional($row->expectedShelf)->title }}</td>
                                <td>{{ $row->marketplaceOrderItem->marketplaceOrder->returned_at }}</td>
                                <td class="text-danger">Утерян</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>
