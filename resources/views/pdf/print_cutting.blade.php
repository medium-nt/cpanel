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

        .page {
            page-break-before: always;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .page:first-child {
            page-break-before: avoid;
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

        .col-qr {
            width: 15%;
            border: 1px solid #000 !important;
            text-align: center;
            vertical-align: middle;
        }

        /* Вторая страница - крупные ячейки для разрезки */
        .page-large .dimensions {
            font-size: 20px;
        }

        .page-large .dimensions-span {
            font-size: 26px;
        }

        .page-large td {
            height: 80px;
        }

        .qr-container {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 5px;
            justify-content: flex-start;
        }

        .qr-container > div:last-child {
            flex: 1;
        }

        .page-large .qr-container .dimensions-span {
            font-size: 24px;
        }
    </style>
</head>
<body>

<div class="page">
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
</div>

<div class="page page-large">
    @foreach($orders as $material => $items)
        <table>
            <tbody>
            @foreach($items->chunk(2) as $pair)
                <tr>
                    @foreach($pair as $item)
                        @if($printQr)
                            <td class="col-qr">
                                @if(extension_loaded('imagick'))
                                    @php
                                        $qrData = QrCode::format('png')->size(60)->generate($item->marketplaceOrder->order_id);
                                    @endphp
                                    <img
                                        src="data:image/png;base64,{{ base64_encode($qrData) }}"
                                        alt="QR" width="60" height="60">
                                @else
                                    {{ $item->marketplaceOrder->order_id }}
                                @endif
                            </td>
                        @endif
                        <td class="dimensions">
                            <span class="dimensions-span">
                                {{ $material }}
                                {{ $item->item->width }} × {{ $item->item->height }}<br>
                            </span>
                            {{ $item->marketplaceOrder->MarketplaceTitle }}
                            {{ $item->marketplaceOrder->order_id }}
                        </td>
                    @endforeach

                    @if($pair->count() < 2)
                        {{-- Если только один элемент в строке, добавим пустую ячейку --}}
                        @if($printQr)
                        <td class="col-qr"></td>
                        @endif
                        <td class="dimensions"></td>
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>
    @endforeach
</div>
</body>
</html>
