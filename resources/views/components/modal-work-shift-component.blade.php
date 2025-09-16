<div class="modal fade" id="workShiftModal" tabindex="-1" role="dialog" aria-labelledby="workShiftModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="barcodeForm" action="{{ route('open_close_work_shift') }}">
            <div class="modal-content">
                <div class="modal-body">
                    <input type="text" style="color: white; caret-color: #000" class="form-control"
                           placeholder="отсканируйте ваш персональный штрих-код"
                           id="workShiftInput" name="barcode" autocomplete="off" autofocus>

                    <input type="hidden" name="user_id" value="{{ $userId }}">
                </div>
            </div>
        </form>
    </div>
</div>
