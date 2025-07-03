<div class="barcode-scanner">
    <div class="mb-3">
        <label for="{{ $name }}" class="form-label">Штрихкод</label>
        <input type="text" name="{{ $name }}" id="{{ $name }}Input" class="form-control" readonly required>
    </div>

    <div class="mb-3">
        <label for="{{ $name }}CameraSelect" class="form-label">Выберите камеру</label>
        <select id="{{ $name }}CameraSelect" class="form-select"></select>
    </div>

    <div class="mb-3 d-flex gap-2">
        <button type="button" id="{{ $name }}ScanButton" class="btn btn-primary">📷 Сканировать</button>
        <button type="button" id="{{ $name }}StopButton" class="btn btn-danger">🛑 Остановить</button>
    </div>

    <video id="{{ $name }}Preview" style="width: 100%; max-height: 300px;" class="mb-3" autoplay muted playsinline></video>
    <audio id="{{ $name }}Beep" src="{{ asset('sounds/beep.mp3') }}" preload="auto"></audio>
</div>

@once
    @push('scripts')
        <script type="module">
            import { BrowserMultiFormatReader } from 'https://cdn.jsdelivr.net/npm/@zxing/browser@0.0.10/+esm';
            import { DecodeHintType, BarcodeFormat } from 'https://cdn.jsdelivr.net/npm/@zxing/library@0.18.6/+esm';

            window.initBarcodeScanner = async function(name) {
                const codeReader = new BrowserMultiFormatReader();
                const input = document.getElementById(name + 'Input');
                const preview = document.getElementById(name + 'Preview');
                const scanButton = document.getElementById(name + 'ScanButton');
                const stopButton = document.getElementById(name + 'StopButton');
                const cameraSelect = document.getElementById(name + 'CameraSelect');
                const beep = document.getElementById(name + 'Beep');

                let currentStream = null;
                let scanned = false;

                const devices = await BrowserMultiFormatReader.listVideoInputDevices();
                if (!devices.length) {
                    alert('Камера не найдена. Разрешите доступ и откройте сайт в Safari.');
                    return;
                }

                devices.forEach(device => {
                    const option = document.createElement('option');
                    option.value = device.deviceId;
                    option.text = device.label || `Камера ${cameraSelect.length + 1}`;
                    cameraSelect.appendChild(option);
                });

                const hints = new Map();
                hints.set(DecodeHintType.POSSIBLE_FORMATS, [
                    BarcodeFormat.CODE_128,
                    BarcodeFormat.QR_CODE
                ]);
                hints.set(DecodeHintType.TRY_HARDER, true);
                codeReader.setHints(hints);

                scanButton.addEventListener('click', async () => {
                    const selectedDeviceId = cameraSelect.value;
                    if (!selectedDeviceId) {
                        alert('Выберите камеру');
                        return;
                    }

                    // 🔊 Разогреваем звук
                    if (beep) {
                        try {
                            await beep.play();
                            beep.pause();
                            beep.currentTime = 0;
                        } catch (e) {
                            console.warn('Не удалось активировать звук заранее:', e);
                        }
                    }

                    if (currentStream) {
                        currentStream.getTracks().forEach(track => track.stop());
                        preview.srcObject = null;
                        currentStream = null;
                    }

                    scanned = false;

                    try {
                        const stream = await navigator.mediaDevices.getUserMedia({
                            video: {
                                deviceId: { exact: selectedDeviceId },
                                facingMode: 'environment'
                            }
                        });

                        currentStream = stream;
                        preview.srcObject = stream;
                        preview.play();

                        const track = stream.getVideoTracks()[0];
                        if (track.getCapabilities && track.applyConstraints) {
                            const capabilities = track.getCapabilities();
                            if (capabilities.focusMode?.includes('continuous')) {
                                await track.applyConstraints({
                                    advanced: [{ focusMode: 'continuous' }]
                                });
                            }
                        }

                        await codeReader.decodeFromVideoElement(preview, (result, err) => {
                            if (result && !scanned) {
                                scanned = true;
                                input.value = result.getText();

                                if (beep) {
                                    beep.currentTime = 0;
                                    beep.play().catch(err => console.warn('Ошибка воспроизведения звука:', err));
                                }

                                if (currentStream) {
                                    currentStream.getTracks().forEach(track => track.stop());
                                    preview.srcObject = null;
                                    currentStream = null;
                                }
                            }
                        });

                    } catch (e) {
                        console.error(e);
                        alert('Ошибка при доступе к камере');
                    }
                });

                stopButton.addEventListener('click', () => {
                    if (currentStream) {
                        currentStream.getTracks().forEach(track => track.stop());
                        preview.srcObject = null;
                        currentStream = null;
                    }
                });
            };
        </script>
    @endpush
@endonce

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.initBarcodeScanner(@json($name));
        });
    </script>
@endpush
