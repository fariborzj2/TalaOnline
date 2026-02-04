document.addEventListener('DOMContentLoaded', async function() {
    console.log('Charts script initialized');

    /**
     * Utility to convert English digits to Persian digits.
     * (Though Intl.DateTimeFormat 'fa-IR' does this automatically)
     */
    const toPersianDigits = (str) => {
        if (str === null || str === undefined) return '';
        const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return str.toString().replace(/\d/g, x => persianDigits[x]);
    };

    // Formatters using native Intl
    const jalaliDateFormatter = new Intl.DateTimeFormat('fa-IR', { calendar: 'persian', year: 'numeric', month: '2-digit', day: '2-digit' });
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
                x: d.date, // Use raw date for logic, format in formatter
                y: d.price
            }));
        } else if (currentPeriodDays === 30) {
            return aggregateData(slicedData, 3, 10);
        } else if (currentPeriodDays === 365) {
            return aggregateData(slicedData, 30, 12);
        }
        return slicedData.map(d => ({
            x: d.date,
            y: d.price
        }));
    };

    /**
     * Aggregates data by interval and calculates average price.
     */
    const aggregateData = (data, interval, targetCount) => {
        let result = [];
        for (let i = data.length - 1; i >= 0; i -= interval) {
            let chunk = data.slice(Math.max(0, i - interval + 1), i + 1);
            if (chunk.length === 0) continue;

            const avgY = chunk.reduce((sum, p) => sum + p.price, 0) / chunk.length;
            const lastDate = chunk[chunk.length - 1].date;

            result.unshift({ x: lastDate, y: Math.floor(avgY) });
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
                height: 250,
                toolbar: { show: false },
                fontFamily: 'Vazirmatn, Tahoma, sans-serif',
                animations: { enabled: true },
                rtl: false
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
            xaxis: {
                type: 'category',
                labels: {
                    style: { colors: '#596486', fontFamily: 'Vazirmatn' },
                    rotate: 0,
                    formatter: function(val) {
                        if (!val) return '';
                        if (currentPeriodDays === 365) {
                            return getJalaliMonth(val);
                        }
                        return formatJalali(val);
                    }
                },
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                show: true,
                labels: {
                    formatter: (val) => toPersianDigits(val.toLocaleString()),
                    style: { colors: '#596486', fontFamily: 'Vazirmatn' }
                }
            },
            grid: {
                show: true,
                borderColor: '#f1f1f1',
                xaxis: { lines: { show: false } },
                yaxis: { lines: { show: true } }
            },
            tooltip: {
                theme: 'light',
                x: {
                    show: true,
                    formatter: function(val) {
                        if (!val) return '';
                        if (currentPeriodDays === 365) {
                            return getJalaliMonth(val) + ' ' + getJalaliYear(val);
                        }
                        return formatJalali(val);
                    }
                },
                y: {
                    formatter: function (val) {
                        return toPersianDigits(val.toLocaleString()) + ' تومان';
                    }
                }
            },
            colors: ['#e29b21']
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
    };

    // --- Bootstrapping ---
    const success = await fetchData();
    if (success) {
        initChart();
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
