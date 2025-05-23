<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
{{--        <icon rel="icon" type="image/png" sizes="16x16" href="{{ asset('vendor/adminlte/dist/img/crm_logo.png') }}">--}}

        <link rel="shortcut icon" href="{{ asset('vendor/adminlte/dist/img/crm_logo.png') }}">
        <title>МЕГАТЮЛЬ | {{ $title }}</title>

        <link rel="stylesheet" href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
        <link rel="stylesheet" href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">

        <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

        <style>
            .wrapper {
                background-color: #f4f6f9;
            }

            .square-btn {
                width: 50px !important;
                height: 50px !important;
                padding: 0;
                font-size: 1rem;
            }
        </style>
    </head>
    <body>

        <div class="wrapper" style="min-height: 100vh;">
            <div class="content">
                <div class="container-fluid">
                    <div class="card" style="top: 50px;">
                        <div class="row mt-3 ml-3 mr-1">
                            <div class="form-group col-md-1">
                                <a class="btn btn-primary" href="{{ route('sticker_printing') }}">Обновить</a>
                            </div>

                            <div class="form-group col-md-3">
                                <select name="seamstress_id"
                                        id="seamstress_id"
                                        class="form-control"
                                        onchange="updatePageWithQueryParam(this)"
                                        required>
                                    <option value="" selected>Все</option>
                                    @foreach($seamstresses as $seamstress)
                                        <option value="{{ $seamstress->id }}"
                                                @if(request('seamstress_id') == $seamstress->id) selected @endif
                                        >{{ $seamstress->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card" style="top: 50px;">
                        <div class="card-body">

                            <div class="table-responsive">
                                <table class="table table-hover table-bordered">
                                    <thead class="thead-dark">
                                    <tr>
                                        <th style="text-align: center" scope="col"></th>
                                        <th style="text-align: center" scope="col">Номер заказа</th>
                                        <th style="text-align: center" scope="col">Товар</th>
                                        <th style="text-align: center" scope="col">Маркетплейс</th>
                                    </tr>
                                    </thead>
                                    <tbody>

                                    @foreach ($items as $item)
                                        <tr>
                                            <td>
                                                <div class="row ml-1">
                                                    <a href="{{ route('marketplace_api.barcode', ['marketplaceOrderId' => $item->marketplaceOrder->order_id]) }}"
                                                       class="btn btn-outline-secondary btn-lg mr-3 mb-1 d-flex align-items-center justify-content-center square-btn"
                                                       target="_blank">
                                                        <i class="fas fa-barcode fa-2x"></i>
                                                    </a>

                                                    <form action="{{ route('marketplace_order_items.done', ['marketplace_order_item' => $item->id]) }}"
                                                          method="POST">
                                                        @csrf
                                                        @method('PUT')
                                                        <button type="submit" class="btn btn-success btn-lg mr-1 square-btn"
                                                                title="Выполнено"
                                                                onclick="return confirm('Вы уверены, что распечатали и наклеили стикер?')">
                                                            <i class="fas fa-check fa-lg"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                            <td style="text-align: center">{{ $item->marketplaceOrder->order_id }}</td>
                                            <td style="text-align: center">{{ $item->item->title }} - {{ $item->item->width / 100 }}.{{ $item->item->height }}</td>
                                            <td style="text-align: center">
                                                <img style="width: 80px;"
                                                     src="{{ asset($item->marketplaceOrder->marketplace_name) }}"
                                                     alt="{{ $item->marketplaceOrder->marketplace_name }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script src="{{ asset('js/PageQueryParam.js') }}"></script>
        <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.js') }}"></script>
        <script src="{{ asset('vendor/adminlte/dist/js/adminlte.js') }}"></script>
        <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>
    </body>
</html>
