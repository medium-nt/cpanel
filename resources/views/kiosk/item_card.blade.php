<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="shortcut icon"
          href="{{ asset('vendor/adminlte/dist/img/crm_logo.png') }}">
    <title>МЕГАТЮЛЬ | {{ $title }}</title>

    <link rel="stylesheet"
          href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
    <link rel="stylesheet"
          href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">

    <link rel="stylesheet" href="{{ asset('css/kiosk.css') }}">
</head>
<body data-action="{{ $action }}"
      data-csrf-token="{{ csrf_token() }}"
      data-replace-storage-url="{{ route('warehouse_of_item.storage_barcode') }}"
      data-replace-process-url="{{ route('kiosk.process_replace', ['orderItem' => $orderItem]) }}"
      data-repack-item-id="{{ $orderItem->id }}"
      data-repack-storage-url="{{ route('warehouse_of_item.storage_barcode') }}"
      data-defect-old-reason="{{ old('reason') }}">

<div class="wrapper">
    <div class="content">
        <div class="container-fluid">
            <!-- Кнопка назад -->
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('on_inspection') }}"
                       class="btn-kiosk btn-lg btn-kiosk-blue">Назад</a>
                </div>
            </div>

            <!-- Alert для сообщений -->
            <div id="alert" class="alert d-none">
                <strong id="alert-title"></strong> <span
                    id="alert-message"></span>
            </div>

            <!-- Карточка товара -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $title }}
                        - {{ $orderItem->item->title }} {{ $orderItem->item->width }}
                        x {{ $orderItem->item->height }}</h3>
                </div>
                <div class="card-body">
                    <!-- Форма в зависимости от action -->

                    @if($action === 'repack')
                        <!-- Переупаковка -->
                        <form method="POST"
                              action="{{ route('kiosk.process_repack', ['orderItem' => $orderItem]) }}">
                            @csrf
                            <div class="form-group">
                                <label>Потрачено материалов на
                                    переупаковку:</label>
                                <select name="material_used" id="material-used"
                                        class="form-control form-control-lg">
                                    <option value="" selected disabled>Выберите
                                        материал
                                    </option>
                                    <option value="nothing">Ничего</option>
                                    <option value="flyer">1 флайер</option>
                                    <option value="bag">1 пакет</option>
                                    <option value="flyer-bag">Флаер/пакет
                                    </option>
                                </select>
                                @error('material_used')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button"
                                        id="repack-print-storage-btn"
                                        onclick="printRepackSticker()"
                                        class="btn btn-success btn-lg d-none">
                                    Стикер хранения
                                </button>
                            </div>
                            <button type="submit"
                                    id="repack-complete-btn"
                                    class="btn btn-primary btn-lg mt-2 d-none">
                                Готово
                            </button>
                        </form>

                    @elseif($action === 'replace')
                        <!-- Подмена -->
                        <div>
                            <div class="form-group">
                                <label>Материал:</label>
                                <select id="replace-material"
                                        class="form-control form-control-lg">
                                    <option value="" selected disabled>Выберите
                                        материал
                                    </option>
                                    <option value="Бамбук">Бамбук</option>
                                    <option value="Вуаль">Вуаль</option>
                                    <option value="Лен">Лен</option>
                                    <option value="Молния">Молния</option>
                                    <option value="Мрамор">Мрамор</option>
                                    <option value="Сетка">Сетка</option>
                                    <option value="Шифон">Шифон</option>
                                </select>
                            </div>
                            <div class="form-group mt-3">
                                <label>Ширина:</label>
                                <select id="replace-width"
                                        class="form-control form-control-lg">
                                    <option value="" selected disabled>Выберите
                                        ширину
                                    </option>
                                    <option value="200">200</option>
                                    <option value="300">300</option>
                                    <option value="400">400</option>
                                    <option value="500">500</option>
                                    <option value="600">600</option>
                                    <option value="700">700</option>
                                    <option value="800">800</option>
                                </select>
                            </div>
                            <div class="form-group mt-3">
                                <label>Высота:</label>
                                <select id="replace-height"
                                        class="form-control form-control-lg">
                                    <option value="" selected disabled>Выберите
                                        высоту
                                    </option>
                                    <option value="220">220</option>
                                    <option value="225">225</option>
                                    <option value="230">230</option>
                                    <option value="235">235</option>
                                    <option value="240">240</option>
                                    <option value="245">245</option>
                                    <option value="250">250</option>
                                    <option value="255">255</option>
                                    <option value="260">260</option>
                                    <option value="265">265</option>
                                    <option value="270">270</option>
                                    <option value="275">275</option>
                                    <option value="280">280</option>
                                    <option value="285">285</option>
                                    <option value="290">290</option>
                                    <option value="295">295</option>
                                </select>
                            </div>
                            <div class="form-group mt-3">
                                <label>Потрачено материалов:</label>
                                <select id="replace-material-used"
                                        class="form-control form-control-lg">
                                    <option value="" selected disabled>Выберите
                                        материал
                                    </option>
                                    <option value="nothing">Ничего</option>
                                    <option value="flyer">1 флайер</option>
                                    <option value="bag">1 пакет</option>
                                    <option value="flyer-bag">Флаер/пакет
                                    </option>
                                </select>
                            </div>

                            <!-- Кнопка создания товара -->
                            <button type="button"
                                    id="replace-create-btn"
                                    onclick="createReplaceItem()"
                                    class="btn btn-warning btn-lg d-none">
                                Создать товар
                            </button>

                            <!-- Кнопки печати (после создания) -->
                            <div class="d-none gap-2"
                                 id="replace-print-buttons">
                                <button type="button"
                                        id="replace-print-storage-btn"
                                        onclick="printReplaceSticker()"
                                        class="btn btn-success btn-lg">
                                    Стикер хранения
                                </button>
                            </div>

                            <!-- Кнопка готово (после печати ЛЮБОГО стикера) -->
                            <a href="{{ route('on_inspection') }}"
                               id="replace-complete-btn"
                               class="btn btn-primary btn-lg mt-2 d-none">Готово</a>
                        </div>

                    @elseif($action === 'defect')
                        <!-- Брак -->
                        <form method="POST"
                              action="{{ route('kiosk.process_defect', ['item_id' => $orderItem->id]) }}">
                            @csrf
                            <div class="form-group">
                                <label>Причина брака:</label>
                                <select name="reason" id="defect-reason"
                                        class="form-control form-control-lg">
                                    <option value="" selected disabled>Выберите
                                        причину
                                    </option>
                                    <option value="Порван"
                                            @if(old('reason') === 'Порван') selected @endif>
                                        Порван
                                    </option>
                                    <option value="Грязный"
                                            @if(old('reason') === 'Грязный') selected @endif>
                                        Грязный
                                    </option>
                                    <option value="Неверный размер"
                                            @if(old('reason') === 'Неверный размер') selected @endif>
                                        Неверный размер
                                    </option>
                                    <option value="Брак ткани"
                                            @if(old('reason') === 'Брак ткани') selected @endif>
                                        Брак ткани
                                    </option>
                                </select>
                                @error('reason')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit"
                                    id="defect-complete-btn"
                                    class="btn btn-primary btn-lg d-none">Готово
                            </button>
                        </form>

                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Аудио -->
    <audio id="scan-success-sound" src="{{ asset('sounds/success.mp3') }}"
           preload="auto"></audio>
    <audio id="scan-error-sound" src="{{ asset('sounds/error.mp3') }}"
           preload="auto"></audio>
</div>

<iframe id="printFrame" class="d-none"></iframe>

<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.js') }}"></script>
<script src="{{ asset('vendor/adminlte/dist/js/adminlte.js') }}"></script>
<script src="{{ asset('js/printBarcode.js') }}"></script>
<script
    src="{{ asset('js/kiosk/item-card.js') }}?v={{ filemtime(public_path('js/kiosk/item-card.js')) }}"></script>

</body>
</html>
