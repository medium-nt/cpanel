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

    <style>
        .wrapper {
            background-color: #f4f6f9;
        }
    </style>
</head>
<body>

<div class="wrapper" style="min-height: 100vh;">
    <div class="content">
        <div class="container-fluid">
            <div class="card" style="top: 10px;">
                <div class="card-body">
                    <label>Поле ввода сразу должно быть с фокусом.</label>
                    <input type="text"
                           id="badgeInput"
                           class="form-control form-control-lg"
                           placeholder="1234567890"
                           style="border-width: 3px;"
                           value=""
                           autofocus>

                    <span id="result"></span>
                </div>
            </div>

            <iframe id="printFrame"
                    style="display:none"></iframe>

            <div class="card" style="top: 10px;">
                <div class="card-body">
                    <button
                        onclick="printBarcode('/fbo_barcode?marketplaceOrderId=FBO1')"
                            class="btn btn-primary">
                        Печать
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const input = document.getElementById('badgeInput');

    let buffer = '';
    let lastTime = Date.now();

    document.addEventListener('keypress', e => {
        const now = Date.now();

        // если пауза — считаем, что начался новый скан
        if (now - lastTime > 200) {
            buffer = '';
        }
        lastTime = now;

        if (e.key === 'Enter') {
            input.value = buffer;
            handleScanned(buffer); // твоя бизнес-логика
            buffer = '';

            // опционально очищать поле
            setTimeout(() => input.value = '', 300);
        } else {
            buffer += e.key;
        }
    });

    function handleScanned(code) {
        console.log('SCANNED:', code);

        // запротить span результат
        const result = document.getElementById('result');
        result.innerHTML = 'Отсканирован код: ' + code;
    }
</script>

<script src="{{ asset('js/printBarcode.js') }}"></script>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.js') }}"></script>
<script src="{{ asset('vendor/adminlte/dist/js/adminlte.js') }}"></script>
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

</body>
</html>
