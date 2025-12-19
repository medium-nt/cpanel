@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-lg-6 col-md-12 col-sm-12">
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

            <form action="{{ route('movements_from_supplier.store') }}" method="POST">
                @method('POST')
                @csrf
                <div class="card-body">
                    <div class="form-group">
                        <label for="supplier_id">Поставщик</label>
                        <select name="supplier_id" id="supplier_id" class="form-control" required>
                            <option value="" disabled selected>---</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->title }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="comment">Комментарий</label>
                        <textarea class="form-control @error('comment') is-invalid @enderror"
                                  id="comment"
                                  name="comment"
                                  rows="3"
                                  value="{{ old('comment') }}"></textarea>
                    </div>

                    <div id="materials-container" class="materials-container">
                        <div class="material-row" data-row-index="0">
                            <div class="row">
                                <div class="col-md-8 form-group">
                                    <label for="material_id">Материал</label>
                                    <select name="material_id"
                                            class="form-control" required>
                                        <option value="" disabled selected>---
                                        </option>
                                        @foreach($materials as $material)
                                            <option
                                                value="{{ $material->id }}">{{ $material->title }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-2 form-group">
                                    <label for="quantity">Кол-во</label>
                                    <input type="number"
                                           class="form-control @error('quantity') is-invalid @enderror"
                                           name="quantity[0]"
                                           value="{{ old('quantity.0') }}"
                                           step="0.01"
                                           min="0.01"
                                           required>
                                </div>

                                <div class="col-md-2 form-group">
                                    <label for="number_rolls">Рулонов</label>
                                    <input type="number"
                                           class="form-control @error('number_rolls') is-invalid @enderror"
                                           name="number_rolls[0]"
                                           value="{{ old('number_rolls.0') }}"
                                           step="1"
                                           min="1">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Строка ИТОГО -->
                    <div class="total-row">
                        <div class="row">
                            <div
                                class="col-md-8 form-group d-flex justify-content-end align-items-center">
                                <strong>ИТОГО:</strong>
                            </div>
                            <div class="col-md-2 form-group">
                                <input type="text"
                                       id="total-quantity"
                                       class="form-control font-weight-bold"
                                       readonly
                                       value="0">
                            </div>
                            <div class="col-md-2 form-group">
                                <input type="text"
                                       id="total-rolls"
                                       class="form-control font-weight-bold"
                                       readonly
                                       value="0">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-8 form-group">
                        </div>
                        <div class="col-md-2 form-group">
                            <button type="button" id="add-material-btn"
                                    class="btn btn-success btn">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Принять</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let materialRowIndex = 1; // Начинаем с 1, так как 0 уже существует

            // Функция добавления новой строки
            function addMaterialRow() {
                const container = document.getElementById('materials-container');

                // Создаем новую строку
                const newRow = document.createElement('div');
                newRow.className = 'material-row';
                newRow.setAttribute('data-row-index', materialRowIndex);

                // Генерируем HTML для новой строки
                newRow.innerHTML = `
                    <div class="row mb-2">
                        <div class="col-md-8 form-group">
                            <div class="input-group-append d-flex justify-content-end">
                                <button type="button" class="btn btn-danger btn-remove-row" title="Удалить строку">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-2 form-group">
                            <input type="number"
                               class="form-control quantity-input"
                               name="quantity[${materialRowIndex}]"
                                     step="0.01"
                                     min="0.01"
                                     required>
                        </div>
                        <div class="col-md-2 form-group">
                            <div class="input-group">
                                <input type="number"
                                     class="form-control rolls-input"
                                     name="number_rolls[${materialRowIndex}]"
                                     step="1"
                                     min="1">
                            </div>
                        </div>
                    </div>
                `;

                container.appendChild(newRow);
                materialRowIndex++;

                // Добавляем обработчик на кнопку удаления
                const removeBtn = newRow.querySelector('.btn-remove-row');
                removeBtn.addEventListener('click', function () {
                    removeMaterialRow(this);
                });

                calculateTotals();
            }

            // Функция удаления строки
            function removeMaterialRow(button) {
                const rows = document.querySelectorAll('.material-row');

                // Нельзя удалять если осталась только одна строка
                if (rows.length <= 1) {
                    alert('Должна остаться хотя бы одна строка с материалом');
                    return;
                }

                const rowToRemove = button.closest('.material-row');
                rowToRemove.remove();

                calculateTotals();
            }

            // Обработчик кнопки добавления
            document.getElementById('add-material-btn').addEventListener('click', addMaterialRow);

            // Обработчики изменений полей для автоматического расчета итогов
            document.getElementById('materials-container').addEventListener('input', function (e) {
                if (e.target.matches('[name^="quantity["], [name^="number_rolls["]')) {
                    calculateTotals();
                }
            });

            // Обработка ошибок валидации (если есть)
            const errorFields = document.querySelectorAll('.is-invalid');
            errorFields.forEach(field => {
                field.addEventListener('input', function () {
                    this.classList.remove('is-invalid');
                });
            });

            // Функция расчета итогов
            function calculateTotals() {
                let totalQuantity = 0;
                let totalRolls = 0;

                // Получаем все поля quantity
                const quantityInputs = document.querySelectorAll('[name^="quantity["]');
                quantityInputs.forEach(input => {
                    const quantity = parseFloat(input.value) || 0;

                    // Ищем поле с рулонами в той же строке
                    const row = input.closest('.material-row');
                    const rollsInput = row.querySelector('[name^="number_rolls["]');
                    const rolls = parseInt(rollsInput?.value) || 1;

                    // Получаем общее количество рулонов и итого штук/метров.
                    totalRolls += rolls;
                    totalQuantity += quantity * rolls;
                });

                // Обновляем поля итогов
                document.getElementById('total-quantity').value = totalQuantity.toFixed(2);
                document.getElementById('total-rolls').value = totalRolls;
            }

            calculateTotals();

            document.querySelector('input[name="quantity[0]"]').addEventListener('input', calculateTotals);
            document.querySelector('input[name="number_rolls[0]"]').addEventListener('input', calculateTotals);

        });
    </script>

@stop

