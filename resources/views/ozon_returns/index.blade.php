@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-12">

        {{-- Блок со штрих-кодом выдачи возвратов --}}
        <div class="card mb-3">
            <div class="card-body">
                {{-- Заголовок и кнопка обновления --}}
                <div
                    class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0 text-center flex-grow-1">Штрих-код выдачи
                        возвратов OZON</h5>
                    <form method="POST"
                          action="{{ route('ozon_returns.refresh_barcode') }}"
                          class="ms-2">
                        @csrf
                        <button type="submit" class="btn btn-outline-primary"
                                title="Обновить штрих-код">
                            <i class="fas fa-sync-alt"></i>
                            <span
                                class="d-none d-md-inline ms-1">Обновить</span>
                        </button>
                    </form>
                </div>

                <p class="text-muted text-center mb-3 small">
                    Отсканируйте этот штрих-код на пункте выдачи для получения
                    всех возвратов.
                </p>

                {{-- Штрих-код --}}
                <div class="text-center">
                    @if($returnsBarcodeData)
                        <img
                            src="data:image/png;base64,{{ $returnsBarcodeData['png'] }}"
                            alt="Штрих-код выдачи возвратов"
                            style="max-width: 300px; height: auto; width: 100%;">
                        @if($returnsBarcodeData['barcode'])
                            <div class="mt-2">
                                <strong
                                    style="font-size: 1.2rem;">{{ $returnsBarcodeData['barcode'] }}</strong>
                            </div>
                        @endif
                    @else
                        <div class="barcode-placeholder">
                            <i class="fas fa-barcode fa-5x text-muted"></i>
                            <p class="text-muted mt-2">Штрих-код недоступен</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Переключатель табов --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="btn-group w-100" role="group">
                    <a href="{{ route('ozon_returns.index', ['tab' => 'returns']) }}"
                       class="btn {{ $tab === 'returns' ? 'btn-primary' : 'btn-outline-primary' }}">
                        Список возвратов
                    </a>
                    <a href="{{ route('ozon_returns.index', ['tab' => 'deliveries']) }}"
                       class="btn {{ $tab === 'deliveries' ? 'btn-primary' : 'btn-outline-primary' }}">
                        Активные выдачи
                    </a>
                </div>
            </div>
        </div>

        {{-- Таблица с данными --}}
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    @if($tab === 'returns')
                        {{-- Таблица: Список возвратов --}}
                        <table class="table table-hover table-bordered">
                            <thead class="thead-dark">
                            <tr>
                                <th>Пункт выдачи</th>
                                <th>Кол-во</th>
                                <th>Пропуск</th>
                            </tr>
                            </thead>
                            <tbody>
                            @if($returnsData->count() > 0)
                                @foreach($returnsData as $item)
                                    <tr>
                                        <td>
                                            {{ $item['name'] ?? '-' }}
                                            @if(isset($item['address']))
                                                <br><small
                                                    class="text-muted">{{ $item['address'] }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $item['returns_count'] ?? 0 }}</td>
                                        <td>
                                            @if($item['pass_info']['is_required'])
                                                Требуется
                                            @else
                                                Не требуется
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="3"
                                        class="text-center text-muted">
                                        Нет данных
                                    </td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    @else
                        {{-- Таблица: Активные выдачи --}}
                        <table class="table table-hover table-bordered">
                            <thead class="thead-dark">
                            <tr>
                                <th>Пункт выдачи</th>
                                <th>Дата-время</th>
                                <th>Кол-во в отгрузке</th>
                                <th>Общее кол-во</th>
                                <th>Статус</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @if($deliveriesData->count() > 0)
                                @foreach($deliveriesData as $item)
                                    <tr>
                                        <td>
                                            {{ $item['warehouse_name'] ?? '-' }}
                                            @if(isset($item['warehouse_address']) && $item['warehouse_address'])
                                                <br><small
                                                    class="text-muted">{{ $item['warehouse_address'] }}</small>
                                            @endif
                                        </td>
                                        <td>{{ \Illuminate\Support\Carbon::parse($item['created_at'])->format('d.m.Y H:i') }}</td>
                                        <td>{{ $item['approved_articles_count'] ?? 0 }}</td>
                                        <td>{{ $item['total_articles_count'] ?? 0 }}</td>
                                        <td>
                                            {{ match($item['giveout_status']) {
                                                'GIVEOUT_STATUS_CREATED' => 'Создана',
                                                'GIVEOUT_STATUS_APPROVED' => 'Одобрена',
                                                'GIVEOUT_STATUS_COMPLETED' => 'Завершена',
                                                'GIVEOUT_STATUS_CANCELLED' => 'Отменена',
                                                default => $item['giveout_status'] ?? '-',
                                            } }}
                                        </td>
                                        <td>
                                            <button type="button"
                                                    class="btn btn-sm btn-info"
                                                    data-toggle="modal"
                                                    data-target="#giveoutInfoModal"
                                                    onclick="loadGiveoutInfo({{ $item['giveout_id'] }})">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="6"
                                        class="text-center text-muted">
                                        Нет данных
                                    </td>
                                </tr>
                            @endif
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- Модальное окно информации о выдаче --}}
    <div class="modal fade" id="giveoutInfoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Артикулы выдачи</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="giveoutInfoContent">
                        <div class="text-center">
                            <div class="spinner-border" role="status"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@push('js')
    <script>
        function loadGiveoutInfo(giveoutId) {
            document.getElementById('giveoutInfoContent').innerHTML =
                '<div class="text-center"><div class="spinner-border" role="status"></div></div>';

            fetch('{{ route('ozon_returns.giveout_info') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({giveout_id: giveoutId})
            })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        document.getElementById('giveoutInfoContent').innerHTML =
                            '<div class="alert alert-danger">' + data.error + '</div>';
                        return;
                    }

                    let articlesHtml = '<table class="table table-sm table-bordered">' +
                        '<thead><tr><th>Товар</th><th>Подтверждён</th></tr></thead><tbody>';

                    if (data.articles && data.articles.length > 0) {
                        data.articles.forEach(article => {
                            articlesHtml += '<tr>' +
                                '<td>' + (article.name || '-') + '</td>' +
                                '<td>' + (article.approved ?
                                    '<span class="badge badge-success">Да</span>' :
                                    '<span class="badge badge-secondary">Нет</span>') + '</td>' +
                                '</tr>';
                        });
                    } else {
                        articlesHtml += '<tr><td colspan="2" class="text-center">Нет артикулов</td></tr>';
                    }

                    articlesHtml += '</tbody></table>';

                    document.getElementById('giveoutInfoContent').innerHTML = articlesHtml;
                })
                .catch(error => {
                    document.getElementById('giveoutInfoContent').innerHTML =
                        '<div class="alert alert-danger">Ошибка загрузки данных</div>';
                });
        }
    </script>
@endpush
