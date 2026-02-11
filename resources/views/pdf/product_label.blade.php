<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Этикетка товара</title>
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

        .title {
            font-family: DejaVu Sans, sans-serif;
            font-weight: bold;
            letter-spacing: -1.0px;
            font-size: 25px;
            text-align: left;
            margin-bottom: 10px;
            margin-top: 5px;
        }

        .characteristics {
            font-family: DejaVu Sans, sans-serif;
            letter-spacing: -1.0px;
            font-size: 15px;
            text-align: left;
            line-height: 0.8;
        }
    </style>
</head>
<body>
@for($i = 0; $i < 20; $i++)
<div class="page">
    <div class="container">
        <div class="title">Тюль {{ $data['title'] }}</div>

        <div class="characteristics">
            <div class="characteristic">Цвет: {{ $data['color'] }}</div>
            <div class="characteristic">Вид
                принта: {{ $data['print_type'] }}</div>
            <div class="characteristic">Материал: {{ $data['material'] }}</div>
            <div class="characteristic">
                Производство: {{ $data['country'] }}</div>
            <div class="characteristic">Тип
                крепления: {{ $data['fastening_type'] }}</div>
        </div>
    </div>
</div>
@endfor

</body>
</html>
