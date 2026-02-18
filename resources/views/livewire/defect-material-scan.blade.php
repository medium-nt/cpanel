<div class="col-md-12"
     x-data
     x-init="$nextTick(() => { $refs.scanInput.focus() })"
     @click="if (!$event.target.closest('select, button, input, textarea, a, [data-no-refocus]')) { $refs.scanInput.focus() }"
>
    {{-- Карточка со счетчиком --}}
    <div class="card">
        <div class="card-body">
            <div>Отсканировано заявок: <b>{{ $scannedOrders->count() }}</b>
            </div>
            <div>Всего заявок на брак: <b>{{ $totalAvailableOrders }}</b></div>

            @if($scannedOrders->isNotEmpty())
                <div class="mt-3">
                    <button
                        onclick="confirm('Принять все отсканированные заявки на брак?') || event.stopImmediatePropagation()"
                        wire:click="acceptAll"
                        class="btn btn-success"
                        data-no-refocus
                    >
                        Принять всё отсканированные
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Карточка сканирования --}}
    <div class="card">
        <div class="card-body">
            @if($statusMessage)
                <div class="alert {{ $statusClass }}" role="alert">
                    {{ $statusMessage }}
                </div>
            @endif
            <div>
                <label>Отсканируйте стикер брака</label>
                <input
                    type="text"
                    x-ref="scanInput"
                    wire:model.defer="scanCode"
                    wire:keydown.enter.prevent="handleScan"
                    autocomplete="off"
                    class="border rounded px-3 py-2 w-100"
                    placeholder="Отсканируйте штрихкод (DEF-123)"
                >
                <small class="text-muted">Формат: DEF-123</small>
            </div>
        </div>
    </div>

    {{-- Таблица отсканированных заявок --}}
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-bordered">
                    <thead class="table-dark">
                    <tr>
                        <th>Штрихкод</th>
                        <th>Материал</th>
                        <th>Количество</th>
                        <th>Причина</th>
                        <th>Сотрудник</th>
                        <th>Дата</th>
                        <th>Действия</th>
                    </tr>
                    </thead>
                    <tbody class="table-hover">
                    @forelse($scannedOrders as $order)
                        @php
                            $material = $order->movementMaterials->first();
                        @endphp
                        <tr>
                            <td><b>DEF-{{ $order->id }}</b></td>
                            <td>{{ $material->material->title }}</td>
                            <td>{{ $material->quantity }} {{ $material->material->unit }}</td>
                            <td>{{ $order->comment }}</td>
                            <td>{{ $order->seamstress?->name ?? $order->cutter?->name }}</td>
                            <td>{{ $order->created_date }}</td>
                            <td>
                                <button
                                    wire:click="removeFromList({{ $order->id }})"
                                    class="btn btn-sm btn-outline-danger"
                                    data-no-refocus
                                >
                                    Удалить
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted text-center">
                                Отсканируйте стикеры брака для добавления в
                                список
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Аудио элементы для звуков --}}
    <audio id="scan-success-sound" src="{{ asset('sounds/success.mp3') }}"
           preload="auto"></audio>
    <audio id="scan-error-sound" src="{{ asset('sounds/error.mp3') }}"
           preload="auto"></audio>
</div>

@push('scripts')
    <script>
        function playScanSound(id) {
            const audio = document.getElementById(id);
            if (audio) {
                audio.currentTime = 0;
                audio.play().catch(() => {
                });
            } else {
                console.warn(`Аудио элемент с id="${id}" не найден`);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Livewire !== 'undefined') {
                // Обработчики звуков
                Livewire.on('scanSuccess', () => playScanSound('scan-success-sound'));
                Livewire.on('scanError', () => playScanSound('scan-error-sound'));

                // Восстановление фокуса после каждого обновления
                Livewire.hook('message.processed', (message, component) => {
                    const input = component.el.querySelector('[x-ref="scanInput"]');
                    if (input) {
                        input.focus();
                    }
                });
            } else {
                console.warn('Livewire не загружен');
            }
        });
    </script>
@endpush

