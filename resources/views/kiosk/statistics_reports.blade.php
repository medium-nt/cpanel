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

            <div class="card" style="top: 10px;">
                <div class="card-body">
                    <h2>Статистика/Отчеты</h2>

                    <div class="mt-3">
                        Динамика преобладания большими размерами
                        <i class="fas fa-info-circle ml-1"
                           onclick="toggleSpoiler()"></i>

                        @if($days_ago > 7)
                            <a href="{{ route('statistics_reports', ['days_ago' => max(0, $days_ago - 7)]) }}"
                               class="btn btn-default btn-sm float-right">
                                Вперед
                                <i class="fa fa-arrow-right"></i>
                            </a>
                        @endif

                        @if($days_ago != 0)
                            <a href="{{ route('statistics_reports') }}"
                               class="btn btn-default btn-sm float-right mr-2">
                                <i class="fa fa-dot-circle-o"></i>
                                Сегодня
                            </a>
                        @endif

                        @if($days_ago < 28)
                            <a href="{{ route('statistics_reports', ['days_ago' => $days_ago + 7]) }}"
                               class="btn btn-default btn-sm float-right mr-2">
                                <i class="fa fa-arrow-left"></i>
                                Назад
                            </a>
                        @endif
                    </div>

                    <div class="card-body">
                        <div id="spoilerText" style="display:none;">
                            <div class="callout callout-info">
                                <h5><i class="icon fas fa-info mr-3 mb-1"></i>
                                    Индекс Доминирования</h5>
                                <p>
                                    В нашей компании высоко ценится
                                    эффективность и оптимальное использование
                                    материалов при пошиве штор.
                                    Для объективной оценки работы швей и
                                    выявления лидеров по рациональному крою, мы
                                    разработали систему расчета
                                    "индекса доминирования размера".
                                    <br>
                                    Суть метода заключается в вычислении
                                    среднего метража, используемого сотрудником
                                    на одно изделие в течение
                                    рабочего дня. Общий метраж, сшитый швеей за
                                    день, делится на количество готовых штор.
                                    Результат демонстрирует
                                    средний размер шторы, с которым
                                    преимущественно работает данный сотрудник.
                                    <br><br>
                                    Например, швея, обработавшая 80 метров ткани
                                    и изготовившая 15 штор, имеет индекс 5.33
                                    (80/15 = 5.33).
                                    В то же время, другая швея, также
                                    обработавшая 80 метров, но изготовившая 25
                                    штор, имеет индекс 3.2.
                                    <br>
                                    Очевидно, что вторая швея в среднем шьет
                                    шторы меньшего размера, чем первая. Более
                                    высокий индекс указывает
                                    на то, что швея чаще работает с крупными
                                    заказами, требующими большей ширины ткани
                                    (от 5 до 8 метров).
                                    <br>
                                    Низкий индекс, напротив, свидетельствует о
                                    преобладании в работе небольших заказов. Эта
                                    система позволяет не
                                    только выявлять швей, успешно справляющихся
                                    с крупными размерами, но и анализировать
                                    общую структуру заказов,
                                    выявлять тенденции и оптимизировать
                                    производственный процесс для достижения
                                    максимальной эффективности.
                                </p>
                            </div>
                        </div>

                        <div style="width:100%; margin: auto;">
                            <canvas id="ratingGraph"
                                    style="width: 100%; height: 400px"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<x-idle-modal-component/>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.js') }}"></script>
<script src="{{ asset('vendor/adminlte/dist/js/adminlte.js') }}"></script>
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('js/toggle_spoiler.js') }}"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(document).ready(function () {
        localStorage.removeItem('orderId');
    });

    window.seamstressesData = {!! $seamstressesJson !!};
    window.dates = {!! $dates !!};
</script>
<script src="{{ asset('js/ratingGraph.js') }}"></script>

</body>
</html>
