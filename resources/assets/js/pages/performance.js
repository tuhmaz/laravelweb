'use strict';

// Performance charts
let responseChart, queriesChart, cacheChart, loadChart;

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function () {
    initCharts();
    updateMetrics();
    // Update metrics every 30 seconds
    setInterval(updateMetrics, 30000);
});

// Initialize all charts
const initCharts = () => {
    // Response Time Chart
    responseChart = new ApexCharts(document.querySelector("#response-chart"), {
        chart: {
            type: 'area',
            height: 100,
            sparkline: {
                enabled: true
            },
            animations: {
                enabled: true,
                easing: 'linear',
                speed: 300
            }
        },
        stroke: {
            curve: 'smooth',
            width: 2
        },
        fill: {
            type: 'gradient',
            gradient: {
                shadeIntensity: 1,
                opacityFrom: 0.7,
                opacityTo: 0.3
            }
        },
        series: [{
            name: 'Response Time',
            data: Array(10).fill(0)
        }],
        yaxis: {
            min: 0
        },
        colors: ['#696cff']
    });
    responseChart.render();

    // Queries Chart
    queriesChart = new ApexCharts(document.querySelector("#queries-chart"), {
        chart: {
            type: 'bar',
            height: 200,
            toolbar: {
                show: false
            }
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '55%',
                endingShape: 'rounded'
            },
        },
        series: [{
            name: 'Queries/s',
            data: Array(12).fill(0)
        }],
        xaxis: {
            categories: Array.from({length: 12}, (_, i) => `${i}:00`)
        },
        colors: ['#696cff'],
        fill: {
            opacity: 1
        }
    });
    queriesChart.render();

    // Cache Hit Ratio Chart
    cacheChart = new ApexCharts(document.querySelector("#cache-chart"), {
        chart: {
            type: 'donut',
            height: 200
        },
        series: [0, 0],
        labels: ['Hits', 'Misses'],
        colors: ['#696cff', '#8592a3'],
        legend: {
            show: true,
            position: 'bottom'
        }
    });
    cacheChart.render();

    // Server Load Chart
    loadChart = new ApexCharts(document.querySelector("#load-chart"), {
        chart: {
            type: 'line',
            height: 200,
            toolbar: {
                show: false
            },
            zoom: {
                enabled: false
            }
        },
        stroke: {
            curve: 'smooth',
            width: 2
        },
        series: [{
            name: 'Load Average',
            data: Array(12).fill(0)
        }],
        xaxis: {
            categories: Array.from({length: 12}, (_, i) => `${i}:00`)
        },
        colors: ['#696cff']
    });
    loadChart.render();
};

