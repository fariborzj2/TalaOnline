document.addEventListener('DOMContentLoaded', function() {
    const goldData = [
        { x: '1404/02/01', y: 19400000 },
        { x: '1404/02/02', y: 19420000 },
        { x: '1404/02/03', y: 19410000 },
        { x: '1404/02/04', y: 19450000 },
        { x: '1404/02/05', y: 19480000 },
        { x: '1404/02/06', y: 19470000 },
        { x: '1404/02/07', y: 19495719 }
    ];

    const silverData = [
        { x: '1404/02/01', y: 18400000 },
        { x: '1404/02/02', y: 18450000 },
        { x: '1404/02/03', y: 18430000 },
        { x: '1404/02/04', y: 18470000 },
        { x: '1404/02/05', y: 18490000 },
        { x: '1404/02/06', y: 18480000 },
        { x: '1404/02/07', y: 18495719 }
    ];

    const options = {
        series: [{
            name: 'قیمت',
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
                    colors: '#596486'
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
            x: {
                show: true
            },
            y: {
                formatter: function (val) {
                    return val.toLocaleString() + ' تومان'
                }
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
