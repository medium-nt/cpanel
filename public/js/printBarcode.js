async function printBarcode(url) {
    const iframe = document.getElementById('printFrame');

    // Если URL относительный (начинается с /), добавляем схему и хост
    if (url.startsWith('/')) {
        url = window.location.protocol + '//' + window.location.host + url;
    }

    try {
        const response = await fetch(url);
        const blob = await response.blob();
        const blobUrl = URL.createObjectURL(blob);

        iframe.onload = () => {
            try {
                iframe.contentWindow.focus();
                iframe.contentWindow.print();
            } catch (e) {
                console.error(e);
            }
            URL.revokeObjectURL(blobUrl);
        };

        iframe.src = blobUrl;
    } catch (e) {
        console.error('Error loading PDF:', e);
    }
}