// Update all metrics
const updateMetrics = async () => {
    try {
        const response = await fetch('/dashboard/performance/metrics/data', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        
        if (data.error) {
            console.error('Server error:', data.error);
            throw new Error(data.error);
        }

        // Update charts with the new data
        if (responseChart) {
            responseChart.updateSeries([{
                name: 'Response Time',
                data: data.responseTime
            }]);
        }

        if (queriesChart) {
            queriesChart.updateSeries([{
                name: 'Requests/min',
                data: data.requestRate
            }]);
        }

        if (cacheChart) {
            cacheChart.updateSeries([{
                name: 'Memory Usage',
                data: data.memory
            }]);
        }

        if (loadChart) {
            loadChart.updateSeries([{
                name: 'CPU Load',
                data: data.cpu
            }]);
        }

        // Update statistics
        updateStatistics(data.stats);

    } catch (error) {
        console.error('Error updating metrics:', error);
        // Show error toast
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        Toast.fire({
            icon: 'error',
            title: 'خطأ في تحديث البيانات'
        });
    }
};

// Update statistics display
const updateStatistics = (stats) => {
    if (!stats) return;
    
    // Update CPU stats
    if (stats.cpu) {
        document.querySelector('#cpu-current').textContent = `${stats.cpu.current}%`;
        document.querySelector('#cpu-average').textContent = `${stats.cpu.average}%`;
        document.querySelector('#cpu-peak').textContent = `${stats.cpu.peak}%`;
    }

    // Update Memory stats
    if (stats.memory) {
        document.querySelector('#memory-current').textContent = formatBytes(stats.memory.current);
        document.querySelector('#memory-average').textContent = formatBytes(stats.memory.average);
        document.querySelector('#memory-peak').textContent = formatBytes(stats.memory.peak);
    }

    // Update Response Time stats
    if (stats.responseTime) {
        document.querySelector('#response-current').textContent = `${stats.responseTime.current}ms`;
        document.querySelector('#response-average').textContent = `${stats.responseTime.average}ms`;
        document.querySelector('#response-peak').textContent = `${stats.responseTime.peak}ms`;
    }

    // Update Request Rate stats
    if (stats.requestRate) {
        document.querySelector('#requests-current').textContent = `${stats.requestRate.current}/min`;
        document.querySelector('#requests-average').textContent = `${stats.requestRate.average}/min`;
        document.querySelector('#requests-peak').textContent = `${stats.requestRate.peak}/min`;
    }
};

// Format bytes to human readable format
const formatBytes = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

// Update CPU metrics
const updateCPUMetrics = (data) => {
    const usage = data.cpu_usage || 0;
    document.getElementById('cpu-usage').textContent = `${usage}%`;
    document.getElementById('cpu-progress').style.width = `${usage}%`;
    
    // Update color based on usage
    const progressBar = document.getElementById('cpu-progress');
    if (usage > 90) {
        progressBar.className = 'progress-bar bg-danger';
    } else if (usage > 70) {
        progressBar.className = 'progress-bar bg-warning';
    } else {
        progressBar.className = 'progress-bar bg-primary';
    }
};

// Update Memory metrics
const updateMemoryMetrics = (data) => {
    const usage = data.usage_percentage || 0;
    document.getElementById('memory-usage').textContent = `${usage}%`;
    document.getElementById('memory-progress').style.width = `${usage}%`;
    document.getElementById('memory-details').textContent = 
        `${data.current.formatted} / ${data.limit.formatted}`;
};

// Update Disk metrics
const updateDiskMetrics = (data) => {
    const usage = data.usage_percentage || 0;
    document.getElementById('disk-usage').textContent = `${usage}%`;
    document.getElementById('disk-progress').style.width = `${usage}%`;
    document.getElementById('disk-details').textContent = 
        `${data.used.formatted} / ${data.total.formatted}`;
};

// Update Response Time metrics
const updateResponseMetrics = (data) => {
    document.getElementById('response-time').textContent = `${data.average}ms`;
    document.getElementById('response-details').textContent = 
        `Peak: ${data.peak}ms | Min: ${data.minimum}ms`;
    
    // Update chart
    const newData = responseChart.w.config.series[0].data;
    newData.push(data.average);
    if (newData.length > 10) newData.shift();
    
    responseChart.updateSeries([{
        data: newData
    }]);
};

// Update Database metrics
const updateDatabaseMetrics = (data) => {
    document.getElementById('db-connections').textContent = data.active_connections;
    document.getElementById('db-queries').textContent = data.queries_per_second;
    document.getElementById('db-size').textContent = data.database_size.formatted;
    document.getElementById('db-uptime').textContent = data.uptime.formatted;
};

// Update Cache metrics
const updateCacheMetrics = (data) => {
    document.getElementById('cache-hit-ratio').textContent = `${data.hit_ratio}%`;
    document.getElementById('cache-size').textContent = data.size;
    
    cacheChart.updateSeries([
        data.hit_ratio,
        100 - data.hit_ratio
    ]);
};

// Update Load metrics
const updateLoadMetrics = (data) => {
    document.getElementById('load-1').textContent = data.load_1.toFixed(2);
    document.getElementById('load-5').textContent = data.load_5.toFixed(2);
    document.getElementById('load-15').textContent = data.load_15.toFixed(2);
    
    // Update chart
    const newData = loadChart.w.config.series[0].data;
    newData.push(data.load_1);
    if (newData.length > 12) newData.shift();
    
    loadChart.updateSeries([{
        data: newData
    }]);
};
