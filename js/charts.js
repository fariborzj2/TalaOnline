document.addEventListener('DOMContentLoaded', function() {
    const toPersianDigits = (str) => {
        if (str === null || str === undefined) return '';
        const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return str.toString().replace(/\d/g, x => persianDigits[x]);
    };

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

    let currentAsset = 'gold';
    let currentPeriod = 7;

    const goldDataFull = generateData(19400000, 365, 200000);
    const silverDataFull = generateData(18400000, 365, 150000);

    const getFilteredData = (asset, days) => {
        const fullData = asset === 'gold' ? goldDataFull : silverDataFull;
        return fullData.slice(-days);
    };

    const options = {
        series: [{
            name: 'قیمت طلا',
            data: getFilteredData('gold', 7)
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
            },
            rtl: false // Force LTR for the chart itself
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
                formatter: function(val) {
                    return toPersianDigits(val);
                }
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
                show: true,
                formatter: function(val) {
                    return toPersianDigits(val);
                }
            },
            y: {
                formatter: function (val) {
                    return toPersianDigits(val.toLocaleString()) + ' تومان'
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

    const updateChart = () => {
        const data = getFilteredData(currentAsset, currentPeriod);
        const name = currentAsset === 'gold' ? 'قیمت طلا' : 'قیمت نقره';
        const color = currentAsset === 'gold' ? '#e29b21' : '#9ca3af';

        chart.updateSeries([{
            name: name,
            data: data
        }]);

        chart.updateOptions({
            colors: [color],
            fill: {
                gradient: {
                    colorStops: [
                        { offset: 0, color: color, opacity: 0.4 },
                        { offset: 100, color: color, opacity: 0 }
                    ]
                }
            },
            stroke: {
                colors: [color]
            }
        });
    };

    // Asset Toggle logic
    const goldBtn = document.querySelector('#gold-chart-btn');
    const silverBtn = document.querySelector('#silver-chart-btn');

    goldBtn.addEventListener('click', function() {
        goldBtn.classList.add('active');
        silverBtn.classList.remove('active');
        currentAsset = 'gold';
        updateChart();
    });

    silverBtn.addEventListener('click', function() {
        silverBtn.classList.add('active');
        goldBtn.classList.remove('active');
        currentAsset = 'silver';
        updateChart();
    });

    // Period Toggle logic
    const period7d = document.querySelector('#period-7d');
    const period30d = document.querySelector('#period-30d');
    const period1y = document.querySelector('#period-1y');

    const periodBtns = [period7d, period30d, period1y];

    period7d.addEventListener('click', function() {
        periodBtns.forEach(btn => btn.classList.remove('active'));
        period7d.classList.add('active');
        currentPeriod = 7;
        updateChart();
    });

    period30d.addEventListener('click', function() {
        periodBtns.forEach(btn => btn.classList.remove('active'));
        period30d.classList.add('active');
        currentPeriod = 30;
        updateChart();
    });

    period1y.addEventListener('click', function() {
        periodBtns.forEach(btn => btn.classList.remove('active'));
        period1y.classList.add('active');
        currentPeriod = 365;
        updateChart();
    });
});
