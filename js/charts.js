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
                x: jalaliMonthDayFormatter.format(new Date(d.date)),
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
                x: label,
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
                height: 280,
                toolbar: { show: false },
                fontFamily: 'Vazirmatn, Tahoma, sans-serif',
                animations: { enabled: true },
                rtl: true,
                sparkline: { enabled: false },
                redrawOnWindowResize: true,
                redrawOnParentResize: true,
                zoom: {
                  enabled: false
                }
            },
            layout: {
                padding: {
                    left: 0,
                    right: 0,
                    top: 0,
                    bottom: 0
                }
            },
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2, colors: ['#e29b21'] },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.45,
                    opacityTo: 0.05,
                    stops: [0, 100]
                }
            },
            markers: {
                size: 0,
                hover: { size: 5, sizeOffset: 2 }
            },
            xaxis: {
                type: 'category',
                tickAmount: 10,
                labels: {
                    style: { colors: 'var(--color-bright)', fontFamily: 'Vazirmatn', fontSize: '11px' },
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
                    align: 'left',
                    offsetX: -10,
                    formatter: (val) => toPersianDigits(val),
                    style: { colors: 'var(--color-bright)', fontFamily: 'Vazirmatn', fontSize: '10px' }
                },
                tooltip: { enabled: false }
            },
            grid: {
                show: true,
                borderColor: 'var(--color-border)',
                xaxis: { lines: { show: false } },
                yaxis: { lines: { show: true, strokeDashArray: 4 } },
                padding: {
                    left: 5,
                    right: 0,
                    top: 0,
                    bottom: 10
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
                    chart: {
                        height: 380,
                    },
                    xaxis: {
                        tickAmount: 6,
                        labels: {
                            rotate: -45,
                            rotateAlways: true,
                            fontSize: '10px',
                            offsetY: 5,
                            style: { colors: '#64748b' },
                            maxHeight: 80,
                            hideOverlappingLabels: true
                        },
                        axisBorder: { show: false }
                    },
                    yaxis: {
                        labels: {
                            formatter: (val) => toPersianDigits(val),
                            style: { fontSize: '9px', colors: '#64748b' },
                            offsetX: -5
                        }
                    },
                    grid: {
                        padding: {
                            left: 10,
                            right: 35,
                            bottom: 60
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
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const goldColor = '#e29b21';
        const silverColor = isDark ? '#94a3b8' : '#64748b';
        const color = currentAsset === 'gold' ? goldColor : silverColor;
        const name = currentAsset === 'gold' ? 'قیمت طلا' : 'قیمت نقره';

        chart.updateSeries([{ name: name, data: data }]);
        chart.updateOptions({
            colors: [color],
            chart: {
                foreColor: isDark ? '#94a3b8' : '#64748b',
            },
            fill: {
                gradient: {
                    colorStops: [
                        { offset: 0, color: color, opacity: 0.4 },
                        { offset: 100, color: color, opacity: 0 }
                    ]
                }
            },
            stroke: { colors: [color] },
            grid: {
                borderColor: isDark ? '#1e293b' : '#e2e8f0'
            },
            tooltip: {
                theme: isDark ? 'dark' : 'light'
            }
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
        updateChart();
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

    // Handle dynamic resize to ensure chart fills container correctly
    let resizeTimeout;
    window.addEventListener('resize', () => {
        if (!chart) return;
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(() => {
            console.log('Resizing chart...');
            chart.render();
        }, 250);
    });

    window.addEventListener('themechanged', () => {
        updateChart();
    });
});
