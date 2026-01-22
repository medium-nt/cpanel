<div class="modal fade" id="idleModal" data-backdrop="static"
     data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Неактивность</h5>
            </div>
            <div class="modal-body text-center">
                <p class="h4">Выход из системы через:</p>
                <p class="display-4 font-weight-bold" id="idleCountdown">60</p>
                <p class="h5">секунд</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary btn-lg"
                        id="stayBtn">Остаться
                </button>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/idleTimer.js') }}"></script>
<script>
    new window.IdleTimer({
        redirectUrl: '{{ route('kiosk', ['idle' => true]) }}'
    });
</script>
