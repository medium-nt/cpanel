@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-12">
        @if($supply->status == 0 && empty($supply->supply_id))
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Выбор поставки WB FBO</h3>
                </div>
                <div class="card-body">
                    @if(!empty($wbSupplies))
                        <form
                            action="{{ route('marketplace_supplies.link_wb_fbo', ['marketplace_supply' => $supply]) }}"
                            method="POST" id="link_wb_fbo_form">
                            @csrf
                            <div class="form-group">
                                <label for="wb_supply_select">Поставка из
                                    WB</label>
                                <select class="form-control"
                                        id="wb_supply_select"
                                        name="wb_supply_id" required>
                                    <option value="">-- Выберите поставку --
                                    </option>
                                    @foreach($wbSupplies as $wbSupply)
                                        <option
                                            value="{{ $wbSupply['supplyID'] }}">
                                            № {{ $wbSupply['supplyID'] }}
                                            от {{ \Carbon\Carbon::parse($wbSupply['createDate'])->format('d.m.Y H:i') }}
                                            (дата
                                            поставки: {{ \Carbon\Carbon::parse($wbSupply['supplyDate'])->format('d.m.Y') }}
                                            )
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                Выбрать
                            </button>
                        </form>
                    @else
                        <p class="text-muted">Нет доступных поставок из WB.</p>
                    @endif
                </div>
            </div>
        @else
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Данные поставки WB FBO</h3>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Номер поставки</th>
                            <td>{{ $supply->supply_id }}</td>
                        </tr>
                        <tr>
                            <th>Кластер (склад)</th>
                            <td>{{ $supply->cluster }}</td>
                        </tr>
                        <tr>
                            <th>Дата поставки</th>
                            <td>{{ $supply->supply_date?->format('d.m.Y') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        @endif
    </div>
@stop

@section('js')
    <script>
        document.getElementById('link_wb_fbo_form')?.addEventListener('submit', function (e) {
            if (!confirm('Вы уверены, что хотите выбрать эту поставку?')) {
                e.preventDefault();
            }
        });
    </script>
@stop
