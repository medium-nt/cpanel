<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <link rel="shortcut icon" href="{{ asset('vendor/adminlte/dist/img/crm_logo.png') }}">
        <title>МЕГАТЮЛЬ | {{ $title }}</title>

        <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">

        <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

        <style>
            .wrapper {
                background-color: #f4f6f9;
            }
            .td_style {
                text-align: center;
                vertical-align: middle;
                font-size: 30px;
            }
        </style>
    </head>
    <body>

    <div class="wrapper" style="min-height: 100vh;">
            <div class="content">
                <div class="container-fluid">
                    <div class="card" style="top: 10px;">
                        <div class="row mt-3 ml-3 mr-1">
                            <div class="form-group col-md-1 mr-5">
                                <a class="btn btn-primary btn-lg" href="{{ route('sticker_printing') }}">Обновить</a>
                            </div>
                            <div class="form-group col-md-1">
                                <a class="btn btn-outline-primary btn-lg" href="#" onclick="window.location.reload()">
                                    <i class="fas fa-sync"></i>
                                </a>
                            </div>

                            <div class="form-group col-md-3 ml-3">
                                <select name="seamstress_id"
                                        id="seamstress_id"
                                        class="form-control form-control-lg"
                                        onchange="updatePageWithQueryParam(this)"
                                        required>
                                    <option value="" selected disabled>Выберите швею</option>
{{--                                    <option value="3" @if(request('seamstress_id') == 3) selected @endif>Тестовая</option>--}}
                                    @foreach($seamstresses as $seamstress)
                                        <option value="{{ $seamstress->id }}"
                                                @if(request('seamstress_id') == $seamstress->id) selected @endif
                                        >{{ $seamstress->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-md-3 ml-5">
                                <select name="marketplace_id"
                                        id="marketplace_id"
                                        class="form-control form-control-lg"
                                        onchange="updatePageWithQueryParam(this)"
                                        required>
                                    <option value="" selected disabled>Выбрать маркетплейс</option>
                                    <option value="1" @if(request()->get('marketplace_id') == 1) selected @endif>OZON</option>
                                    <option value="2" @if(request()->get('marketplace_id') == 2) selected @endif>WB</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    @if($items->isNotEmpty() || $seamstressId != 0)
                    <div class="card" style="top: 10px;">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover table-bordered">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th style="text-align: center; width: 80px" scope="col"></th>
                                            <th style="text-align: center" scope="col">Номер заказа</th>
                                            <th style="text-align: center" scope="col">Товар</th>
                                            <th style="text-align: center" scope="col">Маркетплейс</th>
                                            <th style="text-align: center; width: 80px" scope="col"></th>
                                        </tr>
                                    </thead>
                                    <tbody>

                                    @foreach ($items as $item)
                                        @php
                                            $isPrinted = $item->marketplaceOrder->is_printed;
                                            $orderId = $item->marketplaceOrder->order_id;
                                            $fulfillmentType = $item->marketplaceOrder->fulfillment_type;
                                        @endphp
                                        <script>
                                            $(document).ready(function() {
                                                $("#print_{{ $orderId }}").click(function(e) {
                                                    localStorage.setItem('orderId', '{{ $orderId }}');
                                                    $("#submit_{{ $orderId }}").show();
                                                    $(this).removeClass('btn-outline-secondary').addClass('btn-outline-danger');
                                                });

                                                if (localStorage.getItem('orderId') === '{{ $orderId }}') {
                                                    $("#submit_{{ $orderId }}").css("display", "block").attr("style", function(i, style) {
                                                        return style.replace(/display:\s*none!important;/, '');
                                                    });
                                                    localStorage.removeItem('orderId');
                                                }
                                            });
                                        </script>

                                        <tr>
                                            <td>
                                                @if($fulfillmentType === 'FBS')
                                                <a href="{{ route('marketplace_api.barcode', ['marketplaceOrderId' => $orderId]) }}"
                                                   class="btn btn-lg mx-5 d-flex align-items-center justify-content-center
                                                   @if($isPrinted) btn-outline-danger @else btn-outline-secondary @endif "
                                                   id="print_{{ $orderId }}">
                                                    <i class="fas fa-barcode fa-2x"></i>
                                                </a>
                                                @endif

                                                @if($fulfillmentType === 'FBO')
                                                    <a href="{{ route('marketplace_api.fbo_barcode', ['marketplaceOrderId' => $orderId]) }}"
                                                       class="btn btn-lg mx-5 d-flex align-items-center justify-content-center
                                                @if($isPrinted) btn-outline-danger @else btn-outline-secondary @endif "
                                                       id="print_{{ $orderId }}">
                                                        <i class="fas fa-barcode fa-2x"></i>
                                                    </a>
                                                @endif
                                            </td>

                                            <td class="td_style">
                                                {{ $orderId }}
                                            </td>
                                            <td class="td_style">
                                                {{ $item->item->title }} - {{ $item->item->width / 100 }} х {{ $item->item->height }}
                                            </td>
                                            <td class="td_style">
                                                <img style="width: 80px;"
                                                     src="{{ asset($item->marketplaceOrder->marketplace_name) }}"
                                                     alt="{{ $item->marketplaceOrder->marketplace_name }}">
                                            </td>
                                            <td class="td_style">
                                                <form action="{{ route('marketplace_order_items.done', ['marketplace_order_item' => $item->id]) }}"
                                                      method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-success ml-auto"
                                                            style=" @if(!$isPrinted) display:none !important;" @endif
                                                            id="submit_{{ $orderId }}"
                                                            onclick="return confirm('Вы уверены, что распечатали и наклеили стикер?')">
                                                        <i class="fas fa-check fa-2x"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="card" style="top: 10px;">
                        <div class="card-header">
                            Динамика преобладания большими размерами
                            <i class="fas fa-info-circle ml-1" onclick="toggleSpoiler()"></i>

                            @if($days_ago > 7)
                                <a href="{{ route('sticker_printing', ['days_ago' => max(0, $days_ago - 7)]) }}"
                                   class="btn btn-default btn-sm float-right">
                                    Вперед
                                    <i class="fa fa-arrow-right"></i>
                                </a>
                            @endif

                            @if($days_ago != 0)
                                <a href="{{ route('sticker_printing') }}"
                                   class="btn btn-default btn-sm float-right mr-2">
                                    <i class="fa fa-dot-circle-o"></i>
                                    Сегодня
                                </a>
                            @endif

                            @if($days_ago < 28)
                                <a href="{{ route('sticker_printing', ['days_ago' => $days_ago + 7]) }}"
                                   class="btn btn-default btn-sm float-right mr-2">
                                    <i class="fa fa-arrow-left"></i>
                                    Назад
                                </a>
                            @endif
                        </div>

                        <div class="card-body">
                            <div id="spoilerText" style="display:none;">
                                <div class="callout callout-info">
                                    <h5><i class="icon fas fa-info mr-3 mb-1"></i> Индекс Доминирования</h5>
                                    <p>
                                        В нашей компании высоко ценится эффективность и оптимальное использование материалов при пошиве штор.
                                        Для объективной оценки работы швей и выявления лидеров по рациональному крою, мы разработали систему расчета
                                        "индекса доминирования размера".
                                        <br>
                                        Суть метода заключается в вычислении среднего метража, используемого сотрудником на одно изделие в течение
                                        рабочего дня. Общий метраж, сшитый швеей за день, делится на количество готовых штор. Результат демонстрирует
                                        средний размер шторы, с которым преимущественно работает данный сотрудник.
                                        <br><br>
                                        Например, швея, обработавшая 80 метров ткани и изготовившая 15 штор, имеет индекс 5.33 (80/15 = 5.33).
                                        В то же время, другая швея, также обработавшая 80 метров, но изготовившая 25 штор, имеет индекс 3.2.
                                        <br>
                                        Очевидно, что вторая швея в среднем шьет шторы меньшего размера, чем первая. Более высокий индекс указывает
                                        на то, что швея чаще работает с крупными заказами, требующими большей ширины ткани (от 5 до 8 метров).
                                        <br>
                                        Низкий индекс, напротив, свидетельствует о преобладании в работе небольших заказов. Эта система позволяет не
                                        только выявлять швей, успешно справляющихся с крупными размерами, но и анализировать общую структуру заказов,
                                        выявлять тенденции и оптимизировать производственный процесс для достижения максимальной эффективности.
                                    </p>
                                </div>
                            </div>

                            <div style="width:100%; margin: auto;">
                                <canvas id="ratingGraph" style="width: 100%; height: 400px"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="{{ asset('js/toggle_spoiler.js') }}"></script>
        <script src="{{ asset('js/PageQueryParam.js') }}"></script>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            $(document).ready(function() {
                localStorage.removeItem('orderId');
            });

            window.seamstressesData = {!! $seamstressesJson !!};
            window.dates = {!! $dates !!};
        </script>
        <script src="{{ asset('js/ratingGraph.js') }}"></script>

        <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.js') }}"></script>
        <script src="{{ asset('vendor/adminlte/dist/js/adminlte.js') }}"></script>
        <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    </body>
</html>
