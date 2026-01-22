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
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('kiosk') }}"
                       class="btn-kiosk btn-lg btn-kiosk-blue">На главную</a>
                </div>
            </div>

            @if($user)
                <div class="card" style="top: 10px;">
                    <div class="card-body">
                        <h2 class="mb-3">Приветствую, {{ $user->name }}!</h2>
                        @if(!$user->shift_is_open)
                            <a class="btn btn-success btn-lg"
                               href="{{ route('open_close_work_shift', ['user_id' => $user->id, 'barcode' => request('barcode')]) }}">
                                Открыть смену
                            </a>
                        @else
                            <h4>
                                Ваше начало
                                смены: {{ Carbon\Carbon::parse($user->actual_start_work_shift)->format('H:i') }}
                                <br>
                                Ваш конец
                                смены: {{ Carbon\Carbon::parse($user->end_work_shift)->format('H:i') }}
                            </h4>
                            @if($user->end_work_shift < Carbon\Carbon::now())
                                <a class="btn btn-warning btn-lg mt-3"
                                   href="{{ route('open_close_work_shift', ['user_id' => $user->id, 'barcode' => request('barcode')]) }}"
                                   onclick="return confirm('Вы уверены, что хотите закрыть смену?')">
                                    Закрыть смену
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            @else
                @if(request()->filled('barcode'))
                    <div class="alert alert-default-danger text-center">
                        <h2>
                            Такого сотрудника не существует!
                        </h2>
                    </div>
                @else
                    <div class="alert alert-default-info text-center">
                        <h2>
                            Отсканируйте свой штрих-код сотрудника
                        </h2>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>

<!-- Modal для предупреждения о неактивности -->
<x-idle-modal-component/>

<script>
    const actionUrl = '{{ route('opening_closing_shifts') }}';

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
