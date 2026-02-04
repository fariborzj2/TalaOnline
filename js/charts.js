document.addEventListener('DOMContentLoaded', function() {
    const generateData = (base, count, volatility) => {
        let data = [];
        let currentPrice = base;
        for (let i = count; i >= 0; i--) {
            let date = new Date();
            date.setDate(date.getDate() - i);
            let year = 1404; // Simplified Jalali mock
            let month = date.getMonth() + 1;
            let day = date.getDate();
            let dateStr = `${year}/${month.toString().padStart(2, '0')}/${day.toString().padStart(2, '0')}`;

            currentPrice += (Math.random() - 0.5) * volatility;
            data.push({ x: dateStr, y: Math.floor(currentPrice) });
        }
        return data;
    };

    const goldData = generateData(19400000, 60, 200000);
    const silverData = generateData(18400000, 60, 150000);

    const options = {
        series: [{
            name: 'قیمت طلا',
            data: goldData
        }],
        chart: {
            type: 'area',
            height: 250,
            toolbar: {
                show: false
            },
            fontFamily: 'Vazirmatn, Tahoma, sans-serif',
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800,
            }
        },
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 3,
            colors: ['#e29b21']
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.45,
                opacityTo: 0.05,
                stops: [50, 100],
                colorStops: [
                    {
                        offset: 0,
                        color: '#e29b21',
                        opacity: 0.4
                    },
                    {
                        offset: 100,
                        color: '#e29b21',
                        opacity: 0
                    }
                ]
            }
        },
        xaxis: {
            labels: {
                style: {
                    colors: '#596486',
                    fontFamily: 'Vazirmatn'
                },
                rotate: -45,
                rotateAlways: false,
                hideOverlappingLabels: true,
            },
            axisBorder: {
                show: false
            },
            axisTicks: {
                show: false
            },
            tooltip: {
                enabled: false
            }
        },
        yaxis: {
            show: false
        },
        grid: {
            show: false
        },
        tooltip: {
            theme: 'light',
            style: {
                fontSize: '12px',
                fontFamily: 'Vazirmatn'
            },
            x: {
                show: true
            },
            y: {
                formatter: function (val) {
                    return val.toLocaleString() + ' تومان'
                }
            },
            marker: {
                show: true
            }
        },
        colors: ['#e29b21']
    };

    const chart = new ApexCharts(document.querySelector("#chart"), options);
    chart.render();

    // Toggle logic
    const goldBtn = document.querySelector('#gold-chart-btn');
    const silverBtn = document.querySelector('#silver-chart-btn');

    goldBtn.addEventListener('click', function() {
        goldBtn.classList.add('active');
        silverBtn.classList.remove('active');
        chart.updateSeries([{
            name: 'قیمت طلا',
            data: goldData
        }]);
        chart.updateOptions({
            colors: ['#e29b21'],
            fill: {
                gradient: {
                    colorStops: [
                        { offset: 0, color: '#e29b21', opacity: 0.4 },
                        { offset: 100, color: '#e29b21', opacity: 0 }
                    ]
                }
            },
            stroke: {
                colors: ['#e29b21']
            }
        });
    });

    silverBtn.addEventListener('click', function() {
        silverBtn.classList.add('active');
        goldBtn.classList.remove('active');
        chart.updateSeries([{
            name: 'قیمت نقره',
            data: silverData
        }]);
        chart.updateOptions({
            colors: ['#9ca3af'],
            fill: {
                gradient: {
                    colorStops: [
                        { offset: 0, color: '#9ca3af', opacity: 0.4 },
                        { offset: 100, color: '#9ca3af', opacity: 0 }
                    ]
                }
            },
            stroke: {
                colors: ['#9ca3af']
            }
        });
    });
});
