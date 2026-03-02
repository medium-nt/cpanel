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

            <div class="card">
                <div class="card-header p-0">
                    <ul class="nav nav-tabs">
                        <li class="nav-item">
                            <a class="nav-link"
                               href="{{ route('on_inspection') }}">Товары на
                                осмотре</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('returns') }}">Товары
                                готовые к осмотру</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active bg-white"
                               href="{{ route('kiosk.processed_items') }}">Обработанные
                                товары</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="form-group col-md-2">
                            <select name="material" id="material"
                                    class="form-control form-control-lg"
                                    onchange="updatePageWithQueryParam(this)"
                                    required>
                                <option value="" selected disabled>Выберите
                                    материал
                                </option>
                                <option value="Бамбук"
                                        @if(request()->get('material') == 'Бамбук') selected @endif>
                                    Бамбук
                                </option>
                                <option value="Вуаль"
                                        @if(request()->get('material') == 'Вуаль') selected @endif>
                                    Вуаль
                                </option>
                                <option value="Лен"
                                        @if(request()->get('material') == 'Лен') selected @endif>
                                    Лен
                                </option>
                                <option value="Молния"
                                        @if(request()->get('material') == 'Молния') selected @endif>
                                    Молния
                                </option>
                                <option value="Мрамор"
                                        @if(request()->get('material') == 'Мрамор') selected @endif>
                                    Мрамор
                                </option>
                                <option value="Сетка"
                                        @if(request()->get('material') == 'Сетка') selected @endif>
                                    Сетка
                                </option>
                                <option value="Шифон"
                                        @if(request()->get('material') == 'Шифон') selected @endif>
                                    Шифон
                                </option>
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <select name="width" id="width"
                                    class="form-control form-control-lg"
                                    onchange="updatePageWithQueryParam(this)"
                                    required>
                                <option value="" selected disabled>Выберите
                                    ширину
                                </option>
                                @foreach([200,300,400,500,600,700,800] as $w)
                                    <option value="{{ $w }}"
                                            @if(request()->get('width') == $w) selected @endif>{{ $w }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2">
                            <select name="height" id="height"
                                    class="form-control form-control-lg"
                                    onchange="updatePageWithQueryParam(this)"
                                    required>
                                <option value="" selected disabled>Выберите
                                    высоту
                                </option>
                                @foreach([220,225,230,235,240,245,250,255,260,265,270,275,280,285,290,295] as $h)
                                    <option value="{{ $h }}"
                                            @if(request()->get('height') == $h) selected @endif>{{ $h }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group col-md-2 ml-3">
                            <a href="{{ route('kiosk.processed_items') }}"
                               class="btn btn-lg btn-outline-secondary">Сбросить</a>
                        </div>
                    </div>
                    <table class="table table-bordered">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">#</th>
                            <th scope="col">Товар</th>
                            <th scope="col">Статус</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($processedItems ?? [] as $item)
                            <tr>
                                <td class="align-middle">{{ $item->id }}</td>
                                <td class="align-middle">
                                    <b>{{ $item->item->title }} {{ $item->item->width }}
                                        х{{ $item->item->height }}</b>
                                </td>
                                <td class="align-middle">
                                    @if($item->status === 15)
                                        <span class="badge badge-success">Осмотрен</span>
                                    @elseif($item->status === 16)
                                        <span class="badge badge-danger">На утилизацию</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-muted text-center">
                                    Товаров нет
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <x-idle-modal-component/>

    <script
        src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.js') }}"></script>
    <script src="{{ asset('vendor/adminlte/dist/js/adminlte.js') }}"></script>
    <script src="{{ asset('js/PageQueryParam.js') }}"></script>
</div>
</body>
</html>
