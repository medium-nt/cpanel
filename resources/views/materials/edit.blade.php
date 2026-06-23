@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-6">
        <div class="card">

            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card-header">
                <h3 class="card-title">
                    <a href="{{ route('materials.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Вернуться в список материалов
                    </a>
                </h3>
            </div>

            <form action="{{ route('materials.update', ['material' => $material->id]) }}" method="POST">
                @method('PUT')
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="title">Название</label>
                        <input type="text"
                               class="form-control @error('title') is-invalid @enderror"
                               id="title"
                               name="title"
                               placeholder=""
                               value="{{ $material->title }}"
                               required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="type_id">Тип</label>
                                <select name="type_id" id="type_id" class="form-control" required>
                                    <option value="" disabled selected>---</option>
                                    @foreach($typesMaterial as $typeMaterial)
                                        <option value="{{$typeMaterial->id}}" @selected($material->type_id == $typeMaterial->id)>
                                            {{$typeMaterial->title}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="unit">Ед. измерения</label>
                                <input type="text"
                                       class="form-control @error('unit') is-invalid @enderror"
                                       id="unit"
                                       name="unit"
                                       maxlength="10"
                                       placeholder=""
                                       value="{{ $material->unit }}"
                                       required>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label
                                    for="purchase_price">Себестоимость</label>
                                <input type="number"
                                       class="form-control @error('purchase_price') is-invalid @enderror"
                                       id="purchase_price"
                                       name="purchase_price"
                                       placeholder="за единицу"
                                       min="0.01"
                                       step="0.01"
                                       value="{{ $material->purchase_price }}"
                                       required>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="is_active">Статус</label>
                        <select name="is_active" id="is_active"
                                class="form-control" required>
                            <option value="1" @selected($material->is_active)>
                                Активен
                            </option>
                            <option value="0" @selected(!$material->is_active)>
                                Неактивен
                            </option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="minimum_roll_size_for_closure">Мин. остаток
                            для закрытия рулона</label>
                        <input type="number"
                               class="form-control @error('minimum_roll_size_for_closure') is-invalid @enderror"
                               id="minimum_roll_size_for_closure"
                               name="minimum_roll_size_for_closure"
                               placeholder="в метрах"
                               min="0"
                               step="0.01"
                               value="{{ $material->minimum_roll_size_for_closure }}"
                               required>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-success">Сохранить</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Карточка поставщиков --}}
        <div class="card mt-4">
            <div class="card-header">
                <h3 class="card-title">Поставщики</h3>
            </div>
            <div class="card-body">
                @if($suppliers->isEmpty())
                    <p class="text-muted">Нет поставщиков в системе</p>
                @else
                    {{-- Таблица привязанных поставщиков --}}
                    @if($attachedSuppliers->isNotEmpty())
                        <form
                            action="{{ route('materials.suppliers.update', ['material' => $material->id]) }}"
                            method="POST">
                            @method('PUT')
                            @csrf
                            <table class="table table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th>Поставщик</th>
                                    <th style="width: 150px;">Недостача %</th>
                                    <th style="width: 80px;"></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($attachedSuppliers as $supplier)
                                    <tr>
                                        <td>{{ $supplier->title }}</td>
                                        <td>
                                            <input type="number"
                                                   name="shortages[{{ $supplier->pivot->id }}]"
                                                   class="form-control"
                                                   value="{{ $supplier->pivot->shortage_percent }}"
                                                   min="0"
                                                   max="100"
                                                   step="0.01">
                                        </td>
                                        <td>
                                            <button type="button"
                                                    class="btn btn-danger btn-sm"
                                                    onclick="deleteSupplier({{ $supplier->pivot->id }}, '{{ $supplier->title }}')">
                                                ×
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>

                            <button type="submit" class="btn btn-success">
                                Сохранить недостачу
                            </button>
                        </form>

                        {{-- Скрытые DELETE формы для JS --}}
                        @foreach($attachedSuppliers as $supplier)
                            <form id="delete-form-{{ $supplier->pivot->id }}"
                                  action="{{ route('materials.suppliers.detach', ['material' => $material->id, 'pivotId' => $supplier->pivot->id]) }}"
                                  method="POST"
                                  style="display:none;">
                                @method('DELETE')
                                @csrf
                            </form>
                        @endforeach

                        <script>
                            function deleteSupplier(pivotId, supplierTitle) {
                                if (confirm('Удалить поставщика "' + supplierTitle + '"?')) {
                                    document.getElementById('delete-form-' + pivotId).submit();
                                }
                            }
                        </script>
                    @else
                        <p class="text-muted">Нет привязанных поставщиков</p>
                    @endif

                    {{-- Блок добавления --}}
                    @if($suppliers->count() > $attachedSuppliers->count())
                        <hr>
                        <div class="mt-3">
                            <a href="#" id="show-add-supplier-btn">Добавить
                                поставщика</a>

                            <div id="add-supplier-form" class="form-group"
                                 style="display:none;">
                                <label>Выберите поставщика</label>
                                <form
                                    action="{{ route('materials.suppliers.attach', ['material' => $material->id]) }}"
                                    method="POST">
                                    @csrf
                                    <select name="supplier_id"
                                            class="form-control" required>
                                        <option value="" disabled selected>---
                                        </option>
                                        @foreach($suppliers as $supplier)
                                            @if(!$attachedSuppliers->contains('id', $supplier->id))
                                                <option
                                                    value="{{ $supplier->id }}">{{ $supplier->title }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                    <div class="mt-2">
                                        <button type="submit"
                                                class="btn btn-success">ОК
                                        </button>
                                        <button type="button"
                                                id="hide-add-supplier-btn"
                                                class="btn btn-secondary">
                                            Отмена
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <script>
                            document.getElementById('show-add-supplier-btn').addEventListener('click', function (e) {
                                e.preventDefault();
                                document.getElementById('add-supplier-form').style.display = 'block';
                                this.style.display = 'none';
                            });

                            document.getElementById('hide-add-supplier-btn').addEventListener('click', function () {
                                document.getElementById('add-supplier-form').style.display = 'none';
                                document.getElementById('show-add-supplier-btn').style.display = 'inline';
                            });
                        </script>
                    @endif
                @endif
            </div>
        </div>
    </div>
@stop
