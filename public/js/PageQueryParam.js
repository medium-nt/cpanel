function updatePageWithQueryParam(selectElement) {
    const paramName = selectElement.name;
    const paramValue = selectElement.value;

    const urlParams = new URLSearchParams(window.location.search);

    // Проверяем, есть ли уже параметр с таким именем, и удаляем его перед установкой нового значения
    urlParams.delete(paramName);

    // Добавляем новый параметр
    if (paramValue !== '' && paramValue !== 'all') {
        urlParams.append(paramName, paramValue);
    }

    window.location.assign(`${window.location.origin}${window.location.pathname}?${urlParams}`);
}
