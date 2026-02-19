<div class="col-md-12"
     x-data
     x-init="$nextTick(() => { $refs.scanInput.focus() })"
     @click="if (!$event.target.closest('select, button, input, textarea, a, [data-no-refocus]')) { $refs.scanInput.focus() }"
>
    <div class="card">
        <div class="card-body">
            <div>Отсканировано товаров: <b>{{ count($scannedItems) }}</b></div>
        </div>
    </div>

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
                <option value="">— выбери новую полку —</option>
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
                >
            </div>
        </div>
    </div>

    @if(!empty($scannedItems))
        <div class="card">
            <div class="card-body">
                <button
                    data-no-refocus
                    onclick="confirm('Сохранить изменения? Все товары будут перемещены на выбранную полку.') || event.stopImmediatePropagation()"
                    wire:click="saveChanges"
                    class="btn btn-success"
                >
                    Сохранить изменения
                </button>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            {{-- таблица отсканированных товаров --}}
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-dark">
                    <tr>
                        <th>Штрихкод</th>
                        <th>Товар</th>
                        <th>Текущая полка</th>
                        <th>Новая полка</th>
                        <th>Действия</th>
                    </tr>
                    </thead>
                    <tbody class="table-hover">
                    @forelse($scannedItems as $item)
                        <tr>
                            <td>{{ $item['storage_barcode'] }}</td>
                            <td>{{ $item['item_title'] }}
                                - {{ $item['item_width'] }}
                                x {{ $item['item_height'] }}</td>
                            <td>{{ $item['current_shelf_title'] ?? 'без полки' }}</td>
                            <td>{{ $shelves->firstWhere('id', $selectedShelfId)?->title }}</td>
                            <td>
                                <button
                                    data-no-refocus
                                    type="button"
                                    class="btn btn-sm btn-outline-danger"
                                    onclick="confirm('Удалить из списка?') || event.stopImmediatePropagation()"
                                    wire:click="removeFromList({{ $item['id'] }})"
                                >
                                    Удалить
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted">Пока ничего не
                                добавлено
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Livewire !== 'undefined') {
                Livewire.hook('morph.updated', ({component}) => {
                    const input = component.el.querySelector('[x-ref="scanInput"]');
                    if (input) {
                        input.focus();
                    }
                });
            }
        });
    </script>
@endpush
