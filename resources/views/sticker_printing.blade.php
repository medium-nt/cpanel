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

        <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

        <style>
            .wrapper {
                background-color: #f4f6f9;
            }
            .td_style {
                text-align: center;
                vertical-align: middle;
                font-size: 30px;
            }

            input.invisible-text {
                caret-color: #000;
            }
        </style>
    </head>
    <body>

    <div class="wrapper" style="min-height: 100vh;">
        <div class="content">
            <div class="container-fluid">
                <div class="card" style="top: 10px;">
                    <div class="row mt-3 ml-1 mr-1">
                        <a class="btn btn-link btn-xs"
                           href="{{ route('sticker_printing_test') }}">-</a>
                        <div class="form-group col-md-1 mr-5">
                            <a class="btn btn-primary btn-lg" href="{{ route('sticker_printing') }}">Обновить</a>
                        </div>
                        <div class="form-group col-md-1 ml-3">
                            <a class="btn btn-outline-primary btn-lg" href="#" onclick="window.location.reload()">
                                <i class="fas fa-sync"></i>
                            </a>
                        </div>

                        <div class="form-group col-md-2">
                            <a class="btn btn-outline-primary btn-lg" href="#" data-toggle="modal" data-target="#barcodeModal">
                                <i class="fas fa-barcode"></i>
                            </a>
                        </div>

                        <x-modal-scan-barcode-component/>

                        <div class="modal fade" id="barcodeModal2" tabindex="-1" role="dialog" aria-labelledby="barcodeModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <form id="barcodeForm" action="{{ route('sticker_printing') }}">
                                    <div class="modal-content">
                                        <div class="modal-body">
                                            <input type="text" style="color: white; caret-color: #000" class="form-control"
                                                   placeholder="тест выбора пользователя"
                                                   id="barcodeInput2" name="barcode" autocomplete="off" autofocus>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="modal fade" id="barcodeModal3" tabindex="-1" aria-labelledby="barcodeModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-body">
                                        <form id="barcodeForm" action="{{ route('sticker_printing') }}">
                                            <input type="text" name="barcode" id="barcodeInput3" class="form-control" placeholder="Штрихкод">
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <x-modal-work-shift-component :userId="$userId"/>

                        <div class="form-group col-md-2">
                            <select name="user_id"
                                    id="user_id"
                                    class="form-control form-control-lg"
                                    onchange="updatePageWithQueryParam(this)"
                                    required>
                                <option value="" selected disabled>Выберите
                                    сотрудника
                                </option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}"
                                            @if(request('user_id') == $user->id) selected @endif
                                    >{{ $user->short_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-2 ml-1">
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

                        <div class="form-group col-md-3">
                            @if($workShift)
                                @if(!$workShift['shift_is_open'])
                                <a class="btn btn-success btn-lg" href="#" data-toggle="modal" data-target="#workShiftModal">
                                    Открыть смену
                                </a>
                                @else
                                <div class="row">
                                    <div class="col-6">
                                        Начало смены: {{ Carbon\Carbon::parse($workShift['start'])->format('H:i') }} <br>
                                        Конец смены: {{ Carbon\Carbon::parse($workShift['end'])->format('H:i') }}
                                    </div>
                                    @if($workShift['end'] < Carbon\Carbon::now())
                                    <div class="col-6">
                                        <a class="btn btn-warning btn-lg" href="#" data-toggle="modal" data-target="#workShiftModal">
                                            Закрыть смену
                                        </a>
                                    </div>
                                    @endif
                                </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                @if($items->isNotEmpty() || $userId != 0)
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

                                <iframe id="printFrame"
                                        style="display:none"></iframe>

                                @foreach ($items as $item)
                                    @php
                                        $isPrinted = $item->marketplaceOrder->is_printed;
                                        $orderId = $item->marketplaceOrder->order_id;
                                        $fulfillmentType = $item->marketplaceOrder->fulfillment_type;
                                        $partBtoWB = $item->marketplaceOrder->part_b ? "({$item->marketplaceOrder->part_b})" : '';

                                        $route = match ($fulfillmentType) {
                                            'FBO' => 'fbo_barcode_html',
                                            'FBS' => 'barcode',
                                        }
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
                                            {{--                                            <button--}}
                                            {{--                                                onclick="printBarcode('{{ $route }}' ,'{{ $item->marketplaceOrder->order_id }}')"--}}
                                            {{--                                                class="btn btn-xs mx-5 d-flex align-items-center justify-content-center--}}
                                            {{--                                                    @if($isPrinted) btn-outline-danger @else btn-outline-secondary @endif "--}}
                                            {{--                                                id="print_{{ $orderId }}">--}}
                                            {{--                                                --}}{{--                                                <i class="fas fa-barcode fa-xl"></i>--}}
                                            {{--                                            </button>--}}

                                        @if($fulfillmentType === 'FBS')
                                            <a href="{{ route('marketplace_api.barcode', ['marketplaceOrderId' => $orderId]) }}"
                                               class="btn btn-lg mx-5 d-flex align-items-center justify-content-center
                                               @if($isPrinted) btn-outline-danger @else btn-outline-secondary @endif "
                                               id="print_{{ $orderId }}">
                                                <i class="fas fa-barcode fa-2x"></i>
                                            </a>
                                            @endif

                                            @if($fulfillmentType === 'FBO')
                                                <a href="{{ route('marketplace_api.fbo_barcode_html', ['marketplaceOrderId' => $orderId]) }}"
                                                   class="btn btn-xs mx-5 d-flex align-items-center justify-content-center
                                               @if($isPrinted) btn-outline-danger @else btn-outline-secondary @endif "
                                                   id="print_{{ $orderId }}">
                                                    <i class="fas fa-barcode fa-xl"></i>
                                                </a>

                                                <a href="{{ route('marketplace_api.fbo_barcode', ['marketplaceOrderId' => $orderId]) }}"
                                                   class="btn btn-lg mx-5 d-flex align-items-center justify-content-center
                                               @if($isPrinted) btn-outline-danger @else btn-outline-secondary @endif "
                                                   id="print_{{ $orderId }}">
                                                    <i class="fas fa-barcode fa-2x"></i>
                                                </a>
                                            @endif
                                        </td>

                                        <td class="td_style">
                                            {{ $orderId }} <b>{{ $partBtoWB }}</b>
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

    <script>
        function printBarcode(link, orderId) {
            const iframe = document.getElementById('printFrame');

            iframe.onload = () => {
                try {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                } catch (e) {
                    console.error(e);
                }
            };

            const BASE_URL = "{{ request()->getSchemeAndHttpHost() }}";

            const url = `${BASE_URL}/${link}/?marketplaceOrderId=${orderId}`;
            console.log('iframe src =', url);

            iframe.src = url;
        }
    </script>

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

    <!-- Скрипт фокусировки и inert -->
    <script>
        // const mainContent = document.getElementById('mainContent');
        // const input = document.getElementById('barcodeInput3');
        //
        // $('#barcodeModal').on('shown.bs.modal', function () {
        //     mainContent.setAttribute('inert', '');
        //     input.value = '';
        //     setTimeout(() => input.focus(), 100);
        // });
        //
        // $('#barcodeModal').on('hidden.bs.modal', function () {
        //     mainContent.removeAttribute('inert');
        // });
    </script>

    <script>
        function setupModalFocus(modalId, inputId) {
            const input = document.getElementById(inputId);

            function enforceFocus(e) {
                if (!input.contains(e.target)) {
                    input.focus();
                }
            }

            let modal_id = $(`#${modalId}`);

            modal_id.on('shown.bs.modal', function () {
                input.value = '';
                input.focus();
                document.addEventListener('focusin', enforceFocus);
            });

            modal_id.on('hidden.bs.modal', function () {
                document.removeEventListener('focusin', enforceFocus);
            });
        }

        setupModalFocus('barcodeModal', 'barcodeInput');
        // setupModalFocus2('barcodeModal2', 'barcodeInput2');
        setupModalFocus('workShiftModal', 'workShiftInput');

        function setupModalFocus2(modalId, inputId) {
            const input = document.getElementById(inputId);

            function enforceFocus(e) {
                if (e.target !== input) {
                    input?.focus();
                }
            }

            const modal = $(`#${modalId}`);

            modal.on('shown.bs.modal', function () {
                if (input) {
                    input.value = '';
                    setTimeout(() => input.focus(), 100);
                    document.addEventListener('focusin', enforceFocus);
                }
            });

            modal.on('hidden.bs.modal', function () {
                document.removeEventListener('focusin', enforceFocus);
            });
        }
    </script>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.js') }}"></script>
    <script src="{{ asset('vendor/adminlte/dist/js/adminlte.js') }}"></script>
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

    <script>
        <?php if (session('error')) { ?>
        toastr.error("<?= session('error') ?>");
        <?php } ?>
    </script>
    </body>
</html>
