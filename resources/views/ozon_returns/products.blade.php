@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-12">

        {{-- Фильтр по статусу --}}
        <div class="card mb-3">
            <div class="card-body">
                <div class="form-group col-md-4">
                    <label for="visual_status_name">Статус возврата:</label>
                    <select name="visual_status_name"
                            id="visual_status_name"
                            class="form-control"
                            onchange="updatePageWithQueryParam(this)">
                        <option value="">Все статусы</option>
                        <option value="DisputeOpened"
                                @if($currentStatus === 'DisputeOpened') selected @endif>
                            Открыт спор с покупателем
                        </option>
                        <option value="OnSellerApproval"
                                @if($currentStatus === 'OnSellerApproval') selected @endif>
                            На согласовании у продавца
                        </option>
                        <option value="ArrivedAtReturnPlace"
                                @if($currentStatus === 'ArrivedAtReturnPlace') selected @endif>
                            В пункте выдачи
                        </option>
                        <option value="OnSellerClarification"
                                @if($currentStatus === 'OnSellerClarification') selected @endif>
                            На уточнении у продавца
                        </option>
                        <option
                            value="OnSellerClarificationAfterPartialCompensation"
                            @if($currentStatus === 'OnSellerClarificationAfterPartialCompensation') selected @endif>
                            На уточнении у продавца после частичной компенсации
                        </option>
                        <option value="OfferedPartialCompensation"
                                @if($currentStatus === 'OfferedPartialCompensation') selected @endif>
                            Предложена частичная компенсация
                        </option>
                        <option value="ReturnMoneyApproved"
                                @if($currentStatus === 'ReturnMoneyApproved') selected @endif>
                            Одобрен возврат денег
                        </option>
                        <option value="PartialCompensationReturned"
                                @if($currentStatus === 'PartialCompensationReturned') selected @endif>
                            Вернули часть денег
                        </option>
                        <option value="CancelledDisputeNotOpen"
                                @if($currentStatus === 'CancelledDisputeNotOpen') selected @endif>
                            Возврат отклонён, спор не открыт
                        </option>
                        <option value="Rejected"
                                @if($currentStatus === 'Rejected') selected @endif>
                            Заявка отклонена
                        </option>
                        <option value="CrmRejected"
                                @if($currentStatus === 'CrmRejected') selected @endif>
                            Заявка отклонена Ozon
                        </option>
                        <option value="Cancelled"
                                @if($currentStatus === 'Cancelled') selected @endif>
                            Заявка отменена
                        </option>
                        <option value="Approved"
                                @if($currentStatus === 'Approved') selected @endif>
                            Заявка одобрена продавцом
                        </option>
                        <option value="ApprovedByOzon"
                                @if($currentStatus === 'ApprovedByOzon') selected @endif>
                            Заявка одобрена Ozon
                        </option>
                        <option value="ReceivedBySeller"
                                @if($currentStatus === 'ReceivedBySeller') selected @endif>
                            Продавец получил возврат
                        </option>
                        <option value="MovingToSeller"
                                @if($currentStatus === 'MovingToSeller') selected @endif>
                            Возврат на пути к продавцу
                        </option>
                        <option value="ReturningToSellerByCourier"
                                @if($currentStatus === 'ReturningToSellerByCourier') selected @endif>
                            Курьер везёт возврат продавцу
                        </option>
                        <option value="Utilizing"
                                @if($currentStatus === 'Utilizing') selected @endif>
                            На утилизации
                        </option>
                        <option value="Utilized"
                                @if($currentStatus === 'Utilized') selected @endif>
                            Утилизирован
                        </option>
                        <option value="MoneyReturned"
                                @if($currentStatus === 'MoneyReturned') selected @endif>
                            Покупателю вернули всю сумму
                        </option>
                        <option value="PartialCompensationInProcess"
                                @if($currentStatus === 'PartialCompensationInProcess') selected @endif>
                            Одобрен частичный возврат денег
                        </option>
                        <option value="DisputeYouOpened"
                                @if($currentStatus === 'DisputeYouOpened') selected @endif>
                            Продавец открыл спор
                        </option>
                        <option value="CompensationRejected"
                                @if($currentStatus === 'CompensationRejected') selected @endif>
                            Отказано в компенсации
                        </option>
                        <option value="DisputeOpening"
                                @if($currentStatus === 'DisputeOpening') selected @endif>
                            Обращение в поддержку отправлено
                        </option>
                        <option value="CompensationOffered"
                                @if($currentStatus === 'CompensationOffered') selected @endif>
                            Ожидает вашего решения по компенсации
                        </option>
                        <option value="WaitingCompensation"
                                @if($currentStatus === 'WaitingCompensation') selected @endif>
                            Ожидает компенсации
                        </option>
                        <option value="SendingError"
                                @if($currentStatus === 'SendingError') selected @endif>
                            Ошибка при отправке обращения в поддержку
                        </option>
                        <option value="CompensationRejectedBySla"
                                @if($currentStatus === 'CompensationRejectedBySla') selected @endif>
                            Истёк срок решения
                        </option>
                        <option value="CompensationRejectedBySeller"
                                @if($currentStatus === 'CompensationRejectedBySeller') selected @endif>
                            Продавец отказался от компенсации
                        </option>
                        <option value="MovingToOzon"
                                @if($currentStatus === 'MovingToOzon') selected @endif>
                            Едет на склад Ozon
                        </option>
                        <option value="ReturnedToOzon"
                                @if($currentStatus === 'ReturnedToOzon') selected @endif>
                            На складе Ozon
                        </option>
                        <option value="MoneyReturnedBySystem"
                                @if($currentStatus === 'MoneyReturnedBySystem') selected @endif>
                            Быстрый возврат
                        </option>
                        <option value="WaitingShipment"
                                @if($currentStatus === 'WaitingShipment') selected @endif>
                            Ожидает отправки
                        </option>
                    </select>
                </div>
            </div>
        </div>

        {{-- Таблица с возвратами --}}
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-bordered table-sm">
                        <thead class="thead-dark">
                        <tr>
                            <th>ID возврата</th>
                            <th>Заказ</th>
                            <th>Товар</th>
                            <th>Цены</th>
                            <th>Тип/Схема</th>
                            <th>Статус</th>
                            <th>Даты возврата</th>
                            <th>Штрих-код</th>
                            <th>Причина</th>
                            <th>Пункт выдачи</th>
                            <th>Storage</th>
                            <th>Доп. инфо</th>
                            <th>Компенсация</th>
                        </tr>
                        </thead>
                        <tbody>
                        @if($returns->count() > 0)
                            @foreach($returns as $item)
                                <tr>
                                    <td>
                                        {{ $item['id'] ?? '-' }}<br>
                                        <small
                                            class="text-muted">CID: {{ $item['company_id'] ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <span
                                            style="white-space: nowrap">{{ $item['order_number'] ?? '-' }}</span><br>
                                        <small class="text-muted">
                                            <span
                                                style="white-space: nowrap">{{ $item['posting_number'] ?? '-' }}</span>
                                            <br>
                                            OrderID: {{ $item['order_id'] ?? '-' }}
                                        </small>
                                    </td>
                                    <td>
                                        {{ $item['product']['name'] ?? '-' }}
                                        <br>
                                        <small class="text-muted">
                                            SKU: {{ $item['product']['sku'] ?? '-' }}
                                            <br>
                                            Offer: {{ $item['product']['offer_id'] ?? '-' }}
                                            <br>
                                            Кол-во: {{ $item['product']['quantity'] ?? 0 }}
                                        </small>
                                    </td>
                                    <td>
                                        <div class="mb-1">
                                            <strong>{{ $item['product']['price']['price'] ?? '-' }}</strong>
                                            <small
                                                class="text-muted">{{ $item['product']['price']['currency_code'] ?? '' }}</small>
                                        </div>
                                        <div class="mb-1 text-muted">
                                            <small>без
                                                ком.: {{ $item['product']['price_without_commission']['price'] ?? '-' }}</small>
                                        </div>
                                        <div class="text-danger">
                                            <small>ком.: {{ $item['product']['commission']['price'] ?? '-' }}
                                                ({{ $item['product']['commission_percent'] ?? '-' }}
                                                %)</small>
                                        </div>
                                    </td>
                                    <td>
                                        {{ $item['type'] ?? '-' }}<br>
                                        {{ $item['schema'] ?? '-' }}
                                    </td>
                                    <td>
                                        <span
                                            class="badge badge-info">{{ $item['visual']['status']['display_name'] ?? '-' }}</span><br>
                                        <small class="text-muted">
                                            @if(isset($item['visual']['change_moment']))
                                                {{ \Illuminate\Support\Carbon::parse($item['visual']['change_moment'])->format('d.m.Y H:i') }}
                                            @else
                                                -
                                            @endif
                                        </small>
                                    </td>
                                    <td>
                                        @if(isset($item['logistic']['return_date']))
                                            <div class="mb-1"><i
                                                    class="far fa-calendar-alt text-muted"></i> {{ \Illuminate\Support\Carbon::parse($item['logistic']['return_date'])->format('d.m.Y H:i') }}
                                            </div>
                                        @endif
                                        @if(isset($item['logistic']['final_moment']))
                                            <div class="text-success">
                                                <small>✓ {{ \Illuminate\Support\Carbon::parse($item['logistic']['final_moment'])->format('d.m.Y H:i') }}</small>
                                            </div>
                                        @endif
                                        @if(isset($item['logistic']['cancelled_with_compensation_moment']))
                                            <div class="text-warning">
                                                <small>⚠ {{ \Illuminate\Support\Carbon::parse($item['logistic']['cancelled_with_compensation_moment'])->format('d.m.Y H:i') }}</small>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $item['logistic']['barcode'] ?? '-' }}</td>
                                    <td>
                                        <small>{{ $item['return_reason_name'] ?? '-' }}</small>
                                    </td>
                                    <td>
                                        {{ $item['place']['name'] ?? '-' }}<br>
                                        <small
                                            class="text-muted">{{ mb_substr($item['place']['address'] ?? '', 0, 40) }}
                                            ...</small>
                                    </td>
                                    <td>
                                        <div class="mb-1">
                                            <strong>{{ $item['storage']['sum']['price'] ?? '-' }}</strong>
                                            <small
                                                class="text-muted">{{ $item['storage']['sum']['currency_code'] ?? '' }}</small>
                                            <span class="badge badge-secondary">{{ $item['storage']['days'] ?? '-' }} дн.</span>
                                        </div>
                                        @if(isset($item['storage']['arrived_moment']))
                                            <div class="text-info"
                                                 style="white-space: nowrap">
                                                <small>→ {{ \Illuminate\Support\Carbon::parse($item['storage']['arrived_moment'])->format('d.m.Y') }}</small>
                                            </div>
                                        @endif
                                        @if(isset($item['storage']['utilization_forecast_date']))
                                            <div class="text-danger"
                                                 style="white-space: nowrap">
                                                <small>⚠ {{ \Illuminate\Support\Carbon::parse($item['storage']['utilization_forecast_date'])->format('d.m.Y') }}</small>
                                            </div>
                                        @endif
                                        <div class="text-muted mt-1"
                                             style="white-space: nowrap">
                                            <small>утил.: {{ $item['storage']['utilization_sum']['price'] ?? '-' }} {{ $item['storage']['utilization_sum']['currency_code'] ?? '' }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="mb-1">
                                            @if($item['additional_info']['is_opened'] ?? false)
                                                <span
                                                    class="badge badge-warning">Открыт</span>
                                            @else
                                                <span
                                                    class="badge badge-secondary">Закрыт</span>
                                            @endif
                                            @if($item['additional_info']['is_super_econom'] ?? false)
                                                <span class="badge badge-info">Супер эконом</span>
                                            @endif
                                        </div>
                                        <div class="text-muted">
                                            <small>
                                                Source: {{ $item['source_id'] ?? '-' }}
                                                <br>
                                                Clr: {{ $item['clearing_id'] ?? '-' }}
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        @if(isset($item['compensation_status']['status']))
                                            <small>
                                                {{ $item['compensation_status']['status']['display_name'] ?? '-' }}
                                                <br>
                                                @if(isset($item['compensation_status']['change_moment']))
                                                    {{ \Illuminate\Support\Carbon::parse($item['compensation_status']['change_moment'])->format('d.m.Y H:i') }}
                                                @else
                                                    -
                                                @endif
                                            </small>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="13" class="text-center text-muted">
                                    Нет данных
                                </td>
                            </tr>
                        @endif
                        </tbody>
                    </table>
                </div>

                @if($hasNext)
                    <div class="text-center mt-3">
                        <small class="text-muted">Загружены только первые 500
                            записей.</small>
                    </div>
                @endif
            </div>
        </div>

    </div>
@stop

@push('js')
    <script src="{{ asset('js/PageQueryParam.js') }}"></script>
@endpush
