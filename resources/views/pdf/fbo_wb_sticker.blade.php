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
            margin-bottom: 6px;
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
    </style>
</head>
<body>
<div class="sticker">
    <div class="wb-corner">WB</div>

    <div class="barcode-container">
        {!! DNS1D::getBarcodeHTML($barcode, 'C128', 1.2, 40) !!}
    </div>
    <div class="sku-number">{{ $barcode }}</div>

    <div class="title-row">
        <span class="title">ИП Левкин</span>
        <img
            src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('icons/eac.jpg'))) }}"
            alt="EAC" width="25" height="25">
    </div>

    <div class="text">ТЮЛЬ {{ Str::upper($item->title) }}</div>
    <div class="text">Артикул: {{ $article }}</div>
    <div class="text">Цвет: {{ $color }} / Размер: {{ $item->width }}
        x{{ $item->height }}</div>
    <div class="text">Страна: {{ $country }}</div>
    <div class="text">Бренд: МЕГАТЮЛЬ</div>
</div>
</body>
</html>
