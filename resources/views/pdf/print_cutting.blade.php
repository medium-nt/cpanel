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
            padding: 5px;
            text-align: center;
        }

        .dimensions {
            font-weight: bold;
            font-size: 14px;
        }

        .label {
            font-weight: bold;
            font-size: 34px;
        }

        .page-break {
            page-break-after: always;
        }

        /* Ширина колонок */
        .col-item {
            width: 35%;
        }

        .col-cutting {
            width: 15%;
            border-right: 1px solid #000 !important;
        }

        .col-label {
            width: 50%;
        }

        .col-hidden {
            border: none !important;
            background: transparent !important;
        }
    </style>
</head>
<body>

@foreach($orders as $material => $items)
    <h2>{{ $material }}: {{ $items->count() }} шт.</h2>

    <table>
        <thead>
        <tr>
            <th class="col-item">{{ auth()->user()->name }}</th>
            <th class="col-cutting">{{ now()->format('d/m/Y') }}</th>
            <th class="col-label col-hidden"></th>
        </tr>
        </thead>
    </table>

    <table>
        <thead>
        <tr>
            <th сlass="col-item">Заказ</th>
            <th class="col-cutting">Накроено</th>
            <th class="col-label">На товар</th>
        </tr>
        </thead>
        <tbody>
        @foreach($items as $item)
            <tr>
                <td class="dimensions">
                    {{ $item->item->width }} × {{ $item->item->height }}
                    <br><br>
                    {{ $item->marketplaceOrder->MarketplaceTitle }}<br>
                    ({{ $item->marketplaceOrder->order_id }})
                </td>
                <td></td>
                <td class="label">
                    {{ $item->item->title }}<br>
                    {{ $item->item->width }} × {{ $item->item->height }} <br>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @if(!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach

</body>
</html>
