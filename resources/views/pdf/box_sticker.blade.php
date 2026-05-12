<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Стикер короба</title>
    <style>
        @page {
            size: 75mm 120mm;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 5mm;
            width: 75mm;
            height: 120mm;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            box-sizing: border-box;
        }

        .sticker {
            position: relative;
        }

        .box-number {
            font-size: 16px;
            text-align: center;
            margin-left: -15px;
            width: 100%;
        }

        .box-number .tail {
            font-size: 18px;
            font-weight: bold;
        }

        .qr-main {
            position: absolute;
            bottom: 245px;
            right: 85px;
        }

        .qr-main td {
            text-align: center;
        }

        .qr-main img {
            width: 50px;
            height: 50px;
        }

        .info-row {
            font-size: 11px;
            margin-bottom: 4px;
            line-height: 1.3;
        }

        .info-row .label {
            font-weight: normal;
        }

        .info-row .value {
            font-weight: bold;
            display: block;
        }

        .info-cols {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            margin-top: 165px;
        }

        .info-cols td {
            width: 50%;
            vertical-align: top;
            padding: 0 2px;
        }

        .info-cols .label {
            font-size: 11px;
        }

        .info-cols .value {
            font-size: 13px;
            font-weight: bold;
        }

        .qr-duplicate {
            position: absolute;
            bottom: 30px;
            right: 30px;
        }
    </style>
</head>
<body>

<div class="sticker">
    <div
        class="box-number">{{ \Illuminate\Support\Str::substr($box->number, 0, -4) }}
        <span
            class="tail">{{ \Illuminate\Support\Str::substr($box->number, -4) }}</span>
    </div>
    <div class="box-number">Короб</div>

    <div class="qr-main">
        {!! DNS2D::getBarcodeHTML($box->number, 'QRCODE', 7, 7) !!}
    </div>

    <table class="info-cols">
        <tr>
            <td>
                <div class="label">Номер поставки</div>
                <div class="value">{{ $box->supply->supply_id }}</div>
            </td>
            <td>
                <div class="label">Плановая дата</div>
                <div
                    class="value">{{ $box->supply->gazelka_shipment_date?->format('d.m.Y') ?? '-' }}</div>
            </td>
        </tr>
    </table>
    <div class="info-row"><span class="label">Склад назначения</span><span
            class="value">{{ $box->supply->cluster }}</span></div>
    <div class="info-row"><span class="label">Продавец</span><span
            class="value">ИП Левкин Андрей Станиславович</span></div>

    <div class="qr-duplicate">
        {!! DNS2D::getBarcodeHTML($box->number, 'QRCODE', 2, 2) !!}
    </div>
</div>

</body>
</html>
