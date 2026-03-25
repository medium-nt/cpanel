<div class="col-md-12"
     x-data
     x-init="$nextTick(() => { $refs.scanInput.focus() })"
     @click="if (!$event.target.closest('select, button, input, textarea, a, [data-no-refocus]')) { $refs.scanInput.focus() }"
>
    {{-- Статистика --}}
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>Отсканировано товаров: <b
                        class="text-success">{{ $this->scannedCount }}</b></div>
                <div>Осталось: <b
                        class="text-secondary">{{ $this->remainingCount }}</b>
                </div>
            </div>
        </div>
    </div>

    {{-- Выбор полки - только для сценария размещения на склад --}}
    @if($fromStatus === 18 && $toStatus === 11)
        <div class="card">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-2">
                        <label class="form-label mb-0">Полка для
                            размещения:</label>
                    </div>
                    <div class="col-md-3">
                        <select wire:model.live="selectedShelfId"
                                class="form-control">
                            <option value="0">-- Выберите полку --</option>
                            @foreach($shelves as $shelf)
                                <option
                                    value="{{ $shelf->id }}">{{ $shelf->title }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Поле сканирования --}}
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
                    placeholder="Введите штрихкод хранения"
                >
            </div>
        </div>
    </div>

    {{-- Кнопка Готово --}}
    @if(!empty($scannedItems))
        <div class="card">
            <div class="card-body">
                <button
                    data-no-refocus
                    onclick="confirm('Применить новый статус ко всем отсканированным товарам?') || event.stopImmediatePropagation()"
                    wire:click="complete"
                    class="btn btn-success"
                >
                    Готово
                </button>
            </div>
        </div>
    @endif

    {{-- Таблица товаров --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-dark">
                    <tr>
                        <th>Штрихкод хранения</th>
                        <th>Товар</th>
                        <th>Размер</th>
                        <th>Переупаковщик</th>
                        <th>Действия</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($items as $item)
                        <tr @if(isset($scannedItems[$item->id])) style="background-color: #d4edda !important;" @endif>
                            <td>{{ $item->storage_barcode }}</td>
                            <td>{{ $item->item->title }}</td>
                            <td>{{ $item->item->width }}
                                x {{ $item->item->height }}
                            </td>
                            <td>{{ $item->repacker?->name }}</td>
                            <td>
                                @if(isset($scannedItems[$item->id]))
                                    <button
                                        data-no-refocus
                                        type="button"
                                        class="btn btn-sm btn-outline-danger"
                                        wire:click="removeFromList({{ $item->id }})"
                                    >
                                        Удалить
                                    </button>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-muted">Нет товаров со
                                статусом для обработки
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

                // Воспроизведение звуков при сканировании
                Livewire.on('scanSuccess', () => {
                    new Audio('/sounds/success.mp3').play();
                });

                Livewire.on('scanError', () => {
                    new Audio('/sounds/error.mp3').play();
                });
            }
        });
    </script>
@endpush
