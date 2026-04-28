<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Barcode PDF</title>
    <style>
        @page {
            size: 58mm 40mm; /* Установка размера страницы */
            margin: 0; /* Убираем отступы */
        }

        body {
            margin: 0; /* Убираем отступы */
            display: flex;
            align-items: center;
            justify-content: center;
            width: 58mm; /* Ширина страницы */
            height: 40mm; /* Высота страницы */
        }

        .container {
            padding: 0 5px ; /* или укажи в px, например: 20px */
            text-align: center;
            box-sizing: border-box;
            page-break-after: always;
        }

        .container:last-child {
            page-break-after: avoid;
        }

        .barcode {
            max-width: 100%;
            height: auto;
            width: 100%;
            padding: 0 5px; /* немного отступов по краям, чтобы сканер видел quiet zone */
            box-sizing: border-box;
            text-align: center;
        }

        .code-label {
            font-family: Helvetica, sans-serif;
            font-size: 15px;
            text-align: left;
        }

        .code-text {
            font-family: DejaVu Sans, sans-serif;
            letter-spacing: -1.0px;
            font-size: 10px;
            text-align: left;
            line-height: 0.8;
        }

        .code-text2 {
            font-family: DejaVu Sans, sans-serif;
            letter-spacing: -1.0px;
            font-size: 7px;
            line-height: 0.8;
            text-align: right;
            position: absolute;
            bottom: 1px;
            right: 1px;
        }
    </style>
</head>
<body>
@foreach($stickers as $s)
    <div class="container">
        <div class="barcode">
            {!! DNS1D::getBarcodeHTML($s['barcode'], 'C128', 1.5, 85) !!}
        </div>

        <div class="code-label">{{ $s['barcode'] }}</div>
        <div class="code-text">
            <div class="reason">Тюль {{ $s['item']->title }}
                <span
                    style="float:right; font-size: {{ $s['fontSizeCluster'] }}px">
                    {{ $s['order']->order_id }}
                </span>
            </div>
        </div>
        <div class="code-text">ширина {{ $s['item']->width }}</div>
        <div class="code-text">
            <div class="reason">высота {{ $s['item']->height }}
                <span
                    style="float:right; font-weight: bold; font-size: {{ $s['fontSizeCluster'] }}px">
                    {{ $s['order']->cluster }}
                </span>
            </div>
        </div>

        <div class="code-text2"><b>
                @isset($s['cutterId'])
                    закройщик № {{ $s['cutterId'] }} |
                @endisset
                швея № {{ $s['seamstressId'] ?? '0' }}</b>
        </div>
    </div>
@endforeach
</body>
</html>
