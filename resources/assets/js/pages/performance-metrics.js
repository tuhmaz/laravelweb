'use strict';

$(function () {
    let selectedRange = '24h';
    let charts = {};

    // Initialize charts
    function initializeCharts() {
        // CPU Usage Chart
        charts.cpuChart = new ApexCharts(document.querySelector("#cpuUsageChart"), {
            chart: {
                height: 350,
                type: 'area',
                animations: {
                    enabled: true,
                    easing: 'linear',
                    dynamicAnimation: {
                        speed: 1000
                    }
                },
                toolbar: {
                    show: false
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            series: [{
                name: 'CPU Usage',
                data: []
            }],
            xaxis: {
                type: 'datetime'
            },
            yaxis: {
                min: 0,
                max: 100,
                labels: {
                    formatter: function (val) {
                        return val.toFixed(1) + '%';
                    }
                }
            },
            tooltip: {
                x: {
                    format: 'dd MMM yyyy HH:mm:ss'
                }
            },
            colors: ['#696cff']
        });
        charts.cpuChart.render();

        // Memory Usage Chart
        charts.memoryChart = new ApexCharts(document.querySelector("#memoryUsageChart"), {
            chart: {
                height: 350,
                type: 'area',
                animations: {
                    enabled: true,
                    easing: 'linear',
                    dynamicAnimation: {
                        speed: 1000
                    }
                },
                toolbar: {
                    show: false
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            series: [{
                name: 'Memory Usage',
                data: []
            }],
            xaxis: {
                type: 'datetime'
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return formatBytes(val);
                    }
                }
            },
            tooltip: {
                x: {
                    format: 'dd MMM yyyy HH:mm:ss'
                },
                y: {
                    formatter: function (val) {
                        return formatBytes(val);
                    }
                }
            },
            colors: ['#ff6b6b']
        });
        charts.memoryChart.render();

        // Response Time Chart
        charts.responseTimeChart = new ApexCharts(document.querySelector("#responseTimeChart"), {
            chart: {
                height: 350,
                type: 'line',
                animations: {
                    enabled: true,
                    easing: 'linear',
                    dynamicAnimation: {
                        speed: 1000
                    }
                },
                toolbar: {
                    show: false
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            series: [{
                name: 'Response Time',
                data: []
            }],
            xaxis: {
                type: 'datetime'
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return val.toFixed(2) + 'ms';
                    }
                }
            },
            tooltip: {
                x: {
                    format: 'dd MMM yyyy HH:mm:ss'
                },
                y: {
                    formatter: function (val) {
                        return val.toFixed(2) + 'ms';
                    }
                }
            },
            colors: ['#4ecdc4']
        });
        charts.responseTimeChart.render();

        // Request Rate Chart
        charts.requestRateChart = new ApexCharts(document.querySelector("#requestRateChart"), {
            chart: {
                height: 350,
                type: 'bar',
                animations: {
                    enabled: true,
                    easing: 'linear',
                    dynamicAnimation: {
                        speed: 1000
                    }
                },
                toolbar: {
                    show: false
                }
            },
            plotOptions: {
                bar: {
                    columnWidth: '45%',
                    distributed: true
                }
            },
            dataLabels: {
                enabled: false
            },
            series: [{
                name: 'Requests',
                data: []
            }],
            xaxis: {
                type: 'datetime'
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return Math.round(val) + '/min';
                    }
                }
            },
            tooltip: {
                x: {
                    format: 'dd MMM yyyy HH:mm:ss'
                }
            },
            colors: ['#45b649']
        });
        charts.requestRateChart.render();

        // Error Rate Chart
        charts.errorRateChart = new ApexCharts(document.querySelector("#errorRateChart"), {
            chart: {
                height: 350,
                type: 'area',
                animations: {
                    enabled: true,
                    easing: 'linear',
                    dynamicAnimation: {
                        speed: 1000
                    }
                },
                toolbar: {
                    show: false
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            series: [{
                name: 'Error Rate',
                data: []
            }],
            xaxis: {
                type: 'datetime'
            },
            yaxis: {
                min: 0,
                max: 100,
                labels: {
                    formatter: function (val) {
                        return val.toFixed(2) + '%';
                    }
                }
            },
            tooltip: {
                x: {
                    format: 'dd MMM yyyy HH:mm:ss'
                },
                y: {
                    formatter: function (val) {
                        return val.toFixed(2) + '%';
                    }
                }
            },
            colors: ['#ff4444']
        });
        charts.errorRateChart.render();
    }

    // Utility function to format bytes
    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Load metrics data
    function loadMetricsData() {
        $.ajax({
            url: route('dashboard.performance.metrics.data'),
            method: 'GET',
            data: {
                range: selectedRange
            },
            success: function(response) {
                updateCharts(response);
                updateStats(response.stats);
            },
            error: function(xhr) {
                console.error('Error loading metrics:', xhr);
            }
        });
    }

    // Update charts with new data
    function updateCharts(data) {
        if (data.cpu) {
            charts.cpuChart.updateSeries([{
                data: data.cpu
            }]);
        }
        if (data.memory) {
            charts.memoryChart.updateSeries([{
                data: data.memory
            }]);
        }
        if (data.responseTime) {
            charts.responseTimeChart.updateSeries([{
                data: data.responseTime
            }]);
        }
        if (data.requestRate) {
            charts.requestRateChart.updateSeries([{
                data: data.requestRate
            }]);
        }
        if (data.errorRate) {
            charts.errorRateChart.updateSeries([{
                data: data.errorRate
            }]);
        }
    }

    // Update statistics table
    function updateStats(stats) {
        $('#currentCpu').text(stats.cpu.current.toFixed(1) + '%');
        $('#avgCpu').text(stats.cpu.average.toFixed(1) + '%');
        $('#peakCpu').text(stats.cpu.peak.toFixed(1) + '%');

        $('#currentMemory').text(formatBytes(stats.memory.current));
        $('#avgMemory').text(formatBytes(stats.memory.average));
        $('#peakMemory').text(formatBytes(stats.memory.peak));

        $('#currentResponse').text(stats.responseTime.current.toFixed(2) + 'ms');
        $('#avgResponse').text(stats.responseTime.average.toFixed(2) + 'ms');
        $('#peakResponse').text(stats.responseTime.peak.toFixed(2) + 'ms');

        $('#currentRequests').text(Math.round(stats.requestRate.current) + '/min');
        $('#avgRequests').text(Math.round(stats.requestRate.average) + '/min');
        $('#peakRequests').text(Math.round(stats.requestRate.peak) + '/min');
    }

    // Event handlers
    $('.dropdown-item').on('click', function(e) {
        e.preventDefault();
        selectedRange = $(this).data('range');
        $('#timeRangeDropdown').text($(this).text());
        loadMetricsData();
    });

    // Initialize
    initializeCharts();
    loadMetricsData();

    // Refresh data every 30 seconds
    setInterval(loadMetricsData, 30000);
});
