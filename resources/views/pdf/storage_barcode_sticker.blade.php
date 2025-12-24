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

        .page {
            width: 58mm;
            height: 40mm;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .container {
            padding: 0 5px; /* или укажи в px, например: 20px */
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
            font-size: 8px;
            line-height: 0.8;
            text-align: right;
            position: absolute;
            bottom: 5px;
            right: 5px;
        }
    </style>
</head>
<body>

@foreach($items as $item)
    <div class="page">
        <div class="container">
            <div class="barcode">
                {!! DNS1D::getBarcodeHTML($item->storage_barcode, 'C128', 1.8, 85) !!}
            </div>

            <div class="code-label">{{ $item->storage_barcode }}</div>
            <div class="code-text">Тюль {{ $item->item->title }}
                <span
                    style="float:right;">{{ $item->shelf->title ?? '' }}</span>
            </div>
            <div class="code-text">ширина {{ $item->item->width }}</div>
            <div class="code-text">высота {{ $item->item->height }}
                <span style="float:right;">{{ $item->seamstress->name }}</span>
            </div>
        </div>

        <div class="code-text2">{{ $item->cutter->name ?? '' }}</div>
    </div>
    @if (!$loop->last)
        <div style="page-break-after: always;"></div>
    @endif
@endforeach
</body>
</html>
