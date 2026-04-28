<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>WB Sticker PDF</title>
    <style>
        @page {
            size: 58mm 40mm;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 4px;
            width: 58mm;
            height: 40mm;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            box-sizing: border-box;
            position: relative;
        }

        .wb-corner {
            position: absolute;
            top: 1px;
            left: 2px;
            font-size: 10px;
            font-weight: bold;
            z-index: 10;
        }

        .sticker {
            display: flex;
            flex-direction: column;
            page-break-after: always;
        }

        .sticker:last-child {
            page-break-after: avoid;
        }

        .barcode-container {
            text-align: center;
            margin-bottom: 2px;
            margin-left: 30px;
        }

        .sku-number {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 0.8;
            margin-left: 60px;
        }

        .title {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 0.8;
            margin-left: 70px;
        }

        .title-row {
            position: relative;
            margin-bottom: 5px;
        }

        .title-row img {
            position: absolute;
            right: 15px;
            top: 1px;
        }

        .text {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin-bottom: 2px;
            line-height: 0.8;
        }

        .footer {
            font-family: DejaVu Sans, sans-serif;
            font-size: 6px;
            margin-right: 5px;
            margin-top: 3px;
            line-height: 0.8;
        }
    </style>
</head>
<body>
@foreach($stickers as $s)
<div class="sticker">
    <div class="wb-corner">WB</div>

    <div class="barcode-container">
        {!! DNS1D::getBarcodeHTML($s['barcode'], 'C128', 1.2, 40) !!}
    </div>
    <div class="sku-number">{{ $s['barcode'] }}</div>

    <div class="title-row">
        <span class="title">ИП Левкин</span>
        <img
            src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('icons/eac.jpg'))) }}"
            alt="EAC" width="25" height="25">
    </div>

    <div class="text">ТЮЛЬ {{ Str::upper($s['item']->title) }}</div>
    <div class="text">Артикул: {{ $s['article'] }}</div>
    <div class="text">Цвет: {{ $s['color'] }} / Размер:
        <b>
            {{ $s['item']->width }} x {{ $s['item']->height }}
        </b>
    </div>
    <div class="text">Страна: {{ $s['country'] }}
        <span
            style="float:right; margin-right: 5px; font-weight: bold; font-size: {{ $s['fontSizeCluster'] }}px">
            {{ $s['order']->cluster }}
        </span>
    </div>
    <div class="text">Бренд: МЕГАТЮЛЬ
    </div>

    <div class="footer">
        <span style="font-weight: bold; font-size: 8px">
            {{ $s['order']->order_id }}
        </span>

        <span style="float:right; margin-top: 2px;">
            <b>
                @isset($s['cutterId'])
                    закройщик № {{ $s['cutterId'] }} |
                @endisset
                швея № {{ $s['seamstressId'] ?? '0' }}
            </b>
        </span>
    </div>
</div>
@endforeach
</body>
</html>
