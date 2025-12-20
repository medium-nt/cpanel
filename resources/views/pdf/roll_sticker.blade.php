<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Barcode PDF</title>
    <style>
        @page {
            size: 120mm 75mm; /* Установка размера страницы */
            margin: 0; /* Убираем отступы */
        }

        body {
            margin: 0; /* Убираем отступы */
            display: flex;
            align-items: center;
            justify-content: center;
            width: 120mm; /* Ширина страницы */
            height: 75mm; /* Высота страницы */
        }

        .container {
            padding: 15px 15px; /* или укажи в px, например: 20px */
            text-align: center;
            box-sizing: border-box;
        }

        .barcode {
            max-width: 200%;
            height: auto;
            width: 200%;
            padding: 0 5px; /* немного отступов по краям, чтобы сканер видел quiet zone */
            box-sizing: border-box;
            text-align: center;
        }

        .code-label {
            font-family: Helvetica, sans-serif;
            font-size: 25px;
            text-align: left;
        }

        .code-text {
            font-family: DejaVu Sans, sans-serif;
            letter-spacing: -1.0px;
            font-size: 20px;
            text-align: left;
            line-height: 0.8;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="barcode">
        {!! DNS1D::getBarcodeHTML($roll->roll_code, 'C128', 3.0, 115) !!}
    </div>

    <div class="code-label">{{ $roll->roll_code }}</div>
    <div class="code-text">{{ $roll->material->title }}</div>
    <div class="code-text">метраж {{ $roll->initial_quantity }}</div>
</div>

</body>
</html>
