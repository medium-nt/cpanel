<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FBO Barcode</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            width: 58mm;
            height: 40mm;
            overflow: hidden;
        }

        @page {
            size: 58mm 40mm;
            margin: 0;
        }

        body {
            background: white;
        }

        .sticker {
            width: 58mm;
            height: 40mm;
            padding: 0 5px;
            text-align: center;
            position: relative;
        }

        .barcode {
            max-width: 100%;
            height: auto;
            width: 100%;
            padding: 0 5px;
            box-sizing: border-box;
            text-align: center;
        }

        .code-label {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 15px;
            text-align: left;
            margin-top: 2px;
        }

        .code-text {
            font-family: Arial, sans-serif;
            letter-spacing: -1.0px;
            font-size: 10px;
            text-align: left;
            line-height: 1.2;
        }

        .code-text2 {
            font-family: Arial, sans-serif;
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
<div class="sticker">
    <div class="barcode">
        {!! DNS1D::getBarcodeHTML($barcode, 'C128', 1.5, 85) !!}
    </div>

    <div class="code-label">{{ $barcode }}</div>
    <div class="code-text">Тюль {{ $item->title }}</div>
    <div class="code-text">ширина {{ $item->width }}</div>
    <div class="code-text">высота {{ $item->height }}</div>

    <div class="code-text2"><b>швея № {{ $seamstressId }}</b></div>
</div>
</body>
</html>
