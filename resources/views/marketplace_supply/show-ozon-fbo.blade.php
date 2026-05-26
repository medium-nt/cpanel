@extends('layouts.app')

@section('subtitle', $title)
@section('content_header_title', $title)

@section('content_body')
    <div class="col-md-12 pb-4">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Поставка OZON FBO</h3>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th>Номер поставки / Черновик</th>
                        <td>{{ $supply->draft_id ?? $supply->supply_id ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Кластер (склад)</th>
                        <td>{{ $supply->cluster ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Дата поставки</th>
                        <td>{{ $supply->supply_date?->format('d.m.Y') ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Тип отгрузки</th>
                        <td>{{ $supply->supply_type ?? '—' }}</td>
                    </tr>
                    <tr>
                        <th>Статус</th>
                        <td>
                            @switch($supply->status)
                                @case(0)
                                    <span
                                        class="badge bg-secondary">Открытая</span>
                                    @break
                                @case(3)
                                    <span
                                        class="badge bg-success">Закрытая</span>
                                    @break
                                @case(4)
                                    <span
                                        class="badge bg-warning">Отгрузка</span>
                                    @break
                            @endswitch
                        </td>
                    </tr>
                    <tr>
                        <th>Создана</th>
                        <td>{{ $supply->created_at->format('d.m.Y H:i') }}</td>
                    </tr>
                </table>
            </div>
        </div>

        @if(auth()->user()->isAdmin() && $supply->supply_id && ! $supply->draft_id)
            <form
                action="{{ route('marketplace_supplies.destroy', $supply->id) }}"
                method="POST"
                onsubmit="return handleDeleteSubmit(this)">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">
                    <span class="delete-label"><i class="fas fa-trash"></i> Удалить поставку</span>
                    <span class="delete-spinner" style="display:none">
                    <i class="fas fa-spinner fa-spin"></i> Отмена на OZON...
                </span>
                </button>
            </form>
        @endif

        @if(($supply->status === 0 || $supply->status === 4) && ! $supply->supply_id)
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Параметры черновика</h3>
                </div>
                <div class="card-body"
                     @if($supply->draft_id && $supply->draft_created_at)
                         x-data="draftTimer('{{ $supply->draft_created_at->toIso8601String() }}')"
                     x-init="start()"
                    @endif>
                    @if($supply->draft_id && $supply->draft_created_at)
                        <div class="mb-3">
                            <template x-if="!expired">
                                <div
                                    class="alert d-flex align-items-center gap-3 mb-0 py-2"
                                    :class="{
                                 'alert-success': remaining >= 900,
                                 'alert-warning': remaining >= 300 && remaining < 900,
                                 'alert-danger': remaining < 300
                             }">
                                    <i class="fas fa-clock"></i>
                                    <span>До истечения черновика:</span>
                                    <strong class="fs-5"
                                            x-text="formatTime(remaining)"></strong>
                                </div>
                            </template>
                            <template x-if="expired">
                                <div>
                                    <div class="alert alert-danger mb-2">
                                        <strong>Черновик истёк!</strong>
                                        Создайте новый.
                                    </div>
                                    <form
                                        action="{{ route('marketplace_supplies.destroy', $supply->id) }}"
                                        method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-danger"
                                                onclick="return confirm('Удалить поставку?')">
                                            <i class="fas fa-trash"></i> Удалить
                                            черновик
                                        </button>
                                    </form>
                                </div>
                            </template>
                        </div>
                        <div x-show="!expired">
                            @livewire('ozon-fbo-item-search', ['supply' => $supply])
                        </div>
                    @else
                        @livewire('ozon-fbo-item-search', ['supply' => $supply])
                    @endif
                </div>
            </div>
        @endif
    </div>
@stop

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('draftTimer', (createdAt) => ({
                remaining: 0,
                expired: false,
                interval: null,

                start() {
                    this.tick(createdAt);
                    this.interval = setInterval(() => this.tick(createdAt), 1000);
                },

                tick(createdAt) {
                    const created = new Date(createdAt);
                    const expires = new Date(created.getTime() + 30 * 60 * 1000);
                    const diff = Math.max(0, Math.floor((expires - Date.now()) / 1000));
                    this.remaining = diff;
                    this.expired = diff <= 0;
                    if (this.expired && this.interval) {
                        clearInterval(this.interval);
                    }
                },

                formatTime(seconds) {
                    const m = Math.floor(seconds / 60);
                    const s = seconds % 60;
                    return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                },
            }));
        });
    </script>

    <script>
        function handleDeleteSubmit(form) {
            if (!confirm('Удалить поставку? Отмена будет отправлена в OZON.')) {
                return false;
            }
            var btn = form.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.querySelector('.delete-label').style.display = 'none';
            btn.querySelector('.delete-spinner').style.display = '';
            return true;
        }
    </script>
@endpush
