{{--
    Поле аватара с обрезкой в браузере (Cropper.js).
    Переиспользуется в профиле сотрудника и в карточке пользователя (админ).
    Переменные: $user (App\Models\User), $formId (id формы для перехвата submit).
--}}
@push('css')
    <link href="{{ asset('vendor/cropperjs/cropper.min.css') }}"
          rel="stylesheet">
@endpush

<div class="form-group">
    <label for="avatarInput">Аватар</label>
    <input class="form-control" type="file" name="avatar" id="avatarInput"
           accept="image/png,image/jpeg,image/webp,image/gif">
    <small class="form-text text-muted">
        JPG, PNG, WebP или GIF до 2 МБ. После выбора обрежьте лицо в квадрат.
    </small>
    @if(! empty($user->avatar))
        <div class="mt-2">
            <img src="{{ asset('storage/'.$user->avatar) }}"
                 style="width:64px;height:64px;border-radius:50%"
                 alt="Текущий аватар">
        </div>
    @endif
    <div id="avatarCropWrap"
         style="display:none;max-width:360px;margin-top:10px">
        <img id="avatarCropImage" src="" style="max-width:100%"
             alt="Предпросмотр обрезки">
    </div>
</div>

@push('js')
    <script src="{{ asset('vendor/cropperjs/cropper.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form = document.getElementById('{{ $formId }}');
            var input = document.getElementById('avatarInput');
            var cropWrap = document.getElementById('avatarCropWrap');
            var cropImg = document.getElementById('avatarCropImage');
            if (!form || !input) return;

            var cropper = null;

            input.addEventListener('change', function (e) {
                var file = e.target.files && e.target.files[0];
                if (!file) return;
                var reader = new FileReader();
                reader.onload = function (ev) {
                    cropImg.src = ev.target.result;
                    cropWrap.style.display = 'block';
                    if (cropper) cropper.destroy();
                    cropper = new Cropper(cropImg, {
                        aspectRatio: 1,
                        viewMode: 1,
                        autoCropArea: 1,
                        background: false,
                        responsive: true
                    });
                };
                reader.readAsDataURL(file);
            });

            form.addEventListener('submit', function (e) {
                if (!cropper) return; // аватар не выбран — обычная отправка формы
                e.preventDefault();

                var btn = form.querySelector('button[type=submit]');
                if (btn) {
                    btn.disabled = true;
                    btn.textContent = 'Сохранение…';
                }

                cropper.getCroppedCanvas({
                    width: 256,
                    height: 256,
                    imageSmoothingQuality: 'high'
                }).toBlob(function (blob) {
                    var fd = new FormData(form);
                    fd.set('avatar', blob, 'avatar.png');

                    fetch(form.getAttribute('action'), {
                        method: 'POST',
                        headers: {'Accept': 'application/json'},
                        body: fd,
                        redirect: 'follow'
                    }).then(function (resp) {
                        if (resp.ok) {
                            if (window.toastr) toastr.success('Аватар сохранён');
                            setTimeout(function () {
                                window.location.reload();
                            }, 700);
                        } else if (resp.status === 422) {
                            resp.json().then(function (data) {
                                var msg = (data.errors && data.errors.avatar)
                                    ? data.errors.avatar[0]
                                    : (data.message || 'Ошибка валидации');
                                if (window.toastr) toastr.error(msg);
                                if (btn) {
                                    btn.disabled = false;
                                    btn.textContent = 'Сохранить';
                                }
                            });
                        } else {
                            if (window.toastr) toastr.error('Ошибка сохранения');
                            if (btn) {
                                btn.disabled = false;
                                btn.textContent = 'Сохранить';
                            }
                        }
                    }).catch(function () {
                        if (window.toastr) toastr.error('Ошибка сети');
                        if (btn) {
                            btn.disabled = false;
                            btn.textContent = 'Сохранить';
                        }
                    });
                }, 'image/png');
            });
        });
    </script>
@endpush
