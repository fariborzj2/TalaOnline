document.addEventListener('DOMContentLoaded', async function() {
    console.log('Charts script initialized');

    const persianNumberFormatter = new Intl.NumberFormat('fa-IR');

    const toPersianDigits = (num) => {
        if (num === null || num === undefined) return '';
        return persianNumberFormatter.format(num);
    };

    // Formatters using native Intl
    const jalaliDateFormatter = new Intl.DateTimeFormat('fa-IR', { calendar: 'persian', year: 'numeric', month: 'long', day: 'numeric' });
    const jalaliMonthDayFormatter = new Intl.DateTimeFormat('fa-IR', { calendar: 'persian', month: 'long', day: 'numeric' });
    const jalaliMonthFormatter = new Intl.DateTimeFormat('fa-IR', { calendar: 'persian', month: 'long' });
    const jalaliYearFormatter = new Intl.DateTimeFormat('fa-IR', { calendar: 'persian', year: 'numeric' });

    const formatJalali = (dateStr) => {
        const date = new Date(dateStr);
        return jalaliDateFormatter.format(date);
    };

    const getJalaliMonth = (dateStr) => {
        const date = new Date(dateStr);
        return jalaliMonthFormatter.format(date);
    };

    const getJalaliYear = (dateStr) => {
        const date = new Date(dateStr);
        return jalaliYearFormatter.format(date);
    };

    // --- State ---
    let currentAsset = 'gold';
    let currentPeriodDays = 7;
    let allData = { gold: [], silver: [] };
    let chart = null;

    /**
     * Fetch raw data from JSON file
     */
    const fetchData = async () => {
        try {
            console.log('Fetching data...');
            const response = await fetch('data/prices.json');
            if (!response.ok) throw new Error('Failed to fetch price data');
            allData = await response.json();
            console.log('Data fetched successfully', {
                gold: allData.gold?.length,
                silver: allData.silver?.length
            });
            return true;
        } catch (error) {
            console.error('Error loading chart data:', error);
            return false;
        }
    };

    /**
     * Aggregates raw data into specific intervals based on range.
     */
    const getProcessedData = () => {
        const rawData = allData[currentAsset] || [];
        const slicedData = rawData.slice(-currentPeriodDays);

        if (currentPeriodDays === 7) {
            return slicedData.map(d => ({
                x: '\u200f' + jalaliMonthDayFormatter.format(new Date(d.date)),
                y: d.price,
                fullDate: formatJalali(d.date)
            }));
        } else if (currentPeriodDays === 30) {
            return aggregateData(slicedData, 3, 10, 'short');
        } else if (currentPeriodDays === 365) {
            return aggregateData(slicedData, 30, 12, 'month');
        }
        return slicedData.map(d => ({
            x: jalaliDateFormatter.format(new Date(d.date)),
            y: d.price,
            fullDate: formatJalali(d.date)
        }));
    };

    /**
     * Aggregates data by interval and calculates average price.
     */
    const aggregateData = (data, interval, targetCount, labelType) => {
        let result = [];
        for (let i = data.length - 1; i >= 0; i -= interval) {
            let chunk = data.slice(Math.max(0, i - interval + 1), i + 1);
            if (chunk.length === 0) continue;

            const avgY = chunk.reduce((sum, p) => sum + p.price, 0) / chunk.length;
            const lastDate = chunk[chunk.length - 1].date;

            let label = '';
            if (labelType === 'month') {
                label = jalaliMonthFormatter.format(new Date(lastDate));
            } else if (labelType === 'short') {
                label = jalaliMonthDayFormatter.format(new Date(lastDate));
            } else {
                label = formatJalali(lastDate);
            }

            result.unshift({
                x: '\u200f' + label,
                y: Math.floor(avgY),
                fullDate: formatJalali(lastDate)
            });
            if (result.length === targetCount) break;
        }
        return result;
    };

    const initChart = () => {
        const processedData = getProcessedData();

        const options = {
            series: [{
                name: currentAsset === 'gold' ? 'قیمت طلا' : 'قیمت نقره',
                data: processedData
            }],
            chart: {
                type: 'area',
                height: 200,
                toolbar: { show: false },
                fontFamily: 'Vazirmatn, Tahoma, sans-serif',
                animations: { enabled: true },
                rtl: false,
                sparkline: { enabled: false }
            },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 3, colors: ['#e29b21'] },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    opacityTo: 0.05,
                    colorStops: [
                        { offset: 0, color: '#e29b21', opacity: 0.4 },
                        { offset: 100, color: '#e29b21', opacity: 0 }
                    ]
                }
            },
            markers: {
                size: 0,
                hover: { size: 5, sizeOffset: 3 }
            },
            xaxis: {
                type: 'category',
                labels: {
                    style: { colors: '#596486', fontFamily: 'Vazirmatn', fontSize: '11px' },
                    rotate: 0,
                    rotateAlways: false,
                    hideOverlappingLabels: true,
                    offsetY: 5,
                    trim: false
                },
                axisBorder: { show: false },
                axisTicks: { show: false },
                tooltip: { enabled: false }
            },
            yaxis: {
                show: true,
                labels: {
                    offsetX: 0,
            formatter: (val) => toPersianDigits(val),
                    style: { colors: '#596486', fontFamily: 'Vazirmatn', fontSize: '10px' }
                },
                tooltip: { enabled: false }
            },
            grid: {
                show: true,
                borderColor: '#f1f5f9',
                xaxis: { lines: { show: false } },
                yaxis: { lines: { show: true } },
                padding: {
                    left: 5,
                    right: 25,
                    top: 0,
                    bottom: 0
                }
            },
            tooltip: {
                theme: 'light',
                x: {
                    show: true,
                    formatter: function(val, { series, seriesIndex, dataPointIndex, w }) {
                        const data = w.config.series[seriesIndex].data[dataPointIndex];
                        return data ? data.fullDate : val;
                    }
                },
                y: {
                    formatter: function (val) {
            return toPersianDigits(val) + ' تومان';
                    }
                }
            },
            colors: ['#e29b21'],
            responsive: [{
                breakpoint: 768,
                options: {
                    chart: { height: 200 },
                    xaxis: {
                        labels: {
                            rotate: -45,
                            rotateAlways: false,
                            fontSize: '9px',
                            offsetY: 5
                        }
                    },
                    grid: {
                        padding: {
                            right: 35
                        }
                    }
                }
            }]
        };

        const chartElement = document.querySelector("#chart");
        if (chartElement) {
            chart = new ApexCharts(chartElement, options);
            chart.render();
        }
    };

    const updateChart = () => {
        if (!chart) return;

        const data = getProcessedData();
        const color = currentAsset === 'gold' ? '#e29b21' : '#9ca3af';
        const name = currentAsset === 'gold' ? 'قیمت طلا' : 'قیمت نقره';

        chart.updateSeries([{ name: name, data: data }]);
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
            stroke: { colors: [color] }
        });

        // Update High/Low labels in chart box
        if (data.length > 0) {
            const prices = data.map(d => d.y);
            const high = Math.max(...prices);
            const low = Math.min(...prices);

            const highEl = document.querySelector('.chart-high-price');
            const lowEl = document.querySelector('.chart-low-price');

            if (highEl) {
                highEl.textContent = toPersianDigits(high);
                highEl.classList.remove('skeleton');
            }
            if (lowEl) {
                lowEl.textContent = toPersianDigits(low);
                lowEl.classList.remove('skeleton');
            }
        }
    };

    // --- Bootstrapping ---
    const success = await fetchData();
    if (success) {
        initChart();
    } else {
        const chartEl = document.getElementById('chart');
        if (chartEl) chartEl.innerHTML = '<div class="d-flex align-center just-center" style="height: 100%;">خطا در بارگذاری نمودار</div>';
    }

    // --- UI Listeners ---
    document.querySelector('#gold-chart-btn')?.addEventListener('click', function() {
        this.classList.add('active');
        document.querySelector('#silver-chart-btn')?.classList.remove('active');
        currentAsset = 'gold';
        updateChart();
    });

    document.querySelector('#silver-chart-btn')?.addEventListener('click', function() {
        this.classList.add('active');
        document.querySelector('#gold-chart-btn')?.classList.remove('active');
        currentAsset = 'silver';
        updateChart();
    });

    document.querySelector('#period-7d')?.addEventListener('click', function() {
        document.querySelectorAll('.period-toggle .mode-btn').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        currentPeriodDays = 7;
        updateChart();
    });

    document.querySelector('#period-30d')?.addEventListener('click', function() {
        document.querySelectorAll('.period-toggle .mode-btn').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        currentPeriodDays = 30;
        updateChart();
    });

    document.querySelector('#period-1y')?.addEventListener('click', function() {
        document.querySelectorAll('.period-toggle .mode-btn').forEach(btn => btn.classList.remove('active'));
        this.classList.add('active');
        currentPeriodDays = 365;
        updateChart();
    });
});
