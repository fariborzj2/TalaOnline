document.addEventListener('DOMContentLoaded', function() {
    /**
     * Utility to convert English digits to Persian digits.
     * @param {string|number} str
     * @returns {string}
     */
    const toPersianDigits = (str) => {
        if (str === null || str === undefined) return '';
        const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return str.toString().replace(/\d/g, x => persianDigits[x]);
    };

    const persianMonths = [
        'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور',
        'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند'
    ];

    /**
     * Mock data generation for daily prices.
     * Generates a continuous stream of daily data.
     * @param {number} base - Starting price
     * @param {number} count - Number of days
     * @param {number} volatility - Random price fluctuation
     */
    const generateDailyData = (base, count, volatility) => {
        let data = [];
        let currentPrice = base;
        const today = new Date();

        for (let i = count; i >= 0; i--) {
            let date = new Date(today);
            date.setDate(today.getDate() - i);

            // Simplified Jalali date mock for display
            let jYear = date.getFullYear() - 621;
            let jMonth = date.getMonth() + 1;
            let jDay = date.getDate();

            // Basic adjustment for Jalali months shift (approximate)
            // Gregorian Jan (1) is approx Dey (10) in Jalali
            let adjustedMonth = jMonth + 9;
            if (adjustedMonth > 12) {
                adjustedMonth -= 12;
            } else {
                jYear--;
            }

            let dateStr = `${jYear}/${adjustedMonth.toString().padStart(2, '0')}/${jDay.toString().padStart(2, '0')}`;

            currentPrice += (Math.random() - 0.5) * volatility;
            data.push({ x: dateStr, y: Math.floor(currentPrice) });
        }
        return data;
    };

    /**
     * Aggregates daily data into specific intervals based on the selected range.
     * 7 days -> Daily (7 points)
     * 30 days -> 3-day intervals (~10 points)
     * 1 year -> Monthly intervals (12 points)
     * @param {Array} data - Array of {x, y} daily data
     * @param {number} rangeDays - The total range in days
     */
    const getAggregatedData = (data, rangeDays) => {
        const slicedData = data.slice(-rangeDays);

        if (rangeDays === 7) {
            // Requirement: Show daily data (7 points)
            return slicedData;
        } else if (rangeDays === 30) {
            // Requirement: Aggregate data into 3-day intervals (10 points total)
            return aggregateByInterval(slicedData, 3, 10);
        } else if (rangeDays === 365) {
            // Requirement: Aggregate data monthly (12 points total)
            const interval = Math.ceil(slicedData.length / 12);
            return aggregateByInterval(slicedData, interval, 12);
        }
        return slicedData;
    };

    /**
     * Core aggregation logic: groups data by interval and calculates average price.
     * @param {Array} data - The daily data to aggregate
     * @param {number} interval - Number of days per point
     * @param {number} targetCount - Final number of points desired
     */
    const aggregateByInterval = (data, interval, targetCount) => {
        let result = [];
        for (let i = data.length - 1; i >= 0; i -= interval) {
            let chunk = data.slice(Math.max(0, i - interval + 1), i + 1);
            if (chunk.length === 0) continue;

            const avgY = chunk.reduce((sum, p) => sum + p.y, 0) / chunk.length;
            const labelX = chunk[chunk.length - 1].x;

            result.unshift({ x: labelX, y: Math.floor(avgY) });

            if (result.length === targetCount) break;
        }
        return result;
    };

    // --- Chart State ---
    let currentAsset = 'gold';
    let currentPeriodDays = 7;

    const goldDaily = generateDailyData(19400000, 370, 200000);
    const silverDaily = generateDailyData(18400000, 370, 150000);

    const getProcessedData = () => {
        const rawData = currentAsset === 'gold' ? goldDaily : silverDaily;
        return getAggregatedData(rawData, currentPeriodDays);
    };

    const options = {
        series: [{
            name: 'قیمت طلا',
            data: getProcessedData()
        }],
        chart: {
            type: 'area',
            height: 250,
            toolbar: { show: false },
            fontFamily: 'Vazirmatn, Tahoma, sans-serif',
            animations: {
                enabled: true,
                easing: 'easeinout',
                speed: 800,
            },
            rtl: false
        },
        dataLabels: { enabled: false },
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
                    { offset: 0, color: '#e29b21', opacity: 0.4 },
                    { offset: 100, color: '#e29b21', opacity: 0 }
                ]
            }
        },
        xaxis: {
            labels: {
                style: {
                    colors: '#596486',
                    fontFamily: 'Vazirmatn'
                },
                rotate: 0,
                hideOverlappingLabels: true,
                formatter: function(val) {
                    if (currentPeriodDays === 365 && typeof val === 'string') {
                        const parts = val.split('/');
                        if (parts.length >= 2) {
                            const monthIdx = parseInt(parts[1]) - 1;
                            if (monthIdx >= 0 && monthIdx < 12) {
                                return persianMonths[monthIdx];
                            }
                        }
                    }
                    return toPersianDigits(val);
                }
            },
            axisBorder: { show: false },
            axisTicks: { show: false },
            tooltip: { enabled: false }
        },
        yaxis: { show: false },
        grid: { show: false },
        tooltip: {
            theme: 'light',
            style: {
                fontSize: '12px',
                fontFamily: 'Vazirmatn'
            },
            x: {
                show: true,
                formatter: function(val) {
                    if (currentPeriodDays === 365 && typeof val === 'string') {
                        const parts = val.split('/');
                        if (parts.length >= 2) {
                            const monthIdx = parseInt(parts[1]) - 1;
                            if (monthIdx >= 0 && monthIdx < 12) {
                                return persianMonths[monthIdx] + ' ' + toPersianDigits(parts[0]);
                            }
                        }
                    }
                    return toPersianDigits(val);
                }
            },
            y: {
                formatter: function (val) {
                    return toPersianDigits(val.toLocaleString()) + ' تومان';
                }
            },
            marker: { show: true }
        },
        colors: ['#e29b21']
    };

    const chart = new ApexCharts(document.querySelector("#chart"), options);
    chart.render();

    const updateChart = () => {
        const data = getProcessedData();
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
            stroke: { colors: [color] }
        });
    };

    // --- UI Listeners ---
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

    const period7d = document.querySelector('#period-7d');
    const period30d = document.querySelector('#period-30d');
    const period1y = document.querySelector('#period-1y');
    const periodBtns = [period7d, period30d, period1y];

    period7d.addEventListener('click', function() {
        periodBtns.forEach(btn => btn.classList.remove('active'));
        period7d.classList.add('active');
        currentPeriodDays = 7;
        updateChart();
    });

    period30d.addEventListener('click', function() {
        periodBtns.forEach(btn => btn.classList.remove('active'));
        period30d.classList.add('active');
        currentPeriodDays = 30;
        updateChart();
    });

    period1y.addEventListener('click', function() {
        periodBtns.forEach(btn => btn.classList.remove('active'));
        period1y.classList.add('active');
        currentPeriodDays = 365;
        updateChart();
    });
});
