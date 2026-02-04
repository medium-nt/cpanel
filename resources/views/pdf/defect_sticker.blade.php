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
            margin-bottom: 15px;
        }

        .material {
            font-family: DejaVu Sans, sans-serif;
            letter-spacing: -1.0px;
            font-size: 15px;
            text-align: left;
            line-height: 0.8;
        }

        .reason {
            font-family: DejaVu Sans, sans-serif;
            letter-spacing: -1.0px;
            font-size: 12px;
            text-align: left;
            line-height: 0.8;
        }
    </style>
</head>
<body>

<div class="page">
    <div class="container">
        <div class="barcode">
            {!! DNS1D::getBarcodeHTML('DEF-'.$order->id, 'C128', 1.7, 85) !!}
        </div>
        <div class="code-label">{{ 'DEF-'.$order->id }}</div>
        <div class="material">
            <b>
                {{ $order->movementMaterials->first()->material->title }}
                {{ $order->movementMaterials->first()->quantity }}
                {{ $order->movementMaterials->first()->material->unit }}
            </b>
        </div>
        <div class="reason">Причина: {{ $order->comment }}
            <span style="float:right; font-weight: bold">ID:
                {{ $order->seamstress->id ?? '' }} {{ $order->cutter->id ?? '' }}
            </span>
        </div>
    </div>
</div>

</body>
</html>
