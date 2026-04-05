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
                        Файл должен содержать колонки: артикул/SKU, количество,
                        штрихкод/баркод (опционально), кластер (опционально).
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
                <a href="{{ route('marketplace_orders.index') }}"
                   class="btn btn-default ml-2">Отмена</a>
            </div>
        </div>

        {{-- Step 2: Column Mapping --}}
    @elseif($step === 2)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Сопоставление колонок</h3>
            </div>
            <div class="card-body">
                <p>Найденные заголовки в файле:
                    <strong>{{ implode(', ', $fileHeaders) }}</strong></p>
                <p class="text-muted">Выберите, какая колонка из файла
                    соответствует каждому полю. Система попыталась определить
                    автоматически.</p>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Артикул / SKU <span
                                    class="text-danger">*</span></label>
                            <select wire:model="columnMap.sku"
                                    class="form-control">
                                <option value="">-- Не выбрано --</option>
                                @foreach($fileHeaders as $index => $header)
                                    <option
                                        value="{{ $index }}">{{ $header }}</option>
                                @endforeach
                            </select>
                            @error('columnMap.sku') <span
                                class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Количество <span class="text-danger">*</span></label>
                            <select wire:model="columnMap.quantity"
                                    class="form-control">
                                <option value="">-- Не выбрано --</option>
                                @foreach($fileHeaders as $index => $header)
                                    <option
                                        value="{{ $index }}">{{ $header }}</option>
                                @endforeach
                            </select>
                            @error('columnMap.quantity') <span
                                class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Штрихкод / Баркод</label>
                            <select wire:model="columnMap.barcode"
                                    class="form-control">
                                <option value="">-- Не выбрано (использовать
                                    артикул) --
                                </option>
                                @foreach($fileHeaders as $index => $header)
                                    <option
                                        value="{{ $index }}">{{ $header }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Кластер</label>
                            <select wire:model="columnMap.cluster"
                                    class="form-control">
                                <option value="">-- Не выбрано --</option>
                                @foreach($fileHeaders as $index => $header)
                                    <option
                                        value="{{ $index }}">{{ $header }}</option>
                                @endforeach
                            </select>
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

        {{-- Step 3: Preview & Edit --}}
    @elseif($step === 3)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Предпросмотр ({{ count($processedRows) }}
                    строк)</h3>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <div class="info-box bg-info">
                            <span class="info-box-icon"><i
                                    class="fas fa-list"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Всего строк</span>
                                <span
                                    class="info-box-number">{{ count($processedRows) }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-success">
                            <span class="info-box-icon"><i
                                    class="fas fa-check"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Найдено</span>
                                <span
                                    class="info-box-number">{{ $this->matchedCount }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="info-box bg-danger">
                            <span class="info-box-icon"><i
                                    class="fas fa-times"></i></span>
                            <div class="info-box-content">
                                <span class="info-box-text">Не найдено</span>
                                <span
                                    class="info-box-number">{{ $this->unmatchedCount }}</span>
                            </div>
                        </div>
                    </div>
                </div>


                <div style="max-height: 70vh; overflow-y: auto;">
                    <table class="table table-bordered table-sm">
                        <thead class="thead-light"
                               style="position: sticky; top: 0; z-index: 1;">
                        <tr>
                            <th style="width: 30px;">#</th>
                            <th>Штрихкод</th>
                            <th style="width: 80px;">Кол-во</th>
                            <th>Кластер</th>
                            <th>Товар</th>
                            <th>SKU</th>
                            <th>Маркетплейс</th>
                            <th style="width: 80px;">Действие</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($processedRows as $rowIndex => $row)
                            <tr class="{{ $row['error'] ? 'table-danger' : '' }}">
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $row['barcode_raw'] }}</td>
                                <td>
                                    <input type="number"
                                           value="{{ $row['quantity'] }}"
                                           min="1"
                                           step="1"
                                           class="form-control form-control-sm"
                                           wire:change="updateRowQuantity({{ $rowIndex }}, $event.target.value)">
                                </td>
                                <td wire:ignore>
                                    <select
                                        class="form-control form-control-sm item-select2"
                                        wire:change="updateRowCluster({{ $rowIndex }}, $event.target.value)">
                                        <option value="">---</option>
                                        @foreach(($row['marketplace_id'] == 1 ? \App\Livewire\ExcelOrderImport::CLUSTERS_OZON : \App\Livewire\ExcelOrderImport::CLUSTERS_WB) as $cluster)
                                            <option
                                                value="{{ $cluster }}" {{ $row['cluster'] === $cluster ? 'selected' : '' }}>{{ $cluster }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <div wire:ignore>
                                        <select
                                            class="form-control form-control-sm item-select2"
                                            wire:change="updateRowItem({{ $rowIndex }}, $event.target.value)">
                                            <option value="">-- Выберите товар
                                                --
                                            </option>
                                            @foreach($allItems as $item)
                                                <option
                                                    value="{{ $item['id'] }}" {{ ($row['item_id'] ?? null) == $item['id'] ? 'selected' : '' }}>{{ $item['title'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @if($row['error'])
                                        <small
                                            class="text-danger">{{ $row['error'] }}</small>
                                    @endif
                                </td>
                                <td>{{ $row['sku_raw'] }}</td>
                                <td>
                                    {{ $row['marketplace_id'] == 1 ? 'OZON' : 'WB' }}
                                </td>
                                <td>
                                    <button type="button"
                                            class="btn btn-danger btn-sm"
                                            wire:click="removeRow({{ $rowIndex }})"
                                            title="Удалить строку">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer">
                <button type="button" class="btn btn-success" wire:click="save"
                        wire:loading.attr="disabled">
                    <span wire:loading.remove>Сохранить заказы</span>
                    <span wire:loading>Сохранение...</span>
                </button>
                <button type="button" class="btn btn-default ml-2"
                        wire:click="goToStep(2)">Назад
                </button>
            </div>
        </div>

        {{-- Step 4: Result --}}
    @elseif($step === 4)
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                <h3>Импорт завершён!</h3>
                <p class="lead">Создано заказов:
                    <strong>{{ $createdCount }}</strong></p>
                <a href="{{ route('marketplace_orders.index') }}"
                   class="btn btn-primary mt-3">Вернуться к заказам</a>
            </div>
        </div>
    @endif

    @push('css')
        <link
            href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css"
            rel="stylesheet"/>
    @endpush

    @push('js')
        <script
            src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
            <script>
                function initItemSelect2() {
                    document.querySelectorAll('.item-select2').forEach(function (el) {
                        if (el.classList.contains('select2-hidden-accessible')) return;
                        $(el).select2({
                            width: '100%',
                            dropdownAutoWidth: true,
                            placeholder: '-- Выберите --',
                        });

                        $(el).on('select2:select', function (e) {
                            const value = e.params.data.id;
                            $(el).val(value);
                            el.dispatchEvent(new Event('change', {bubbles: true}));
                        });
                    });
                }

                document.addEventListener('livewire:init', function () {
                    Livewire.hook('commit', function ({succeed}) {
                        succeed(function () {
                            setTimeout(initItemSelect2, 100);
                        });
                    });
                });
            </script>
        @endpush
</div>
