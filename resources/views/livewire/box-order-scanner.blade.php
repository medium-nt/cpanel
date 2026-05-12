<div class="col-md-12"
     x-data
     @if(!$box->closed_at)
     x-init="$nextTick(() => { $refs.scanInput.focus() })"
     @click="if (!$event.target.closest('select, button, input, textarea, a, [data-no-refocus]')) { $refs.scanInput.focus() }"
    @endif
>
    {{-- Сканер --}}
    @if(!$box->closed_at)
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
    @endif

    {{-- Таблица заказов --}}
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                Заказы в коробе ({{ $box->orders->count() }})
            </h3>
        </div>
        <div class="card-body">
            @if($box->orders->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th>Заказ</th>
                            <th>Товар</th>
                            @if(!$box->closed_at)
                                <th></th>
                            @endif
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($box->orders as $order)
                            @foreach($order->items as $item)
                                <tr>
                                    <td>{{ $order->order_id }}</td>
                                    <td>{{ $item->item?->title ?? '-' }} {{ $item->item?->width }}
                                        x{{ $item->item?->height }}</td>
                                    @if(!$box->closed_at && $loop->first)
                                        <td rowspan="{{ $order->items->count() }}">
                                            <button
                                                wire:click="removeOrder({{ $order->id }})"
                                                class="btn btn-danger btn-sm"
                                                data-no-refocus
                                                onclick="return confirm('Убрать заказ из короба?')"
                                            >
                                                Удалить
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">Короб пуст. Отсканируйте товары для
                    добавления.</p>
            @endif
        </div>
    </div>

    {{-- Аудио --}}
    <audio id="box-scan-success" src="{{ asset('sounds/success.mp3') }}"
           preload="auto"></audio>
    <audio id="box-scan-error" src="{{ asset('sounds/error.mp3') }}"
           preload="auto"></audio>
</div>

@push('scripts')
    <script>
        function playBoxScanSound(id) {
            const audio = document.getElementById(id);
            if (audio) {
                audio.currentTime = 0;
                audio.play().catch(() => {
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Livewire !== 'undefined') {
                Livewire.on('scanSuccess', () => playBoxScanSound('box-scan-success'));
                Livewire.on('scanError', () => playBoxScanSound('box-scan-error'));

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
