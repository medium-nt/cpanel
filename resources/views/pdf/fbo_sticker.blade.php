<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>FBO Sticker PDF</title>
    <style>
        @page {
            size: 120mm 75mm;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 5px 8px;
            width: 120mm;
            height: 75mm;
            font-family: DejaVu Sans, sans-serif;
            font-size: 13px;
            box-sizing: border-box;
        }

        .sticker {
            page-break-after: always;
        }

        .sticker:last-child {
            page-break-after: avoid;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .header-table td {
            vertical-align: middle;
        }

        .header-badge {
            width: 15%;
            text-align: left;
            font-size: 14px;
            font-weight: bold;
            vertical-align: top;
        }

        .header-logo {
            width: 35%;
            text-align: center;
        }

        .header-logo img {
            height: 22mm;
        }

        .header-right {
            width: 60%;
            text-align: right;
            font-size: 11px;
            color: #666;
            line-height: 1.2;
            padding-right: 15px;
        }

        .content-table {
            width: 100%;
            border-collapse: collapse;
        }

        .content-table td {
            vertical-align: top;
        }

        .data-col {
            width: 55%;
            padding-right: 5px;
        }

        .barcode-col {
            width: 45%;
            text-align: center;
            padding-top: 1px;
        }

        .text {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin-bottom: 1px;
            line-height: 1.1;
        }

        .sku-number {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            line-height: 1.0;
            margin-top: 1px;
            margin-right: 10px;
        }

        .barcode-footer {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            line-height: 1.2;
            margin-top: 3px;
            margin-right: 10px;
        }

        .footer {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            margin-top: 2px;
            line-height: 1.0;
        }
    </style>
</head>
<body>
@foreach($stickers as $s)
    <div class="sticker">
        <table class="header-table">
            <tr>
                <td class="header-badge">
                    {{ $s['marketplace_id'] == 1 ? 'OZON' : 'WB' }}
                    <br>
                    <br>
                    <img
                        src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('icons/eac.jpg'))) }}"
                        alt="EAC" width="22" height="22">
                </td>
                <td class="header-logo">
                    <img
                        src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('icons/megatule_stiker_logo.jpg'))) }}"
                        alt="МЕГАТЮЛЬ">
                </td>
                <td class="header-right">
                    <b>Получили бракованный товар?</b>
                    <br>Свяжитесь с нами и мы <br> оперативно решим вопрос!
                </td>
            </tr>
        </table>

        <table class="content-table">
            <tr>
                <td class="data-col">
                    <div class="text">
                        <b>
                            ТЮЛЬ {{ Str::upper($s['item']->title) }}
                            {{ $s['item']->width }}x{{ $s['item']->height }}
                        </b>
                    </div>
                    <div class="text">{{ $s['fastening_type'] }}</div>
                    <div class="text">Состав: {{ $s['material'] }}</div>
                    <div class="text">Цвет: {{ $s['color'] }}</div>
                    <div class="text">Артикул: {{ $s['article'] }}</div>
                    <div class="text">Страна: {{ $s['country'] }}</div>
                    <br>
                    <div class="text">Бренд: МЕГАТЮЛЬ</div>
                    <div class="text">ИП Левкин А.С.</div>
                    <div class="text" style="font-size: 8px">
                        ОГРН:322774600341432 ИНН:760218194200
                    </div>
                </td>

                <td class="barcode-col">
                    {!! DNS1D::getBarcodeHTML($s['barcode'], 'C128', 1.55, 80) !!}
                    <div class="sku-number">{{ $s['barcode'] }}</div>

                    <div class="barcode-footer">
                        <b>{{ $s['order']->order_id }}</b>
                        <br>
                        <br>
                        <span style="font-size: {{ $s['fontSizeCluster'] }}px">
                        {{ $s['order']->cluster }}
                    </span>
                        <br>
                        @isset($s['cutterId'])
                            закройщик № {{ $s['cutterId'] }} |
                        @endisset
                        швея № {{ $s['seamstressId'] ?? '0' }}
                    </div>
                </td>
            </tr>
        </table>
    </div>
@endforeach
</body>
</html>
