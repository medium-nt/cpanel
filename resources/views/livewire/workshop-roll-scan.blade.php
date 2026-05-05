<div>
    <div class="card">
        <div class="card-body">
            <h5 class="mb-3">Запрошенный материал:
                <b>{{ $requestedMaterialTitle }}</b></h5>

            @if($order->comment)
                <p class="text-muted">Комментарий: {{ $order->comment }}</p>
            @endif

            <hr>

            <div class="row" x-data
                 x-init="$nextTick(() => { $refs.scanInput.focus() })">
                <div class="col-md-9">
                    <input type="text"
                           wire:model.defer="scanCode"
                           wire:keydown.enter.prevent="scanRoll"
                           x-ref="scanInput"
                           class="form-control mb-3"
                           placeholder="Отсканируйте ШК рулона">
                </div>
                <div class="col-md-3">
                    <button wire:click="scanRoll"
                            class="btn btn-primary w-100 mb-3">Добавить
                    </button>
                </div>
            </div>

            <audio id="scan-success-sound"
                   src="{{ asset('sounds/success.mp3') }}"
                   preload="auto"></audio>
            <audio id="scan-error-sound" src="{{ asset('sounds/error.mp3') }}"
                   preload="auto"></audio>

            @if($message)
                <div
                    class="mb-3 text-{{ $messageType === 'error' ? 'danger' : 'success' }}">
                    {{ $message }}
                </div>
            @endif

            @if($scannedMaterials->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-dark">
                        <tr>
                            <th>Материал</th>
                            <th>ШК рулона</th>
                            <th>Кол-во</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($scannedMaterials as $item)
                            <tr>
                                <td>{{ $item->material->title }}</td>
                                <td><b>{{ $item->roll->roll_code }}</b></td>
                                <td>{{ $item->quantity }} {{ $item->material->unit }}</td>
                                <td>
                                    <button
                                        wire:click="removeRoll({{ $item->id }})"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Удалить">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot class="font-weight-bold">
                        <tr>
                            <td colspan="2">ИТОГО отсканировано:</td>
                            <td colspan="2">{{ $scannedRollsCount }}
                                рул., {{ $scannedTotalQuantity }} {{ $scannedMaterials->first()?->material->unit }}</td>
                        </tr>
                        <tr>
                            <td colspan="2">Осталось на складе:</td>
                            <td colspan="2">{{ $storageRollsCount }}
                                рул., {{ $storageTotalQuantity }} {{ $scannedMaterials->first()?->material->unit }}</td>
                        </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="form-group mt-3">
                    <button wire:click="confirmShipment"
                            class="btn btn-success">
                        Подтвердить отгрузку
                    </button>
                </div>
            @else
                <p class="text-muted">Нет отсканированных рулонов.</p>
            @endif
        </div>
    </div>
</div>

@push('scripts')
    <script>
        function playSound(id) {
            const audio = document.getElementById(id);
            if (audio) {
                audio.currentTime = 0;
                audio.play().catch(() => {
                });
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Livewire !== 'undefined') {
                Livewire.on('clearMessage', () => {
                    setTimeout(() => {
                        @this.
                        call('resetMessage');
                    }, 3000);
                });

                Livewire.on('scanSuccess', () => {
                    playSound('scan-success-sound');
                    const input = document.querySelector('[x-ref="scanInput"]');
                    if (input) input.focus();
                });

                Livewire.on('scanError', () => {
                    playSound('scan-error-sound');
                    const input = document.querySelector('[x-ref="scanInput"]');
                    if (input) input.focus();
                });
            }
        });
    </script>
@endpush
