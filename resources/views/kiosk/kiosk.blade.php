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
        html, body {
            height: 100%;
            overflow: hidden;
            margin: 0;
            padding: 0;
            touch-action: none;
        }

        .wrapper {
            background-color: #f4f6f9;
            height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .content {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .container-fluid {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .kiosk-buttons-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: repeat(2, 1fr);
            gap: 1rem;
            padding: 1rem;
            flex: 1;
            min-height: 0;
        }

        .btn-kiosk {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
            border-radius: 0.5rem;
            transition: transform 0.1s, box-shadow 0.1s;
        }

        .btn-kiosk:active {
            transform: scale(0.98);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .btn-kiosk-blue {
            background-color: #3b82f6;
            color: white;
        }

        .btn-kiosk-green {
            background-color: #22c55e;
            color: white;
        }

        .btn-kiosk-yellow {
            background-color: #eab308;
            color: black;
        }

        .btn-kiosk-red {
            background-color: #ef4444;
            color: white;
        }

        .btn-kiosk-purple {
            background-color: #a855f7;
            color: white;
        }

        .btn-kiosk-orange {
            background-color: #f97316;
            color: white;
        }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="content">
        <div class="container-fluid">
            @if($user)
                <div class="alert alert-default-success text-center mt-3">
                    <h2>
                        Приветствую, {{ $user->name }}!
                        <a href="{{ route('kiosk', ['idle' => true]) }}"
                           class="btn btn-sm float-right mt-1 btn-danger">
                            Выход
                        </a>
                    </h2>
                </div>

                <!-- Modal для предупреждения о неактивности -->
                <x-idle-modal-component/>

                <div class="kiosk-buttons-grid">
                    <a href="{{ route('opening_closing_shifts') }}"
                       class="btn-kiosk btn-kiosk-blue">Открытие / Закрытие
                        смены</a>
                    <a href="{{ route('sticker_printing') }}"
                       class="btn-kiosk btn-kiosk-green">Печать
                        заказов</a>
                    {{--                <a href="#" class="btn-kiosk btn-kiosk-yellow">Работа с--}}
                    {{--                    рулонами</a>--}}
                    {{--                <a href="#" class="btn-kiosk btn-kiosk-red">Работа с--}}
                    {{--                    возвратами</a>--}}
                    <a href="{{ route('statistics_reports') }}"
                       class="btn-kiosk btn-kiosk-orange">Статистика /
                        Отчеты</a>
                    @if($user->isCutter() || $user->isSeamstress())
                    <a href=" {{ route('defects.create') }}"
                       class="btn-kiosk btn-kiosk-purple">Работа с браком</a>
                    @endif
                </div>
            @else
                @if(request()->filled('barcode'))
                    <div class="alert alert-default-danger text-center mt-3">
                        <h2>
                            Неверный штрих-код сотрудника!
                        </h2>
                    </div>

                    <!-- Modal для предупреждения о неактивности -->
                    <x-idle-modal-component/>
                @else
                    <div class="alert alert-default-info text-center mt-3">
                        <h2>
                            Отсканируйте свой штрих-код сотрудника
                        </h2>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<script>
    const actionUrl = '{{ route('kiosk') }}';

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
            window.location.href = actionUrl + '?barcode=' + encodeURIComponent(buffer);
            buffer = '';
        } else {
            buffer += e.key;
        }
    });
</script>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.js') }}"></script>
<script src="{{ asset('vendor/adminlte/dist/js/adminlte.js') }}"></script>
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

</body>
</html>
