<div>
    @if($errorMessage)
        <div class="alert alert-danger">{{ $errorMessage }}</div>
    @endif

    {{-- Step 1: Upload --}}
    @if($step === 1)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Загрузка файла</h3>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label for="excelFile">Выберите файл (xlsx, xls,
                        csv)</label>
                    <input type="file"
                           wire:model.live="excelFile"
                           id="excelFile"
                           accept=".xlsx,.xls,.csv,.txt"
                           class="form-control-file @error('excelFile') is-invalid @enderror">
                    @error('excelFile')
                    <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                </div>
                <div class="form-group mt-3">
                    <small class="text-muted">
                        Файл должен содержать колонки с артикулом и количеством.
                        Максимум 10 МБ. Поддерживаются форматы xlsx, xls, csv.
                    </small>
                </div>
            </div>
            <div class="card-footer">
                <button type="button"
                        class="btn btn-primary"
                        wire:click="uploadAndParse"
                        wire:loading.attr="disabled"
                        wire:target="uploadAndParse"
                        @if(!$excelFile) disabled @endif>
                    <span wire:loading.remove wire:target="uploadAndParse">Загрузить и продолжить</span>
                    <span wire:loading
                          wire:target="uploadAndParse">Загрузка...</span>
                </button>
                <a href="{{ route('marketplace_order_items.sticker_tape') }}"
                   class="btn btn-default ml-2">Отмена</a>
            </div>
        </div>

        {{-- Step 2: Column Mapping --}}
    @elseif($step === 2)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Маппинг колонок</h3>
            </div>
            <div class="card-body">
                <p>Укажите какие колонки соответствуют артикулу и
                    количеству:</p>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Артикул</label>
                            <select wire:model.live="columnMap.article"
                                    class="form-control">
                                <option value="">-- Выберите колонку --</option>
                                @foreach($fileHeaders as $index => $header)
                                    <option
                                        value="{{ $index }}">{{ $header }}</option>
                                @endforeach
                            </select>
                            @error('columnMap.article')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Количество</label>
                            <select wire:model.live="columnMap.quantity"
                                    class="form-control">
                                <option value="">-- Выберите колонку --</option>
                                @foreach($fileHeaders as $index => $header)
                                    <option
                                        value="{{ $index }}">{{ $header }}</option>
                                @endforeach
                            </select>
                            @error('columnMap.quantity')
                            <span class="text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="button" class="btn btn-primary"
                        wire:click="confirmMapping">Далее
                </button>
                <button type="button" class="btn btn-default ml-2"
                        wire:click="goToStep(1)">Назад
                </button>
            </div>
        </div>

        {{-- Step 3: Preview + Generate --}}
    @elseif($step === 3)
        <div class="row align-items-end mb-3 p-2 bg-light rounded">
            <div class="col-12 mb-2 mb-md-0 col-md-auto">
                <strong>Выбор маркетплейса и кластера:</strong>
            </div>
            <div class="col-6 col-md-2">
                <select wire:model.live="globalMarketplace"
                        class="form-control form-control-sm">
                    <option value="0">-- Маркетплейс --</option>
                    <option value="1">OZON</option>
                    <option value="2">WB</option>
                </select>
            </div>
            <div class="col-6 col-md-4">
                <select id="global-cluster-select"
                        class="form-control form-control-sm"
                        wire:change="$set('globalCluster', $event.target.value)"
                        @if(!$globalMarketplace) disabled @endif>
                    <option value="">---</option>
                    @foreach($warehouses[$globalMarketplace] ?? [] as $name => $label)
                        <option
                            value="{{ $name }}" {{ $globalCluster === (string) $name ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12 col-md-auto mt-2 mt-md-0">
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Предпросмотр ({{ count($processedRows) }}
                    строк)</h3>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Артикул</th>
                        <th>Кол-во</th>
                        <th>Товар</th>
                        <th>Статус</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($processedRows as $row)
                        <tr class="{{ $row['error'] ? 'table-warning' : '' }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row['article_raw'] }}</td>
                            <td>{{ $row['quantity'] }}</td>
                            <td>{{ $row['item_title'] ?? '-' }}</td>
                            <td>
                                @if($row['error'])
                                    <span
                                        class="text-danger">{{ $row['error'] }}</span>
                                @else
                                    <span class="text-success">OK</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <button type="button" class="btn btn-default"
                        wire:click="goToStep(2)">Назад
                </button>
                @if($globalMarketplace)
                    <button type="button"
                            class="btn btn-success ml-2"
                            wire:click="generatePdf"
                            wire:loading.attr="disabled"
                            wire:target="generatePdf">
                        <i class="fas fa-print" wire:loading.remove
                           wire:target="generatePdf"></i>
                        <i class="fas fa-spinner fa-spin" wire:loading
                           wire:target="generatePdf"></i>
                        <span wire:loading.remove wire:target="generatePdf">Сгенерировать PDF</span>
                        <span wire:loading wire:target="generatePdf">Генерация...</span>
                    </button>
                @endif
            </div>
        </div>
    @endif
</div>

@push('css')
    <link
        href="{{ asset('vendor/select2/select2.min.css') }}"
        rel="stylesheet"/>
    <style>
        .select2-container--default .select2-selection--single {
            height: calc(1.5em + 0.75rem + 2px);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.5;
            padding: 0.375rem 0.75rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + 0.75rem + 2px);
        }
    </style>
@endpush

@push('js')
    <script
        src="{{ asset('vendor/select2/select2.min.js') }}"></script>
    <script>
        function initClusterSelect() {
            var el = document.getElementById('global-cluster-select');
            if (!el) return;
            if (el.classList.contains('select2-hidden-accessible')) {
                $(el).select2('destroy');
            }
            $(el).select2({
                width: '100%',
                dropdownAutoWidth: true,
            });

            $(el).on('select2:select', function (e) {
                const value = e.params.data.id;
                $(el).val(value);
                el.dispatchEvent(new Event('change', {bubbles: true}));
            });
        }

        document.addEventListener('livewire:init', function () {
            Livewire.hook('commit', function ({succeed}) {
                succeed(function () {
                    setTimeout(initClusterSelect, 50);
                });
            });
        });

        initClusterSelect();
    </script>
@endpush
