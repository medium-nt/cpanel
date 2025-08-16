document.addEventListener('DOMContentLoaded', function () {
    for (let i = 0; i < 6; i++) { // до 5, потому что from_6 не нужно заполнять
        const toInput = document.getElementById(`to_${i}`);
        const nextFromInput = document.getElementById(`from_${i + 1}`);

        if (toInput && nextFromInput) {
            toInput.addEventListener('input', function () {
                nextFromInput.value = this.value;
            });
        }
    }
});

document.addEventListener('DOMContentLoaded', function () {
    const toFields = document.querySelectorAll('input[name="to[]"]');
    const rateFields = document.querySelectorAll('input[name="rate[]"]');

    toFields.forEach((toField, index) => {
        const rateField = rateFields[index];

        const updateRateRequirement = () => {
            const value = parseFloat(toField.value);
            if (!isNaN(value) && value > 0) {
                rateField.setAttribute('required', 'required');
            } else {
                rateField.removeAttribute('required');
            }
        };

        // Обновляем при вводе
        toField.addEventListener('input', updateRateRequirement);

        // Инициализация при загрузке
        updateRateRequirement();
    });
});
