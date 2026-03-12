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

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

    <link rel="stylesheet" href="{{ asset('css/kiosk.css') }}">

    <style>
        .content {
            overflow-y: auto;
        }

        .container-fluid {
            overflow-y: auto;
        }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('kiosk') }}"
                       class="btn-kiosk btn-lg btn-kiosk-blue">На главную</a>
                </div>
            </div>

            <iframe id="printFrame"
                    style="display:none"></iframe>

            <audio id="scan-success-sound"
                   src="{{ asset('sounds/success.mp3') }}"
                   preload="auto"></audio>
            <audio id="scan-error-sound" src="{{ asset('sounds/error.mp3') }}"
                   preload="auto"></audio>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible mt-3">
                    <button type="button" class="close" data-dismiss="alert"
                            aria-hidden="true">×
                    </button>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible mt-3">
                    <button type="button" class="close" data-dismiss="alert"
                            aria-hidden="true">×
                    </button>
                    {{ session('error') }}
                </div>
            @endif

            <div id="alert" class="alert d-none">
                <strong id="alert-title"></strong> <span
                    id="alert-message"></span>
            </div>

            <div class="card">
                <div class="card-header p-0">
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link active bg-white"
                               href="{{ route('on_inspection') }}">Товары на
                                осмотре</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('returns') }}">Товары
                                готовые к осмотру</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link"
                               href="{{ route('kiosk.processed_items') }}">Обработанные
                                товары</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-md-2">
                            <select name="material"
                                    id="material"
                                    class="form-control form-control-lg"
                                    onchange="updatePageWithQueryParam(this)"
                                    required>
                                <option value="" selected disabled>Выберите
                                    материал
                                </option>
                                <option value="Бамбук"
                                        @if(request()->get('material') == 'Бамбук') selected @endif>
                                    Бамбук
                                </option>
                                <option value="Вуаль"
                                        @if(request()->get('material') == 'Вуаль') selected @endif>
                                    Вуаль
                                </option>
                                <option value="Лен"
                                        @if(request()->get('material') == 'Лен') selected @endif>
                                    Лен
                                </option>
                                <option value="Молния"
                                        @if(request()->get('material') == 'Молния') selected @endif>
                                    Молния
                                </option>
                                <option value="Мрамор"
                                        @if(request()->get('material') == 'Мрамор') selected @endif>
                                    Мрамор
                                </option>
                                <option value="Сетка"
                                        @if(request()->get('material') == 'Сетка') selected @endif>
                                    Сетка
                                </option>
                                <option value="Шифон"
                                        @if(request()->get('material') == 'Шифон') selected @endif>
                                    Шифон
                                </option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <select name="width"
                                    id="width"
                                    class="form-control form-control-lg"
                                    onchange="updatePageWithQueryParam(this)"
                                    required>
                                <option value="" selected disabled>Выберите
                                    ширину
                                </option>
                                <option value="200"
                                        @if(request()->get('width') == 200) selected @endif>
                                    200
                                </option>
                                <option value="300"
                                        @if(request()->get('width') == 300) selected @endif>
                                    300
                                </option>
                                <option value="400"
                                        @if(request()->get('width') == 400) selected @endif>
                                    400
                                </option>
                                <option value="500"
                                        @if(request()->get('width') == 500) selected @endif>
                                    500
                                </option>
                                <option value="600"
                                        @if(request()->get('width') == 600) selected @endif>
                                    600
                                </option>
                                <option value="700"
                                        @if(request()->get('width') == 700) selected @endif>
                                    700
                                </option>
                                <option value="800"
                                        @if(request()->get('width') == 800) selected @endif>
                                    800
                                </option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <select name="height"
                                    id="height"
                                    class="form-control form-control-lg"
                                    onchange="updatePageWithQueryParam(this)"
                                    required>
                                <option value="" selected disabled>Выберите
                                    высоту
                                </option>
                                <option value="220"
                                        @if(request()->get('height') == 220) selected @endif>
                                    220
                                </option>
                                <option value="225"
                                        @if(request()->get('height') == 225) selected @endif>
                                    225
                                </option>
                                <option value="230"
                                        @if(request()->get('height') == 230) selected @endif>
                                    230
                                </option>
                                <option value="235"
                                        @if(request()->get('height') == 235) selected @endif>
                                    235
                                </option>
                                <option value="240"
                                        @if(request()->get('height') == 240) selected @endif>
                                    240
                                </option>
                                <option value="245"
                                        @if(request()->get('height') == 245) selected @endif>
                                    245
                                </option>
                                <option value="250"
                                        @if(request()->get('height') == 250) selected @endif>
                                    250
                                </option>
                                <option value="255"
                                        @if(request()->get('height') == 255) selected @endif>
                                    255
                                </option>
                                <option value="260"
                                        @if(request()->get('height') == 260) selected @endif>
                                    260
                                </option>
                                <option value="265"
                                        @if(request()->get('height') == 265) selected @endif>
                                    265
                                </option>
                                <option value="270"
                                        @if(request()->get('height') == 270) selected @endif>
                                    270
                                </option>
                                <option value="275"
                                        @if(request()->get('height') == 275) selected @endif>
                                    275
                                </option>
                                <option value="280"
                                        @if(request()->get('height') == 280) selected @endif>
                                    280
                                </option>
                                <option value="285"
                                        @if(request()->get('height') == 285) selected @endif>
                                    285
                                </option>
                                <option value="290"
                                        @if(request()->get('height') == 290) selected @endif>
                                    290
                                </option>
                                <option value="295"
                                        @if(request()->get('height') == 295) selected @endif>
                                    295
                                </option>
                            </select>
                        </div>

                        <div class="form-group col-md-2 ml-3">
                            <a href="{{ route('on_inspection') }}"
                               class="btn btn-lg btn-outline-secondary">Сбросить
                                фильтры</a>
                        </div>
                    </div>
                    <table class="table table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Заказ</th>
                            <th scope="col">Товар</th>
                            <th scope="col">Действия</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($onInspectionItems ?? [] as $item)
                            <tr style="font-size: 18px;">
                                <td class="align-middle">
                                    {{ $item->id }}
                                </td>
                                <td class="align-middle">
                                    <b style="color: #FF8C00;">
                                        {{ $item->marketplaceOrder->order_id }}
                                    </b>
                                </td>
                                <td class="align-middle">
                                    <b>
                                        {{ $item->item->title }}
                                        {{ $item->item->width }} х
                                        {{ $item->item->height }}
                                    </b>
                                </td>
                                <td>
                                    <a href="{{ route('kiosk.item_card', ['item_id' => $item->id, 'action' => 'repack']) }}"
                                       class="btn btn-xl btn-success mr-5">Переупаковка</a>
                                    <a href="{{ route('kiosk.item_card', ['item_id' => $item->id, 'action' => 'replace']) }}"
                                       class="btn btn-xl btn-warning mr-5">Подмена</a>
                                    <a href="{{ route('kiosk.item_card', ['item_id' => $item->id, 'action' => 'defect']) }}"
                                       class="btn btn-xl btn-danger mr-5">Брак</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-muted text-center">
                                    Заявок нет
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>

    <!-- Modal для предупреждения о неактивности -->
    <x-idle-modal-component/>

    <script>
        let buffer = '';
        let lastTime = Date.now();

        // Обработчик сканера и нажатия Enter
        document.addEventListener('keypress', e => {
            const now = Date.now();

            // если пауза — считаем, что начался новый скан
            if (now - lastTime > 200) {
                buffer = '';
            }
            lastTime = now;

            if (e.key === 'Enter') {
                // Отправляем штрихкод на сервер
                fetch('{{ route('kiosk.scan_inspection_item') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({barcode: buffer})
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            playSound('scan-success-sound');
                            showAlert('success', 'Успех!', data.message || 'Статус обновлён');
                            setTimeout(() => {
                                window.location.href = '{{ route('on_inspection', ['material' => request()->get('material'), 'width' => request()->get('width'), 'height' => request()->get('height')]) }}';
                            }, 1000);
                        } else {
                            playSound('scan-error-sound');
                            showAlert('danger', 'Ошибка!', data.message || 'Ошибка при сканировании');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        playSound('scan-error-sound');
                        showAlert('danger', 'Ошибка!', 'Ошибка соединения');
                    });
                buffer = '';
            } else {
                buffer += e.key;
            }
        });
    </script>

    <script
        src="{{ asset('js/printBarcode.js') }}?v={{ filemtime(public_path('js/printBarcode.js')) }}"></script>
    <script
        src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.js') }}"></script>
    <script src="{{ asset('vendor/adminlte/dist/js/adminlte.js') }}"></script>
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

    <script src="{{ asset('js/PageQueryParam.js') }}"></script>

    <script>
        function showAlert(type, title, message) {
            const alertEl = document.getElementById('alert');
            const alertTitle = document.getElementById('alert-title');
            const alertMessage = document.getElementById('alert-message');

            alertEl.className = 'alert alert-' + type;
            alertTitle.textContent = title;
            alertMessage.textContent = message;
            alertEl.classList.remove('d-none');
        }

        function playSound(id) {
            const audio = document.getElementById(id);
            if (audio) {
                audio.currentTime = 0;
                audio.play().catch(() => {
                });
            }
        }

        // Показываем сообщение из URL параметра success
        const urlParams = new URLSearchParams(window.location.search);
        const successMsg = urlParams.get('success');
        if (successMsg) {
            showAlert('success', 'Успех!', decodeURIComponent(successMsg));
            // Убираем параметр из URL
            window.history.replaceState({}, document.title, window.location.pathname + window.location.search.replace(/[?&]success=[^&]*/, '').replace(/^&/, '?'));
        }
    </script>

</div>
</body>
</html>
