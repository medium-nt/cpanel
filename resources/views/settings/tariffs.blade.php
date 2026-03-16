@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form>

                    {{-- Раздел 1: Оклад (выход за смену) --}}
                    <h4>Выход за смену</h4>
                    <div class="row">
                        <div class="col-md-3">
                            <select class="form-control measurement-type-select"
                                    disabled>
                                <option value="per_piece">оклад за день</option>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <input type="number" class="form-control"
                                   id="salary_per_shift" placeholder="0" min="0"
                                   step="0.01">
                        </div>
                    </div>

                    <hr>

                    {{-- Раздел 2: Пошив --}}
                    <div>
                        <h4>Пошив</h4>
                        <div class="row">
                            <div class="col-md-3">
                                <select
                                    class="form-control measurement-type-select">
                                    <option value="" selected>-----</option>
                                    <option value="per_meter">за пог.метр
                                    </option>
                                    <option value="per_piece">за штуку</option>
                                </select>
                            </div>

                            <div class="col-md-9">
                                <div class="pricing-table-per-meter"
                                     style="display: none;">
                                    <div class="table-responsive">
                                        <table
                                            class="table table-bordered table-hover">
                                            <thead>
                                            <tr>
                                                <th>Материал</th>
                                                <th width="80">200</th>
                                                <th width="80">300</th>
                                                <th width="80">400</th>
                                                <th width="80">500</th>
                                                <th width="80">600</th>
                                                <th width="80">700</th>
                                                <th width="80">800</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td>Бамбук</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Лен</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Вуаль</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Мрамор</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="pricing-table-per-piece"
                                     style="display: none;">
                                    <div class="table-responsive">
                                        <table
                                            class="table table-bordered table-hover">
                                            <thead>
                                            <tr>
                                                <th>Материал</th>
                                                <th width="80">200</th>
                                                <th width="80">300</th>
                                                <th width="80">400</th>
                                                <th width="80">500</th>
                                                <th width="80">600</th>
                                                <th width="80">700</th>
                                                <th width="80">800</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td>Бамбук</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Лен</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Вуаль</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Мрамор</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    {{-- Раздел 3: Перепаковка --}}
                    <div>
                        <h4>Перепаковка</h4>
                        <div class="row">
                            <div class="col-md-3">
                                <select
                                    class="form-control measurement-type-select">
                                    <option value="" selected>-----</option>
                                    <option value="per_meter">за пог.метр
                                    </option>
                                    <option value="per_piece">за штуку</option>
                                </select>
                            </div>

                            <div class="col-md-9">
                                <div class="pricing-table-per-meter"
                                     style="display: none;">
                                    <div class="table-responsive">
                                        <table
                                            class="table table-bordered table-hover">
                                            <thead>
                                            <tr>
                                                <th>Материал</th>
                                                <th width="80">200</th>
                                                <th width="80">300</th>
                                                <th width="80">400</th>
                                                <th width="80">500</th>
                                                <th width="80">600</th>
                                                <th width="80">700</th>
                                                <th width="80">800</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td>Бамбук</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Лен</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Вуаль</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Мрамор</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="pricing-table-per-piece"
                                     style="display: none;">
                                    <div class="table-responsive">
                                        <table
                                            class="table table-bordered table-hover">
                                            <thead>
                                            <tr>
                                                <th>Материал</th>
                                                <th width="80">200</th>
                                                <th width="80">300</th>
                                                <th width="80">400</th>
                                                <th width="80">500</th>
                                                <th width="80">600</th>
                                                <th width="80">700</th>
                                                <th width="80">800</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td>Бамбук</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Лен</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Вуаль</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Мрамор</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

                    {{-- Раздел 4: Стикеровка --}}
                    <div>
                        <h4>Стикеровка</h4>
                        <div class="row">
                            <div class="col-md-3">
                                <select
                                    class="form-control measurement-type-select">
                                    <option value="" selected>-----</option>
                                    <option value="per_meter">за пог.метр
                                    </option>
                                    <option value="per_piece">за штуку</option>
                                </select>
                            </div>

                            <div class="col-md-9">
                                <div class="pricing-table-per-meter"
                                     style="display: none;">
                                    <div class="table-responsive">
                                        <table
                                            class="table table-bordered table-hover">
                                            <thead>
                                            <tr>
                                                <th>Материал</th>
                                                <th width="80">200</th>
                                                <th width="80">300</th>
                                                <th width="80">400</th>
                                                <th width="80">500</th>
                                                <th width="80">600</th>
                                                <th width="80">700</th>
                                                <th width="80">800</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td>Бамбук</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Лен</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Вуаль</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Мрамор</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="pricing-table-per-piece"
                                     style="display: none;">
                                    <div class="table-responsive">
                                        <table
                                            class="table table-bordered table-hover">
                                            <thead>
                                            <tr>
                                                <th>Материал</th>
                                                <th width="80">200</th>
                                                <th width="80">300</th>
                                                <th width="80">400</th>
                                                <th width="80">500</th>
                                                <th width="80">600</th>
                                                <th width="80">700</th>
                                                <th width="80">800</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td>Бамбук</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Лен</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Вуаль</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            <tr>
                                                <td>Мрамор</td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                                <td><input type="number"
                                                           class="form-control form-control-sm"
                                                           placeholder="0"
                                                           style="padding: 2px 5px; height: 28px;">
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
@stop

@push('js')
    <script>
        $(document).ready(function () {
            $('.measurement-type-select').on('change', function () {
                const $row = $(this).closest('.row');
                const $tablePerMeter = $row.find('.pricing-table-per-meter');
                const $tablePerPiece = $row.find('.pricing-table-per-piece');
                const value = $(this).val();

                $tablePerMeter.hide();
                $tablePerPiece.hide();

                if (value === 'per_meter') {
                    $tablePerMeter.show();
                } else if (value === 'per_piece') {
                    $tablePerPiece.show();
                }
            });
        });
    </script>
@endpush
