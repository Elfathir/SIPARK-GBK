/* assets/js/chart.js */
let areaChart = null;
let barChart = null;

document.addEventListener('DOMContentLoaded', function() {
    // Check if Chart.js is loaded
    if (typeof Chart === 'undefined') return;

    // 1. AREA CHART: Grafik Kendaraan Masuk & Keluar
    const areaChartEl = document.getElementById('areaChart');
    if (areaChartEl) {
        const ctxArea = areaChartEl.getContext('2d');
        
        // Create gradients
        const gradientIn = ctxArea.createLinearGradient(0, 0, 0, 300);
        gradientIn.addColorStop(0, 'rgba(71, 201, 123, 0.4)');
        gradientIn.addColorStop(1, 'rgba(71, 201, 123, 0.0)');

        const gradientOut = ctxArea.createLinearGradient(0, 0, 0, 300);
        gradientOut.addColorStop(0, 'rgba(213, 84, 84, 0.4)');
        gradientOut.addColorStop(1, 'rgba(213, 84, 84, 0.0)');

        areaChart = new Chart(ctxArea, {
            type: 'line',
            data: {
                labels: chartLabel,
                datasets: [
                    {
                        label: 'Kendaraan Masuk',
                        data: chartMasuk,
                        borderColor: '#47C97B',
                        backgroundColor: gradientIn,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#47C97B',
                        pointBorderColor: '#FFFFFF',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    },
                    {
                        label: 'Kendaraan Keluar',
                        data: chartKeluar,
                        borderColor: '#D55454',
                        backgroundColor: gradientOut,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#D55454',
                        pointBorderColor: '#FFFFFF',
                        pointBorderWidth: 2,
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                family: 'Poppins',
                                size: 12
                            },
                            usePointStyle: true,
                            boxWidth: 8
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1F2937',
                        titleFont: { family: 'Poppins', size: 12 },
                        bodyFont: { family: 'Poppins', size: 12 },
                        padding: 12,
                        cornerRadius: 10,
                        displayColors: true
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { family: 'Poppins', size: 11 }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        min: 0,
                        max: 1000,
                        grid: {
                            color: 'rgba(31, 41, 55, 0.05)'
                        },
                        ticks: {
                            stepSize: 100, // interval
                            font: { family: 'Poppins', size: 11 }
                        }
                    }
                }
            }
        });
    }

    // 2. BAR CHART: Transaksi Hari Ini
    const barChartEl = document.getElementById('barChart');
    if (barChartEl) {
        const ctxBar = barChartEl.getContext('2d');
        
        // Create gradient for bars
        const gradientBar = ctxBar.createLinearGradient(0, 0, 0, 300);
        gradientBar.addColorStop(0, '#D55454');
        gradientBar.addColorStop(1, '#9E3A3A');

        barChart = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: barLabel,
                datasets: [
                    {
                        label: 'Transaksi',
                        data: barData,
                        backgroundColor: gradientBar,
                        borderRadius: 8,
                        borderSkipped: false,
                        barThickness: 8,
                        maxBarThicness: 12,
                        categoryPercentage: 0.4,
                        barPercentage: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: '#1F2937',
                        titleFont: { family: 'Poppins', size: 12 },
                        bodyFont: { family: 'Poppins', size: 12 },
                        padding: 12,
                        cornerRadius: 10
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: { family: 'Poppins', size: 11 }
                        }
                    },
                    y: {
                        grid: {
                            color: 'rgba(31, 41, 55, 0.05)'
                        },
                        ticks: {
                            font: { family: 'Poppins', size: 11 },
                            beginAtZero: true
                        }
                    }
                }
            }
        });
    }
});

function refreshCharts(data){

    if(!areaChart || !barChart){
        return;
    }

    areaChart.data.labels = data.chartLabel;
    areaChart.data.datasets[0].data = data.chartMasuk;
    areaChart.data.datasets[1].data = data.chartKeluar;
    areaChart.update();

    barChart.data.labels = data.barLabel;
    barChart.data.datasets[0].data = data.barData;
    barChart.update();

}