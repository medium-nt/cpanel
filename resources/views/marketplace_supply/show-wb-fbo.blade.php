@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-12">
        @if($supply->status == 0 && empty($supply->supply_id))
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Выбор поставки WB FBO</h3>
                </div>
                <div class="card-body">
                    @if(!empty($wbSupplies))
                        <form
                            action="{{ route('marketplace_supplies.link_wb_fbo', ['marketplace_supply' => $supply]) }}"
                            method="POST" id="link_wb_fbo_form">
                            @csrf
                            <div class="form-group">
                                <label for="wb_supply_select">Поставка из
                                    WB</label>
                                <select class="form-control"
                                        id="wb_supply_select"
                                        name="wb_supply_id" required>
                                    <option value="">-- Выберите поставку --
                                    </option>
                                    @foreach($wbSupplies as $wbSupply)
                                        <option
                                            value="{{ $wbSupply['supplyID'] }}">
                                            № {{ $wbSupply['supplyID'] }}
                                            от {{ \Carbon\Carbon::parse($wbSupply['createDate'])->format('d.m.Y H:i') }}
                                            (дата
                                            поставки: {{ \Carbon\Carbon::parse($wbSupply['supplyDate'])->format('d.m.Y') }}
                                            )
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                Выбрать
                            </button>
                        </form>
                    @else
                        <p class="text-muted">Нет доступных поставок из WB.</p>
                    @endif
                </div>
            </div>
        @else
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Данные поставки WB FBO</h3>
                </div>
                <div class="card-body">
                    <table class="table mb-3">
                        <tr>
                            <th>Номер поставки</th>
                            <td>{{ $supply->supply_id }}</td>
                        </tr>
                        <tr>
                            <th>Кластер (склад)</th>
                            <td>{{ $supply->cluster }}</td>
                        </tr>
                        <tr>
                            <th>Дата поставки</th>
                            <td>{{ $supply->supply_date?->format('d.m.Y') }}</td>
                        </tr>
                        <tr>
                            <th>ID отгрузки в Газельку</th>
                            <td>{{ $supply->gazelka_shipment_id ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th>Дата отгрузки в Газельку</th>
                            <td>{{ $supply->gazelka_shipment_date?->format('d.m.Y') ?? '-' }}</td>
                        </tr>
                    </table>

                    @if(auth()->user()->isAdmin() || auth()->user()->isManager())
                        <a href="{{ route('marketplace_supplies.edit_wb_fbo', ['marketplace_supply' => $supply]) }}"
                           class="btn btn-outline-primary ml-2 mb-2">
                            Редактировать
                        </a>
                    @endif

                    @if($hasOrders && (auth()->user()->isAdmin() || auth()->user()->isStorekeeper()))
                        <a href="{{ route('supply_boxes.index', ['marketplace_supply' => $supply]) }}"
                           class="btn btn-success ml-2 mb-2">
                            Управление коробами
                        </a>
                    @endif

                    @if($canExportExcel)
                        <a href="{{ route('supply_boxes.export_excel', ['marketplace_supply' => $supply]) }}"
                           class="btn btn-warning ml-2 mb-2">
                            Скачать Excel с коробами
                        </a>
                    @endif

                    @if($supply->sticker)
                        @can('downloadSticker', $supply)
                            <a href="{{ route('marketplace_supplies.download_sticker', $supply) }}"
                               class="btn btn-info ml-2 mb-2">
                                Скачать стикер пропуска
                            </a>
                        @endcan
                        @can('manageSticker', $supply)
                            <a href="{{ route('marketplace_supplies.delete_sticker', $supply) }}"
                               class="btn btn-danger ml-2 mb-2"
                               onclick="return confirm('Удалить стикер пропуска?')">
                                Удалить стикер
                            </a>
                        @endcan
                    @else
                        @can('manageSticker', $supply)
                            <div class="border rounded p-3 mt-3">
                                <strong>Стикер пропуска</strong>
                                <form
                                    action="{{ route('marketplace_supplies.upload_sticker', $supply) }}"
                                    method="POST" enctype="multipart/form-data"
                                    class="d-inline ml-3">
                                    @csrf
                                    <input type="file" name="sticker"
                                           accept=".pdf" required>
                                    <button type="submit"
                                            class="btn btn-outline-info">
                                        Загрузить
                                    </button>
                                </form>
                            </div>
                        @endcan
                    @endif

                    @if($supply->status == 0 && !empty($supply->supply_id) && !$hasOrders && empty($supplyGoods) && (auth()->user()->isAdmin() || auth()->user()->isManager()))
                        <a href="{{ route('marketplace_supplies.load_fbo_goods', ['marketplace_supply' => $supply]) }}"
                           class="btn btn-primary ml-2 mb-2">
                            Загрузить товарный состав
                        </a>
                    @endif
                </div>
            </div>

            @if(!$hasOrders && !empty($supplyGoods))
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Товарный состав</h3>
                    </div>
                    <div class="card-body">
                        <a href="{{ route('marketplace_supplies.load_fbo_goods', ['marketplace_supply' => $supply]) }}"
                           class="btn btn-outline-primary mb-3">
                            Обновить товарный состав
                        </a>

                        @if($allItemsFound ?? false)
                            <form
                                action="{{ route('marketplace_supplies.confirm_fbo_goods', ['marketplace_supply' => $supply]) }}"
                                method="POST" id="confirm_fbo_form"
                                class="d-inline">
                                @csrf
                                <button type="submit"
                                        class="btn btn-success mb-3">
                                    Сформировать поставку
                                </button>
                            </form>
                        @endif

                        <table class="table table-hover table-bordered">
                            <thead class="thead-dark">
                            <tr>
                                <th>Артикул</th>
                                <th>Товар</th>
                                <th>Кол-во</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($supplyGoods as $good)
                                <tr>
                                    <td>{{ $good['vendorCode'] }}</td>
                                    <td>{{ $good['name'] }}</td>
                                    <td>{{ $good['quantity'] }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if($hasOrders)
                <div class="card">
                    <div class="card-header cursor-pointer"
                         data-toggle="collapse" data-target="#orders-collapse">
                        <h3 class="card-title">Заказы
                            ({{ $supplyOrders->count() }})</h3>
                    </div>
                    <div id="orders-collapse" class="collapse">
                        <div class="card-body">
                            <div class="table-responsive">
                            <table class="table table-hover table-bordered">
                                <thead class="thead-dark">
                                <tr>
                                    <th>Заказ</th>
                                    <th>Товар</th>
                                    <th>Статус</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($supplyOrders as $order)
                                    @foreach($order->items as $item)
                                        <tr>
                                            <td>{{ $order->order_id }}</td>
                                            <td>{{ $item->item?->title ?? '-' }} {{ $item->item?->width }}
                                                x{{ $item->item?->height }}</td>
                                            <td>
                                                @if($order->box_id)
                                                    <a href="{{ route('supply_boxes.show', ['marketplace_supply' => $supply, 'box' => $order->box_id]) }}"
                                                       class="badge badge-info text-white">
                                                        Короб {{ $order->box->number }}
                                                    </a>
                                                @else
                                                    <span
                                                        class="badge {{ $order->status_color }}">{{ $order->status_name }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                                </tbody>
                            </table>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
@stop

@section('js')
    <script>
        document.getElementById('link_wb_fbo_form')?.addEventListener('submit', function (e) {
            if (!confirm('Вы уверены, что хотите выбрать эту поставку?')) {
                e.preventDefault();
            }
        });

        document.getElementById('confirm_fbo_form')?.addEventListener('submit', function (e) {
            if (!confirm('Сформировать поставку? Будут созданы заказы на основе товарного состава.')) {
                e.preventDefault();
            }
        });
    </script>
@stop

@push('css')
    <style>
        .cursor-pointer {
            cursor: pointer;
        }

        .cursor-pointer:hover {
            background-color: #f0f0f0;
        }
    </style>
@endpush
