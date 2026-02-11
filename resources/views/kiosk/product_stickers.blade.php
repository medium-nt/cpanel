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

    <link rel="stylesheet" href="{{ asset('css/kiosk.css') }}">
</head>
<body>

<div class="wrapper">
    <div class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <a href="{{ route('kiosk') }}"
                       class="btn-kiosk btn-lg btn-kiosk-blue">На главную</a>
                </div>
            </div>

            <div class="card" style="top: 10px;">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <select class="form-control form-control-lg"
                                    onchange="updatePageWithQueryParam(this)"
                                    name="material"
                                    required>
                                <option selected disabled>Выберите материал
                                </option>
                                @foreach($titleMaterials as $titleMaterial)
                                    <option value="{{ $titleMaterial->title }}"
                                            @if(request('material') == $titleMaterial->title) selected @endif
                                    >{{ $titleMaterial->title }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if(request('material'))
                            <button
                                id="printStickerBtn"
                                onclick="printBarcode('{{ route('kiosk.product-label', ['materialName' => request('material')]) }}')"
                                class="btn btn-outline-primary btn-lg mr-5">
                                <i class="fas fa-print"></i>
                                <span
                                    id="printBtnText">Распечатать 20 стикеров</span>
                                <span id="printSpinner"
                                      class="spinner-border spinner-border-sm d-none ms-2"
                                      role="status"></span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<iframe id="printFrame"
        style="display:none"></iframe>

<!-- Modal для предупреждения о неактивности -->
<x-idle-modal-component/>

<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.js') }}"></script>
<script src="{{ asset('vendor/adminlte/dist/js/adminlte.js') }}"></script>

<script
    src="{{ asset('js/printBarcode.js') }}?v={{ filemtime(public_path('js/printBarcode.js')) }}"></script>
<script src="{{ asset('js/PageQueryParam.js') }}"></script>

</body>
</html>
