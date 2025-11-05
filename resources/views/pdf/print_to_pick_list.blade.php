@php use App\Models\MarketplaceOrder; @endphp
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
    </style>
</head>
<body>

<h2>Список заказов на подбор</h2>

<table>
    <thead>
    <tr>
        <th>Заказ</th>
        <th>Товар</th>
        <th>Полки и количество</th>
    </tr>
    </thead>
    <tbody>
    @foreach($orders as $order)
        @php
            /** @var MarketplaceOrder $order */
            $data = $grouped[$order->id] ?? null;
        @endphp
        @if($data)
            <tr>
                <td>
                    {{ $order->order_id }}
                </td>
                <td style="font-size: 16px;">
                    <b>{{ $data['itemName'] }}</b>
                </td>
                <td>
                    @foreach($data['shelfStats'] as $stat)
                        <li>
                            {{ $stat->shelf->title }} — {{ $stat->quantity }}
                            шт.
                        </li>
                    @endforeach
                </td>
            </tr>
        @endif
    @endforeach
    </tbody>
</table>

</body>
</html>
