<div class="modal fade" id="barcodeModal" tabindex="-1" role="dialog" aria-labelledby="barcodeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form id="barcodeForm" action="{{ route('sticker_printing') }}">
            <div class="modal-content">
                <div class="modal-body">
                    <input type="text" style="color: white; caret-color: #000" class="form-control"
                           placeholder="отсканируйте ваш персональный штрих-код"
                           id="barcodeInput" name="barcode" autocomplete="off" autofocus>
                </div>
            </div>
        </form>
    </div>
</div>
