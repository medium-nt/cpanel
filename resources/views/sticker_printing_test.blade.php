<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="shortcut icon"
          href="{{ asset('vendor/adminlte/dist/img/crm_logo.png') }}">
    <title>МЕГАТЮЛЬ | {{ $title }}</title>

    <link rel="stylesheet"
          href="{{ asset('vendor/adminlte/dist/css/adminlte.min.css') }}">
    <link rel="stylesheet"
          href="{{ asset('vendor/fontawesome-free/css/all.min.css') }}">

    <script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

    <style>
        .wrapper {
            background-color: #f4f6f9;
        }
    </style>
</head>
<body>

<div class="wrapper" style="min-height: 100vh;">
    <div class="content">
        <div class="container-fluid">
            <div class="card" style="top: 10px;">
                <div class="card-body">
                    <label>Поле ввода сразу должно быть с фокусом.</label>
                    <input type="text"
                           class="form-control form-control-lg"
                           placeholder="1234567890"
                           style="border-width: 3px;"
                           value=""
                           autofocus>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.js') }}"></script>
<script src="{{ asset('vendor/adminlte/dist/js/adminlte.js') }}"></script>
<script src="{{ asset('vendor/jquery/jquery.min.js') }}"></script>

</body>
</html>
