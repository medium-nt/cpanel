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

        <link href="{{ asset('vendor/toastr/toastr.min.css') }}"
              rel="stylesheet">
        <script src="{{ asset('vendor/toastr/toastr.min.js') }}"></script>

        <link rel="stylesheet" href="{{ asset('css/kiosk.css') }}">

        <style>
            html, body {
                touch-action: pan-y;
            }
            .wrapper {
                background-color: #f4f6f9;
            }

            .content {
                overflow-y: auto;
            }

            .container-fluid {
                overflow-y: auto;
            }
            .td_style {
                text-align: center;
                vertical-align: middle;
                font-size: 30px;
            }

            input.invisible-text {
                caret-color: #000;
            }

            .table-loading-overlay {
                position: absolute;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(255, 255, 255, 0.8);
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 10;
            }

            .table-loading-spinner {
                width: 50px;
                height: 50px;
                border: 5px solid #f3f3f3;
                border-top: 5px solid #007bff;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                0% {
                    transform: rotate(0deg);
                }
                100% {
                    transform: rotate(360deg);
                }
            }
        </style>
    </head>
    <body>

    <div class="wrapper" style="min-height: 100vh;">
        <div class="content">
            <div class="container-fluid">
                <div class="card">
                    <div class="card-body">
                        <a href="{{ route('kiosk') }}"
                           class="btn-kiosk btn-lg btn-kiosk-blue">На
                            главную</a>
                    </div>
                </div>

                <x-confirm-reprint-modal/>

                @if($items->isNotEmpty() || $userId != 0)
                    <div class="card">
                    <div class="card-body">
                        <div class="row">
                            @if($canUseFilter)
                                <div class="form-group col-md-2">
                                    <select name="material"
                                            id="material"
                                            class="form-control form-control-lg"
                                            onchange="updatePageWithQueryParam(this)"
                                            required>
                                        <option value="" selected disabled>
                                            Материал
                                        </option>
                                        @foreach($materials as $material)
                                            <option
                                                value="{{ $material->title }}"
                                                @if(request()->get('material') == $material->title) selected @endif>
                                                {{ $material->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <select name="width"
                                            id="width"
                                            class="form-control form-control-lg"
                                            onchange="updatePageWithQueryParam(this)"
                                            required>
                                        <option value="" selected disabled>
                                            Ширина
                                        </option>
                                        @foreach($widths as $widthItem)
                                            <option
                                                value="{{ $widthItem->width }}"
                                                @if(request()->get('width') == $widthItem->width) selected @endif>
                                                {{ $widthItem->width }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group col-md-2">
                                    <select name="height"
                                            id="height"
                                            class="form-control form-control-lg"
                                            onchange="updatePageWithQueryParam(this)"
                                            required>
                                        <option value="" selected disabled>
                                            Высота
                                        </option>
                                        @foreach($heights as $heightItem)
                                            <option
                                                value="{{ $heightItem->height }}"
                                                @if(request()->get('height') == $heightItem->height) selected @endif>
                                                {{ $heightItem->height }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                @if(!$user->isSeamstress())
                                <div class="form-group col-md-2">
                                    <select name="seamstress_id"
                                            id="seamstress_id"
                                            class="form-control form-control-lg"
                                            onchange="updatePageWithQueryParam(this)"
                                            required>
                                        <option value="" selected disabled>
                                            Швея
                                        </option>
                                        @foreach($seamstresses as $seamstress)
                                            <option
                                                value="{{ $seamstress->id }}"
                                                @if(request('seamstress_id') == $seamstress->id) selected @endif
                                            >{{ $seamstress->short_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @endif
                                <div class="form-group col-md-2">
                                    <select name="marketplace_id"
                                            id="marketplace_id"
                                            class="form-control form-control-lg"
                                            onchange="updatePageWithQueryParam(this)"
                                            required>
                                        <option value="" selected disabled>
                                            Маркетплейс
                                        </option>
                                        <option value="1"
                                                @if(request()->get('marketplace_id') == 1) selected @endif>
                                            OZON
                                        </option>
                                        <option value="2"
                                                @if(request()->get('marketplace_id') == 2) selected @endif>
                                            WB
                                        </option>
                                    </select>
                                </div>
                            @endif

                            @if(request()->get('scan_order_id'))
                                <div class="form-group col-md-2">
                                    <input class="form-control form-control-lg"
                                           value="{{ request()->get('scan_order_id') }}"
                                           style="font-size: 14px;"
                                           type="text"
                                           readonly>
                                </div>
                            @endif
                        </div>

                        @if($isOtkOnShift && !$user->isOtk() && !$user->isStorekeeper() && !$user->isAdmin())
                            <div class="alert alert-danger">
                                Самостоятельная стикеровка запрещена, на смене
                                есть упаковщик!
                            </div>
                        @endif

                        <div class="table-responsive"
                             style="position: relative;">
                            <div class="table-loading-overlay"
                                 id="tableLoadingOverlay">
                                <div class="table-loading-spinner"></div>
                            </div>
                            <table class="table table-hover table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th style="text-align: center; width: 80px" scope="col"></th>
                                        <th style="text-align: center"
                                            scope="col">Швея
                                        </th>
                                        <th style="text-align: center" scope="col">Номер заказа</th>
                                        <th style="text-align: center" scope="col">Товар</th>
                                        <th style="text-align: center"
                                            scope="col">Кластер
                                        </th>
                                        <th style="text-align: center" scope="col">Маркетплейс</th>
                                        <th style="text-align: center; width: 80px" scope="col"></th>
                                    </tr>
                                </thead>
                                <tbody>

                                <iframe id="printFrame"
                                        style="display:none"></iframe>

                                @forelse ($items as $item)
                                    @php
                                        $isPrinted = $item->marketplaceOrder->is_printed;
                                        $orderId = $item->marketplaceOrder->order_id;
                                        $fulfillmentType = $item->marketplaceOrder->fulfillment_type;
                                        $partBtoWB = $item->marketplaceOrder->part_b ? "({$item->marketplaceOrder->part_b})" : '';

                                        $route = match ($fulfillmentType) {
                                            'FBO' => 'fbo_barcode',
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
                                            @if($item->status == 3)
                                                <span
                                                    class="text-danger text-bold d-flex align-items-center">
                                                    уже застикерован
                                                </span>
                                            @else
                                                @if($canSticking)
                                                    <button
                                                        onclick="printBarcode('/{{ $route }}?marketplaceOrderId={{ $item->marketplaceOrder->order_id }}', {{ $isPrinted ? 'true' : 'false' }}, this)"
                                                        class="btn btn-lg mr-2 d-flex align-items-center justify-content-center
                                                        @if($isPrinted) btn-outline-danger @else btn-outline-secondary @endif "
                                                        id="print_{{ $orderId }}">
                                                        <i class="fas fa-barcode fa-2x barcode-icon"></i>
                                                        <span
                                                            class="spinner-border spinner-border-sm d-none ms-0 print-spinner"
                                                            role="status"></span>
                                                    </button>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="td_style">
                                            {{ $item->seamstress->name }}
                                        </td>
                                        <td class="td_style">
                                            {{ $orderId }} <b>{{ $partBtoWB }}</b>
                                        </td>
                                        <td class="td_style">
                                            {{ $item->item->title }} - {{ $item->item->width / 100 }} х {{ $item->item->height }}
                                        </td>
                                        <td class="td_style">
                                            {{ $item->marketplaceOrder->cluster }}
                                        </td>
                                        <td class="td_style">
                                            <img style="width: 80px;"
                                                 src="{{ asset($item->marketplaceOrder->marketplace_name) }}"
                                                 alt="{{ $item->marketplaceOrder->marketplace_name }}">
                                        </td>
                                        <td class="td_style">
                                            @if($canSticking)
                                                @if($item->status == 5)
                                                    <form
                                                        action="{{ route('marketplace_order_items.done', ['marketplace_order_item' => $item->id]) }}"
                                                        method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit"
                                                                class="btn btn-success ml-auto"
                                                                style=" @if(!$isPrinted) display:none !important;"
                                                                @endif
                                                                id="submit_{{ $orderId }}"
                                                                onclick="return confirm('Вы уверены, что распечатали и наклеили стикер?')">
                                                            <i class="fas fa-check fa-2x"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    <span>---</span>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7"
                                            class="text-center td_style">
                                            Не найдено
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @else
                    <div class="alert alert-default-success text-center mt-3">
                        <h2>
                            Для отображения списка заказов, выберите сотрудника!
                        </h2>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal для предупреждения о неактивности -->
    <x-idle-modal-component/>

    <script src="{{ asset('js/toggle_spoiler.js') }}"></script>
    <script src="{{ asset('js/PageQueryParam.js') }}"></script>

    <script>
        $(document).ready(function() {
            localStorage.removeItem('orderId');

            setTimeout(function () {
                $('#tableLoadingOverlay').fadeOut(300);
            }, 500);
        });

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
        // setupModalFocus('workShiftModal', 'workShiftInput');

        <?php if (session('error')) { ?>
        toastr.error("<?= session('error') ?>");
        <?php } ?>
    </script>

    <script>
        const actionUrl = '{{ route('sticker_printing') }}';
        const userId = '{{ request()->get('user_id') }}';

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
                let url = actionUrl + '?scan_order_id=' + encodeURIComponent(buffer);
                if (userId) {
                    url += '&user_id=' + userId;
                }
                window.location.href = url;
                buffer = '';
            } else {
                buffer += e.key;
            }
        });
    </script>

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.js') }}"></script>
    <script src="{{ asset('vendor/adminlte/dist/js/adminlte.js') }}"></script>
    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    <script
        src="{{ asset('js/printBarcode.js') }}?v={{ filemtime(public_path('js/printBarcode.js')) }}"></script>

    </body>
</html>
