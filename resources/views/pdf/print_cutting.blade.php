<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title></title>
    <style>
        @page {
            margin: 5mm;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        h2 {
            margin: 20px 0 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            border: 1px solid #000;
            padding: 1px;
            text-align: center;
        }

        .dimensions {
            font-size: 10px;
            width: 45%;
        }

        .dimensions-span {
            font-weight: bold;
            font-size: 16px;
        }

        .col-cutting {
            width: 5%;
            border-right: 1px solid #000 !important;
        }

        .col-hidden {
            width: 50%;
            border: none !important;
            background: transparent !important;
        }
    </style>
</head>
<body>

<table>
    <thead>
    <tr>
        <th class="dimensions">{{ auth()->user()->name }}</th>
        <th class="col-cutting">{{ now()->format('d/m/Y') }}</th>
        <th class="col-hidden"></th>
    </tr>
    </thead>
</table>

@foreach($orders as $material => $items)
    <table>
        <thead>
        {{--        <tr>--}}
        {{--            <th сlass="col-item">Заказ</th>--}}
        {{--            <th class="col-cutting">Накроено</th>--}}
        {{--            <th сlass="col-item">Заказ</th>--}}
        {{--            <th class="col-cutting">Накроено</th>--}}
        {{--        </tr>--}}
        </thead>
        <tbody>
        @foreach($items->chunk(2) as $pair)
            <tr>
                @foreach($pair as $item)
                    <td class="dimensions">
                        <span class="dimensions-span">
                        {{ $material }}
                        {{ $item->item->width }} × {{ $item->item->height }}<br>
                        </span>
                        {{ $item->marketplaceOrder->MarketplaceTitle }}
                        {{ $item->marketplaceOrder->order_id }}
                    </td>
                    <td class="col-cutting"></td>
                @endforeach

                @if($pair->count() < 2)
                    {{-- Если только один элемент в строке, добавим пустую ячейку --}}
                    <td class="dimensions"></td>
                    <td class="col-cutting"></td>
                @endif
            </tr>
        @endforeach
        </tbody>
    </table>
@endforeach

</body>
</html>
