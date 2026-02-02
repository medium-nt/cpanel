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
    <div class="container">
        <div class="barcode">
            {!! DNS1D::getBarcodeHTML($barcode, 'C128', 1.5, 85) !!}
        </div>

        <div class="code-label">{{ $barcode }}</div>
        <div class="code-text">
            <div class="reason">Тюль {{ $item->title }}
                <span
                    style="float:right; font-size: {{ $fontSizeCluster }}px">
                    {{ $order->order_id }}
                </span>
            </div>
        </div>
        <div class="code-text">ширина {{ $item->width }}</div>
        <div class="code-text">
            <div class="reason">высота {{ $item->height }}
                <span
                    style="float:right; font-weight: bold; font-size: {{ $fontSizeCluster }}px">
                    {{ $order->cluster }}
                </span>
            </div>
        </div>

        <div class="code-text2"><b>
                @isset($cutterId)
                    закройщик № {{ $cutterId }} |
                @endisset
                швея № {{ $seamstressId }}</b>
        </div>
    </div>
</body>
</html>
