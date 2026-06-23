<div x-data x-on:show-error.window="toastr.error($event.detail.message)">
    @if(empty($supply->draft_id))
        <div class="form-group">
            <label for="supply_type">Тип поставки</label>
            <select wire:model.live="supply_type" class="form-control">
                <option value="">-- Выберите тип --</option>
                <option value="crossdock">Кросс-докинг</option>
                <option value="direct">Прямая поставка</option>
            </select>
            @error('supply_type') <span
                class="text-danger">{{ $message }}</span> @enderror
        </div>

        @if($supply_type)
            <div class="form-group mt-3">
                <label for="warehouse">Кластер OZON</label>
                <select wire:model.live="cluster_id" class="form-control">
                    <option value="">-- Выберите кластер --</option>
                    @foreach($clusters as $cluster)
                        <option value="{{ $cluster->macrolocal_cluster_id }}">
                            {{ $cluster->cluster }}
                        </option>
                    @endforeach
                </select>
                @error('cluster_id') <span
                    class="text-danger">{{ $message }}</span> @enderror
            </div>

            @if($supply_type === 'crossdock')
                <div class="form-group mt-3">
                    <label for="seller_warehouse">Склад продавца (откуда
                        забираем)</label>
                    <select wire:model.live="seller_warehouse_id"
                            class="form-control">
                        <option value="">-- Выберите склад продавца --</option>
                        @foreach($sellerWarehouses as $sellerWarehouse)
                            <option
                                value="{{ $sellerWarehouse['seller_warehouse_id'] }}">
                                {{ $sellerWarehouse['seller_warehouse_name'] }}
                                ({{ $sellerWarehouse['address']['city'] ?? '' }}
                                )
                            </option>
                        @endforeach
                    </select>
                    @error('seller_warehouse_id') <span
                        class="text-danger">{{ $message }}</span> @enderror
                </div>
            @endif

            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Товары для черновика</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text"
                                   wire:model.live.debounce.300ms="search"
                                   class="form-control"
                                   placeholder="Поиск по артикулу или названию...">
                        </div>
                        <div class="col-md-2">
                            <input type="number"
                                   wire:model="quantity"
                                   class="form-control"
                                   min="1"
                                   value="1"
                                   placeholder="Кол-во">
                        </div>
                    </div>

                    @if($results->isNotEmpty())
                        <div class="list-group mt-2"
                             style="max-height: 300px; overflow-y: auto;">
                            @foreach($results as $result)
                                <button type="button"
                                        wire:click="addItem({{ $result->id }})"
                                        class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <span>
                                <b>{{ $result->item->article ?? '—' }}</b>
                                — {{ $result->item->title ?? '—' }}
                                {{ $result->item->width }}x{{ $result->item->height }}
                            </span>
                                    <span
                                        class="badge badge-secondary">SKU: {{ $result->sku }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if($draftItems->isNotEmpty())
                        <table class="table table-bordered table-hover mt-3">
                            <thead class="thead-dark">
                            <tr>
                                <th>Артикул</th>
                                <th>Товар</th>
                                <th>SKU</th>
                                <th style="width: 120px">Кол-во</th>
                                <th style="width: 80px"></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($draftItems as $item)
                                <tr>
                                    <td>{{ $item->skuRecord?->item?->article ?? '—' }}</td>
                                    <td>
                                        {{ $item->skuRecord?->item?->title ?? '—' }}
                                        {{ $item->skuRecord?->item?->width }}
                                        x {{ $item->skuRecord?->item?->height }}
                                    </td>
                                    <td>{{ $item->sku }}</td>
                                    <td>
                                        <input type="number"
                                               value="{{ $item->quantity }}"
                                               min="1"
                                               class="form-control form-control-sm"
                                               wire:change="updateQuantity({{ $item->id }}, $event.target.value)">
                                    </td>
                                    <td>
                                        <button type="button"
                                                wire:click="removeItem({{ $item->id }})"
                                                wire:confirm="Удалить товар?"
                                                class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

            <div class="mt-3">
                <button type="button" wire:click="createDraft"
                        wire:loading.attr="disabled"
                        class="btn btn-primary"
                        onclick="if(!confirm('Создать черновик поставки?')) return false;">
                    <span wire:loading.remove>Создать черновик</span>
                    <span wire:loading><i class="fas fa-spinner fa-spin"></i> Создание...</span>
                </button>
            </div>
        @endif

    @else
        <p class="text-muted">Черновик создан. Выберите склад и таймслот для
            продолжения.</p>

        <div class="mt-3">
            <button type="button" wire:click="loadDraftWarehouses"
                    wire:loading.attr="disabled"
                    class="btn btn-outline-primary">
                <span wire:loading.remove>Загрузить склады</span>
                <span wire:loading><i class="fas fa-spinner fa-spin"></i> Загрузка...</span>
            </button>
        </div>

        @if(!empty($draftWarehouses))
            <div class="form-group mt-3">
                <label>Склад OZON</label>
                <select wire:model.live="selectedWarehouseId"
                        class="form-control">
                    <option value="">-- Выберите склад --</option>
                    @foreach($draftWarehouses as $wh)
                        <option value="{{ $wh['warehouse_id'] }}">
                            {{ $wh['name'] }} — {{ $wh['address'] }}
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        @if($selectedWarehouseId)
            <div class="row mt-3">
                <div class="col-md-2">
                    <label>Дата от</label>
                    <input type="date" wire:model.live="dateFrom"
                           class="form-control"
                           min="{{ now()->addDay()->format('Y-m-d') }}"
                           max="{{ now()->addDays(28)->format('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <label>Дата до</label>
                    <input type="date" wire:model.live="dateTo"
                           class="form-control"
                           min="{{ now()->addDay()->format('Y-m-d') }}"
                           max="{{ now()->addDays(28)->format('Y-m-d') }}">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" wire:click="loadTimeslots"
                            wire:loading.attr="disabled"
                            class="btn btn-outline-primary">
                        <span wire:loading.remove>Загрузить таймслоты</span>
                        <span wire:loading><i
                                class="fas fa-spinner fa-spin"></i> Загрузка...</span>
                    </button>
                </div>
            </div>
        @endif

        @if(!empty($timeslotDays))
            <div class="row mt-3">
                <div class="col-md-2">
                    <label>Дата</label>
                    <select wire:model.live="selectedDate" class="form-control">
                        <option value="">-- Выберите дату --</option>
                        @foreach($timeslotDays as $day)
                            <option value="{{ $day['date_in_timezone'] }}">
                                {{ \Carbon\Carbon::parse($day['date_in_timezone'])->format('d.m.Y') }}
                                ({{ count($day['timeslots']) }} слотов)
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($selectedDate)
                    <div class="col-md-2">
                        <label>Время</label>
                        <select wire:model.live="selectedTimeslot"
                                class="form-control">
                            <option value="">-- Выберите время --</option>
                            @foreach(collect($timeslotDays)->first(fn ($d) => $d['date_in_timezone'] === $selectedDate)['timeslots'] ?? [] as $slot)
                                <option value="{{ $slot['from_in_timezone'] }}">
                                    {{ \Carbon\Carbon::parse($slot['from_in_timezone'])->format('H:i') }}
                                    — {{ \Carbon\Carbon::parse($slot['to_in_timezone'])->format('H:i') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </div>

            @if($selectedTimeslot)
                <div class="mt-3 alert alert-info">
                    Выбранный таймслот:
                    <b>{{ \Carbon\Carbon::parse($selectedDate)->format('d.m.Y') }}</b>,
                    <b>{{ \Carbon\Carbon::parse($selectedTimeslot)->format('H:i') }}
                        —
                        @php
                            $selectedDay = collect($timeslotDays)->first(fn ($d) => $d['date_in_timezone'] === $selectedDate);
                            $selectedSlot = collect($selectedDay['timeslots'] ?? [])->first(fn ($s) => $s['from_in_timezone'] === $selectedTimeslot);
                            echo \Carbon\Carbon::parse($selectedSlot['to_in_timezone'] ?? now())->format('H:i');
                        @endphp
                    </b>
                </div>

                <div class="mt-3">
                    <button type="button" wire:click="submitDraft"
                            wire:loading.attr="disabled"
                            class="btn btn-primary"
                            onclick="if(!confirm('Создать заявку на поставку?')) return false;">
                        <span wire:loading.remove>Создать заявку</span>
                        <span wire:loading><i
                                class="fas fa-spinner fa-spin"></i> Создание...</span>
                    </button>
                </div>
            @endif
        @endif
    @endif
</div>
