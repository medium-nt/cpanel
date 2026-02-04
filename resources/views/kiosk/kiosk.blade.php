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
    <link
        href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css"
        rel="stylesheet">
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
                    @if($user->shift_is_open)
                        <a href="{{ route('sticker_printing', ['user_id' => $user->id]) }}"
                       class="btn-kiosk btn-kiosk-green">Печать
                        заказов</a>
                    <a href="{{ route('statistics_reports') }}"
                       class="btn-kiosk btn-kiosk-orange">Статистика /
                        Отчеты</a>
                        @if($user->isCutter() || $user->isSeamstress())
                            <a href=" {{ route('defects.create') }}"
                               class="btn-kiosk btn-kiosk-purple">Работа с
                                браком</a>
                        @endif
                        {{--                        <a href="#" class="btn-kiosk btn-kiosk-yellow">Работа с--}}
                        {{--                            рулонами</a>--}}
                        {{--                        <a href="#" class="btn-kiosk btn-kiosk-red">Работа с--}}
                        {{--                            возвратами</a>--}}
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
