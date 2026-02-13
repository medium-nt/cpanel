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
    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css"
        rel="stylesheet">
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
                        <h2 class="mb-5">Приветствую, {{ $user->name }}!</h2>
                        @if(!$user->shift_is_open)
                            @if($user->closed_work_shift == '00:00:00')
                                <h4>
                                    Ваша смена начинается
                                    в: {{ Carbon\Carbon::parse($user->start_work_shift)->format('H:i') }}
                                    @if($isLate)
                                        <p class="text-danger mt-3">
                                            Вы опоздали
                                            на {{ $lateTimeStartWorkShift }}
                                            мин.
                                            Вам разрешено опоздание только
                                            на {{ $user->max_late_minutes }}
                                            мин.
                                            <br>
                                            При открытии смены вам будет
                                            начислен
                                            штраф в
                                            размере: {{ $lateOpenedShiftPenalty }}
                                            руб.
                                            <br>
                                        </p>
                                    @endif
                                </h4>
                                <a class="btn btn-success btn-lg mt-3"
                                   href="{{ route('open_close_work_shift', ['user_id' => $user->id, 'barcode' => '1-'.$user->id.'-1']) }}">
                                    Открыть смену
                                </a>
                            @else
                                <h4>
                                    Работа на сегодня закончена.<br>
                                    Ваша смена закрыта
                                    в: {{ Carbon\Carbon::parse($user->closed_work_shift)->format('H:i') }}
                                </h4>
                            @endif
                        @else
                            <h4>
                                Ваше начало смены
                                в: {{ Carbon\Carbon::parse($user->actual_start_work_shift)->format('H:i') }}
                                <br>
                                Ваш конец смены
                                в: {{ Carbon\Carbon::parse($user->end_work_shift)->format('H:i') }}
                            </h4>
                            @if($user->end_work_shift < Carbon\Carbon::now() || $user->dailyLimitReached())
                                <a class="btn btn-warning btn-lg mt-3"
                                   href="{{ route('open_close_work_shift', ['user_id' => $user->id, 'barcode' => '1-'.$user->id.'-1']) }}"
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

<!-- Подключаем JS-файл Toastr -->
<script
    src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<script>
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": "5000"
    };
</script>
<style>
    .toast {
        opacity: 1 !important;
    }
</style>

@if(session('success'))
    <script>
        toastr.success("{{ session('success') }}");
    </script>
@endif

@if(session('error'))
    <script>
        toastr.error("{{ session('error') }}");
    </script>
@endif

</body>
</html>
