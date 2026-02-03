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

            @if (!$isAdded)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Новый брак/остаток</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('defects.create') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="quantity">Количество</label>
                                    <input type="number" step="0.01"
                                           class="form-control" name="quantity"
                                           id="quantity" required
                                           value="{{ old('quantity') }}"
                                           readonly>

                                    <button type="button"
                                            class="btn btn-success mt-1 mr-2"
                                            data-step="0.01">+0.01
                                    </button>
                                    <button type="button"
                                            class="btn btn-success mt-1 mr-2"
                                            data-step="0.10">+0.10
                                    </button>
                                    <button type="button"
                                            class="btn btn-success mt-1 mr-2"
                                            data-step="1.00">+1.00
                                    </button>
                                    <br>
                                    <button type="button"
                                            class="btn btn-danger mt-1 mr-2"
                                            data-step="-0.01">- 0.01
                                    </button>
                                    <button type="button"
                                            class="btn btn-danger mt-1 mr-2"
                                            data-step="-0.10">- 0.10
                                    </button>
                                    <button type="button"
                                            class="btn btn-danger mt-1 mr-2"
                                            data-step="-1.00">- 1.00
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="quantity">Причина</label>
                                    <select name="reason" id="reason"
                                            class="form-control is-invalid"
                                            required>
                                        <option value="" selected disabled>
                                            Выберите причину
                                        </option>
                                        <option value="Пятна">Пятна</option>
                                        <option value="Зацепки">Зацепки</option>
                                        <option value="Стрелы">Стрелы</option>
                                        <option value="Полосы">Полосы</option>
                                        <option value="Дыры">Дыры</option>
                                        <option value="Раздвижки">Раздвижки
                                        </option>
                                        <option value="Брак утяжелителя">Брак
                                            утяжелителя
                                        </option>
                                    </select>
                                    <script>
                                        document.getElementById('reason').addEventListener('change', function () {
                                            if (this.value) {
                                                this.classList.remove('is-invalid');
                                                this.classList.add('is-valid');
                                            } else {
                                                this.classList.remove('is-valid');
                                                this.classList.add('is-invalid');
                                            }
                                            // Убираем фокус после выбора
                                            setTimeout(() => this.blur(), 0);
                                        });
                                    </script>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label for="roll">ШК рулона</label>
                                    <input type="text" class="form-control"
                                           name="roll" id="roll"
                                           value="{{ old('roll') }}"
                                           required readonly>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="material">Материал</label>
                                    <input type="text" class="form-control"
                                           id="material" readonly>
                                </div>
                            </div>
                            <input type="hidden" name="user_id"
                                   value="{{ $userId }}">
                        </div>
                        <button type="submit"
                                id="submitBtn"
                                class="btn btn-kiosk btn-success mt-3 d-none">
                            Создать заявку на брак
                        </button>
                    </form>
                </div>
            </div>
            @else
                <div class="card">
                    <div class="card-body">
                        <div
                            class="alert alert-default-success text-center mt-3">
                            <h3>
                                Заявка успешно создана! Распечатайте стикер и
                                прикрепите на
                                материал.
                            </h3>
                        </div>

                        <button
                            onclick="printBarcode('{{ route('defects.print_sticker', ['order' => $defectMaterialOrders->first()]) }}')"
                            class="btn btn-outline-primary btn-lg mr-5">
                            <i class="fas fa-print"></i>
                            Распечатать стикер
                        </button>

                        <a class="btn btn-outline-success btn-lg ml-5"
                           href="{{ route('defects.create') }}">
                            Оформить еще одну заявку
                        </a>
                    </div>
                </div>
            @endif

            <iframe id="printFrame"
                    style="display:none"></iframe>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">История заявок за сегодня</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">Стикер</th>
                            <th scope="col">Материал</th>
                            <th scope="col">Количество</th>
                            <th scope="col">Причина</th>
                            <th scope="col">ШК рулона</th>
                            <th scope="col">Дата</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($defectMaterialOrders ?? [] as $defect)
                            <tr style="{{ $loop->first ? 'background-color: #e6f4ef;' : '' }}">
                                <td>
                                    <button
                                        onclick="printBarcode('{{ route('defects.print_sticker', ['order' => $defect]) }}')"
                                        class="btn btn-outline-secondary btn-md">
                                        <i class="fas fa-barcode fa-2x"></i>
                                    </button>
                                </td>
                                <td>{{ $defect->movementMaterials->first()->material->title }}</td>
                                <td>{{ $defect->movementMaterials->first()->quantity }} {{ $defect->movementMaterials->first()->material->unit }}</td>
                                <td>{{ $defect->comment }}</td>
                                <td>{{ $defect->movementMaterials->first()?->roll?->roll_code }}</td>
                                <td>{{ $defect->created_date_time }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-muted text-center">
                                    За сегодня заявок нет
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
    let roll = document.getElementById('roll');

    // Функция для подгрузки material_id
    async function loadMaterialTitle(rollCode) {
        const submitBtn = document.getElementById('submitBtn');

        if (!rollCode || rollCode.trim().length === 0) {
            document.getElementById('material').value = '';
            checkSubmitBtnVisibility();
            return;
        }

        try {
            const response = await fetch(`/kiosk/api/roll/${rollCode.trim()}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
            });

            const data = await response.json();

            if (data.material_id) {
                document.getElementById('material').value = data.material_id;
                checkSubmitBtnVisibility();
            } else {
                document.getElementById('material').value = 'такого материала нет';
                checkSubmitBtnVisibility();
            }
        } catch (error) {
            console.error('Error fetching roll:', error);
            checkSubmitBtnVisibility();
        }
    }

    // Функция для проверки видимости кнопки отправки
    function checkSubmitBtnVisibility() {
        const submitBtn = document.getElementById('submitBtn');
        const material = document.getElementById('material').value;
        const quantity = parseFloat(document.getElementById('quantity').value) || 0;

        // Показываем кнопку только если материал найден И количество > 0
        if (material && material !== 'такого материала нет' && quantity > 0) {
            submitBtn.classList.remove('d-none');
        } else {
            submitBtn.classList.add('d-none');
        }
    }

    // Обработчик сканера и нажатия Enter
    document.addEventListener('keypress', e => {
        const now = Date.now();

        // если пауза — считаем, что начался новый скан
        if (now - lastTime > 200) {
            buffer = '';
        }
        lastTime = now;

        if (e.key === 'Enter') {
            roll.value = buffer;
            loadMaterialTitle(buffer);
            buffer = '';
        } else {
            buffer += e.key;
        }
    });

    // Обработчики для кнопок изменения количества
    document.querySelectorAll('[data-step]').forEach(button => {
        button.addEventListener('click', function () {
            const step = parseFloat(this.getAttribute('data-step'));
            const quantityInput = document.getElementById('quantity');
            let currentValue = parseFloat(quantityInput.value) || 0;
            let newValue = currentValue + step;

            // Ограничение: не меньше 0
            if (newValue < 0) {
                newValue = 0;
            }

            // Форматирование до 2 знаков после запятой
            quantityInput.value = newValue.toFixed(2);

            // Проверяем видимость кнопки отправки
            checkSubmitBtnVisibility();

            // Убираем фокус с кнопки
            this.blur();
        });
    });
</script>

    <script src="{{ asset('js/printBarcode.js') }}"></script>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.js') }}"></script>
<script src="{{ asset('vendor/adminlte/dist/js/adminlte.js') }}"></script>
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

</body>
</html>
