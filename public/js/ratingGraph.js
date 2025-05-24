const ctx = document.getElementById('ratingGraph').getContext('2d');

let seamstressesData = window.seamstressesData; // Берем данные из глобального объекта
let dates = window.dates; // Берем данные из глобального объекта

const datasets = Object.keys(seamstressesData).map(seamstressId => {
    return {
        label: seamstressesData[seamstressId].name,
        data: Object.keys(seamstressesData[seamstressId]).map(date => seamstressesData[seamstressId][date]).slice(1),
        cubicInterpolationMode: 'monotone'
    };
});

const data = {
    labels: dates,
    datasets: datasets
};

new Chart(ctx, {
    type: 'bar',
    data,
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                min: 1,
                max: 8
            }
        },
        ticks: {
            stepSize: 1
        }
    }
});
