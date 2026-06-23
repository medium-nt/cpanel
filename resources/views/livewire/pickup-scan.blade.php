<div class="col-md-12"
     x-data
     x-init="$nextTick(() => { $refs.scanInput.focus() })"
     @click="if (!$event.target.closest('select, button, input, textarea, a, [data-no-refocus]')) { $refs.scanInput.focus() }"
>
    {{-- Сканер --}}
    <div class="card">
        <div class="card-body">
            @if($statusMessage)
                <div class="alert {{ $statusClass }} py-2" role="alert">
                    {{ $statusMessage }}
                </div>
            @endif
            <div>
                <label>Отсканируйте ШК товара</label>
                <input
                    type="text"
                    x-ref="scanInput"
                    wire:model.defer="scanCode"
                    wire:keydown.enter.prevent="handleScan"
                    autocomplete="off"
                    class="form-control"
                    placeholder="Штрихкод"
                >
            </div>
        </div>
    </div>

    {{-- Таблица отсканированных --}}
    <div class="card">
        <div
            class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">
                Отсканировано ({{ count($scanned) }})
            </h3>
            @if(!empty($scanned))
                <button
                    type="button"
                    wire:click="clearAll"
                    wire:confirm="Очистить весь список?"
                    class="btn btn-outline-danger btn-sm"
                    data-no-refocus
                >
                    <i class="fas fa-trash mr-1"></i> Очистить всё
                </button>
            @endif
        </div>
        <div class="card-body">
            @if(!empty($scanned))
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th>Артикул</th>
                            <th>Заказ</th>
                            <th>Полка</th>
                            <th>Время</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($scanned as $row)
                            <tr>
                                <td>{{ $row['article_title'] }}</td>
                                <td>{{ $row['order_id'] ?? '—' }}</td>
                                <td>{{ $row['shelf'] ?? '—' }}</td>
                                <td>{{ $row['scanned_at'] }}</td>
                                <td>
                                    <button
                                        wire:click="removeScanned({{ $row['item_id'] }})"
                                        wire:confirm="Убрать товар из списка?"
                                        class="btn btn-danger btn-sm"
                                        data-no-refocus
                                    >
                                        Удалить
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">Отсканируйте первый товар.</p>
            @endif
        </div>
    </div>

    {{-- Аудио --}}
    <audio id="pickup-scan-success" src="{{ asset('sounds/success.mp3') }}"
           preload="auto"></audio>
    <audio id="pickup-scan-error" src="{{ asset('sounds/error.mp3') }}"
           preload="auto"></audio>
</div>

@push('scripts')
    <script>
        function playPickupScanSound(id) {
            const audio = document.getElementById(id);
            if (audio) {
                audio.currentTime = 0;
                audio.play().catch(() => {
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Livewire !== 'undefined') {
                Livewire.on('scanSuccess', () => playPickupScanSound('pickup-scan-success'));
                Livewire.on('scanError', () => playPickupScanSound('pickup-scan-error'));

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
