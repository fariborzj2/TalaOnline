document.addEventListener('DOMContentLoaded', async function() {
    console.log('Charts script initialized');

    const persianNumberFormatter = new Intl.NumberFormat('fa-IR');
    const toPersianDigits = (num) => {
        if (num === null || num === undefined) return '';
        return persianNumberFormatter.format(num);
    };

    const jalaliDateFormatter = new Intl.DateTimeFormat('fa-IR', { calendar: 'persian', year: 'numeric', month: 'long', day: 'numeric' });
    const jalaliMonthDayFormatter = new Intl.DateTimeFormat('fa-IR', { calendar: 'persian', month: 'long', day: 'numeric' });
    const jalaliMonthFormatter = new Intl.DateTimeFormat('fa-IR', { calendar: 'persian', month: 'long' });

    const formatJalali = (dateStr) => jalaliDateFormatter.format(new Date(dateStr));

    // --- Chart Helper Class ---
    class AssetChart {
        constructor(containerId, symbol = '18ayar') {
            this.containerId = containerId;
            this.symbol = symbol;
            this.periodDays = 7;
            this.chart = null;
            this.data = [];
        }

        async fetchData() {
            try {
                const response = await fetch(`api/prices.php?symbol=${this.symbol}`);
                if (!response.ok) throw new Error('Failed to fetch data');
                const result = await response.json();
                this.data = result[this.symbol] || [];
                return true;
            } catch (error) {
                console.error(`Error loading data for ${this.symbol}:`, error);
                return false;
            }
        }

        getProcessedData() {
            const slicedData = this.data.slice(-this.periodDays);

            if (this.periodDays === 7) {
                return slicedData.map(d => ({
                    x: jalaliMonthDayFormatter.format(new Date(d.date)),
                    y: d.price,
                    fullDate: formatJalali(d.date)
                }));
            } else if (this.periodDays === 30) {
                return this.aggregateData(slicedData, 3, 10, 'short');
            } else if (this.periodDays === 365) {
                return this.aggregateData(slicedData, 30, 12, 'month');
            }
            return slicedData.map(d => ({
                x: jalaliDateFormatter.format(new Date(d.date)),
                y: d.price,
                fullDate: formatJalali(d.date)
            }));
        }

        aggregateData(data, interval, targetCount, labelType) {
            let result = [];
            for (let i = data.length - 1; i >= 0; i -= interval) {
                let chunk = data.slice(Math.max(0, i - interval + 1), i + 1);
                if (chunk.length === 0) continue;
                const avgY = chunk.reduce((sum, p) => sum + p.price, 0) / chunk.length;
                const lastDate = chunk[chunk.length - 1].date;
                let label = labelType === 'month' ? jalaliMonthFormatter.format(new Date(lastDate)) : jalaliMonthDayFormatter.format(new Date(lastDate));
                result.unshift({ x: label, y: Math.floor(avgY), fullDate: formatJalali(lastDate) });
                if (result.length === targetCount) break;
            }
            return result;
        }

        render() {
            const processedData = this.getProcessedData();
            const options = {
                series: [{ name: 'قیمت', data: processedData }],
                chart: {
                    type: 'area',
                    height: '100%',
                    toolbar: { show: false },
                    fontFamily: 'Estedad, Vazirmatn, sans-serif',
                    rtl: true,
                    zoom: { enabled: false }
                },
                stroke: { curve: 'smooth', width: 2, colors: ['#f59e0c'] },
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 100] }
                },
                xaxis: {
                    type: 'category',
                    labels: { style: { colors: '#64748b', fontSize: '11px' } }
                },
                yaxis: {
                    labels: {
                        formatter: (val) => toPersianDigits(val),
                        style: { colors: '#64748b', fontSize: '11px' }
                    }
                },
                grid: { borderColor: '#eee', strokeDashArray: 4 },
                tooltip: {
                    theme: 'light',
                    x: { formatter: (val, { seriesIndex, dataPointIndex, w }) => w.config.series[seriesIndex].data[dataPointIndex].fullDate },
                    y: { formatter: (val) => toPersianDigits(val) + ' تومان' }
                },
                colors: ['#f59e0c']
            };

            const el = document.querySelector(this.containerId);
            if (!el) return;

            if (processedData.length === 0) {
                el.innerHTML = '<div class="d-flex align-center just-center text-gray" style="height: 100%;">داده‌ای برای این بازه موجود نیست</div>';
                return;
            }

            if (this.chart) {
                this.chart.destroy();
                el.innerHTML = ''; // Clear "no data" message if any
            }

            this.chart = new ApexCharts(el, options);
            this.chart.render();
        }

        async updatePeriod(days) {
            this.periodDays = days;
            if (this.chart) {
                const data = this.getProcessedData();
                this.chart.updateSeries([{ name: 'قیمت', data: data }]);
            }
        }
    }

    // Initialize Main Chart
    const firstSelectedAsset = document.querySelector('.chart-toggle-btn');
    const initialSymbol = firstSelectedAsset ? firstSelectedAsset.getAttribute('data-symbol') : '18ayar';

    const mainChart = new AssetChart('#chart', initialSymbol);
    window.mainChart = mainChart;

    if (await mainChart.fetchData()) {
        mainChart.render();
    }

    // Modal Chart Global Access
    window.AssetChart = AssetChart;

    // --- Main Chart Listeners ---
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('chart-toggle-btn')) {
            const btn = e.target;
            btn.parentElement.querySelectorAll('button').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            const symbol = btn.getAttribute('data-symbol');
            mainChart.symbol = symbol;
            mainChart.fetchData().then(() => mainChart.render());
        }
    });

    document.querySelectorAll('.period-toggle .mode-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            this.parentElement.querySelectorAll('button').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const days = this.id === 'period-7d' ? 7 : (this.id === 'period-30d' ? 30 : 365);
            mainChart.updatePeriod(days);
        });
    });
});
