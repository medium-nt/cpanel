@extends('layouts.app')

{{-- Customize layout sections --}}

@section('subtitle', $title)
@section('content_header_title', $title)

{{-- Content body: main page content --}}

@section('content_body')
    <div class="col-md-12">
        @if($supply->status == 0)
            <div class="card">
                <div class="card-body">
                    @livewire('supply-order-search', ['supply' => $supply])
                </div>
            </div>
        @endif

        @livewire('supply-order-list', ['supplyId' => $supply->id])

        @if($supply->status == 0)
        <div class="card">
            <div class="card-body">
                @if(!$hasShippedOrders)
                    <a href="{{ route('marketplace_supplies.complete', ['marketplace_supply' => $supply]) }}"
                       class="btn btn-primary mr-3 mb-2"
                       id="complete-supply-btn"
                       data-video-present="{{ $supply->video ? '1' : '0' }}">
                        Закрыть поставку и передать в доставку
                    </a>
                @else
                    <p class="text-danger">Отгрузка невозможна! В заказе есть
                        товары, которые нельзя отгружать!</p>
                @endif

                <div id="spinner-wrapper" style="display: none;">
                    <i class="fas fa-spinner fa-spin fa-lg" style="color: #0d6efd;"></i>
                    <span>Передаем товары в доставку...</span>
                </div>

                @if(auth()->user()->isAdmin())
                    <a href="{{ route('marketplace_supplies.close', ['marketplace_supply' => $supply]) }}"
                       class="btn btn-danger mb-2"
                       onclick="return confirm('Закрыть поставку в ERP принудительно без передачи информации об отгрузке в маркетплейс?')">
                        Закрыть поставку принудительно
                    </a>
                @endif
            </div>
        </div>
        @endif

            @if(auth()->user()->isAdmin() || auth()->user()->isStorekeeper())
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Видео упаковки поставки</h3>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if($supply->video)
                    <div class="video-container" style="margin-bottom: 20px;">
                        <video controls>
                            <source src="{{ asset('storage/videos/' . $supply->video) }}" size="1080">
                        </video>
                    </div>
                    @if($supply->status == 0 || auth()->user()->isAdmin())
                        <a href="{{ route('marketplace_supplies.delete_video', ['marketplace_supply' => $supply]) }}"
                           class="btn btn-danger mr-3 mb-2" onclick="return confirm('Вы уверены что хотите удалить видео?')">
                            Удалить видео
                        </a>
                    @endif
                @else
                    @if($supply->status == 0 || auth()->user()->isAdmin())
                        <span class="text-muted">
                            разрешено загружать максимум 1 видео в формате mp4 (720p), длинной не более 2х минут и размером не более 500мб
                        </span>

                        <form action="{{ route('marketplace_supplies.upload-chunk') }}"
                              class="dropzone"
                              id="videoDropzone">
                            <div class="dz-message">
                                <strong>🎬 Перетащи видео сюда</strong><br>
                                или нажми для выбора файла
                            </div>
                        </form>
                    @else
                        <span class="text-muted">Видео не было загружено</span>
                    @endif
                @endif
            </div>
        </div>
            @endif

            <div class="card">
                <div class="card-body">
                    @if($supply->status == 4)
                    @if($supply->marketplace_id == 1)
                    <a href="{{ route('marketplace_supplies.get_docs', ['marketplace_supply' => $supply]) }}"
                       class="btn btn-primary mr-3 mb-2">Получить документы</a>
                    @endif

                    <a href="{{ route('marketplace_supplies.get_barcode', ['marketplace_supply' => $supply]) }}"
                       class="btn btn-primary mr-3 mb-2">Получить штрихкод поставки</a>

                    <a href="{{ route('marketplace_supplies.done', ['marketplace_supply' => $supply]) }}"
                       class="btn btn-success mr-3 mb-2" onclick="return confirm('Вы уверены что поставка отгружена?')">Поставка отгружена в маркетплейс</a>
                    @endif
                    <a href="{{ route('marketplace_supplies.update_status_orders', ['marketplace_supply' => $supply]) }}"
                       class="btn btn-outline-primary mb-2">Обновить статусы
                        заказов</a>
                </div>
            </div>
    </div>
@stop

@section('js')
    <script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>

    <script>
        Dropzone.autoDiscover = false;

        new Dropzone("#videoDropzone", {
            url: "/megatulle/marketplace_supplies/upload-chunk",
            paramName: "video",
            maxFiles: 1,
            chunking: true,
            forceChunking: true,
            chunkSize: 2 * 1024 * 1024,
            retryChunks: true,
            retryChunksLimit: 2,
            parallelChunkUploads: 3,
            acceptedFiles: "video/mp4",
            dictInvalidFileType: "Формат видео неверный. Попробуй снова!",
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            accept: function(file, done) {
                const maxSize = 500 * 1024 * 1024; // 500 MB в байтах

                if (file.size > maxSize) {
                    done("Файл превышает допустимый размер 500 МБ.");
                } else {
                    done(); // Всё нормально, продолжаем загрузку
                }
            },
            init: function () {
                this.on("sending", function(file, xhr, formData) {
                    formData.append("marketplace_supply_id", "{{ $supply->id }}");
                });

                this.on("maxfilesexceeded", function(file) {
                    this.removeFile(file);
                    alert("Можно загружать только один файл.");
                });

                this.on("success", function(file, response) {
                    // Блокируем дальнейшую загрузку
                    this.removeEventListeners();
                    this.disable();
                    document.querySelector("#videoDropzone").classList.add("dz-disabled");

                    // Выводим сообщение
                    const reloadUrl = "{{ route('marketplace_supplies.show', ['marketplace_supply' => $supply]) }}";
                    document.querySelector("#videoDropzone").insertAdjacentHTML("beforeend", `
                      <div class="reload-message">✅ Видео загружено! <a href="${reloadUrl}">Обновить страницу</a></div>
                    `);

                    document.getElementById('complete-supply-btn').dataset.videoPresent = 1;
                });
            }
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const btn = document.getElementById('complete-supply-btn');
            const spinnerWrapper = document.getElementById('spinner-wrapper');

            btn.addEventListener('click', function (e) {

                if (btn.dataset.videoPresent !== '1') {
                    const confirmed = confirm("Видео упаковки поставки не загружено. Вы уверены, что хотите закрыть поставку?");
                    if (!confirmed) {
                        e.preventDefault();
                        return;
                    }
                }

                btn.style.display = 'none';
                spinnerWrapper.style.display = 'inline-block';
                setTimeout(() => {
                    spinnerWrapper.style.display = 'none';
                    btn.style.display = 'inline-block';
                }, 60000);
            });
        });
    </script>
@stop

@push('css')
    <link href="{{ asset('css/desktop_or_smartphone_card_style.css') }}" rel="stylesheet"/>
    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />
    <link href="{{ asset('css/dropzone.css') }}" rel="stylesheet"/>

    <style>
        .video-container {
            width: 400px;
            max-width: 100%;
        }

        .video-container video {
            width: 100%;
            height: auto;
            object-fit: contain;
        }
    </style>
@endpush
