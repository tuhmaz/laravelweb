/**
 * Monitoring Dashboard
 */

'use strict';

// Initialize charts and maps
document.addEventListener('DOMContentLoaded', function () {
    let visitorChart;
    let visitorMap;
    let markers = [];

    // Initialize visitor chart
    const initVisitorChart = () => {
        const options = {
            series: [{
                name: 'Visitors',
                data: []
            }],
            chart: {
                height: 300,
                type: 'area',
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
            grid: {
                strokeDashArray: 5
            },
            xaxis: {
                type: 'datetime',
                labels: {
                    formatter: function (value) {
                        return new Date(value).toLocaleTimeString();
                    }
                }
            },
            yaxis: {
                labels: {
                    formatter: function (value) {
                        return Math.round(value);
                    }
                }
            },
            tooltip: {
                x: {
                    format: 'HH:mm'
                }
            },
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.2,
                    stops: [0, 90, 100]
                }
            }
        };

        visitorChart = new ApexCharts(document.querySelector("#visitorChart"), options);
        visitorChart.render();
    };

    // Initialize visitor map
    const initVisitorMap = () => {
        visitorMap = L.map('visitorMap').setView([0, 0], 2);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: ' OpenStreetMap contributors'
        }).addTo(visitorMap);
    };

    // Update stats
    const updateStats = (data) => {
        // Update visitor count
        document.getElementById('visitors-count').textContent = data.visitors.current;
        document.getElementById('visitors-change').textContent = 
            `${data.visitors.change >= 0 ? '+' : ''}${data.visitors.change}% from last hour`;

        // Update CPU usage
        const cpuUsage = data.system.cpu.usage;
        document.getElementById('cpu-usage').textContent = `${cpuUsage}%`;
        document.getElementById('cpu-progress').style.width = `${cpuUsage}%`;

        // Update memory usage
        const memoryUsage = data.system.memory.percentage;
        document.getElementById('memory-usage').textContent = `${memoryUsage}%`;
        document.getElementById('memory-progress').style.width = `${memoryUsage}%`;

        // Update error count
        document.getElementById('error-count').textContent = data.errors.count;
        document.getElementById('error-change').textContent = 
            `${data.errors.trend >= 0 ? '+' : ''}${data.errors.trend}% from yesterday`;

        // Update visitor chart
        if (visitorChart && data.visitors.history) {
            visitorChart.updateSeries([{
                data: data.visitors.history.map(item => ({
                    x: new Date(item.timestamp).getTime(),
                    y: item.count
                }))
            }]);
        }

        // Update visitor map
        if (visitorMap && data.locations) {
            // Clear existing markers
            markers.forEach(marker => visitorMap.removeLayer(marker));
            markers = [];

            // Add new markers
            data.locations.forEach(location => {
                const marker = L.marker([location.lat, location.lng])
                    .bindPopup(`${location.country}: ${location.count} visitors`)
                    .addTo(visitorMap);
                markers.push(marker);
            });
        }

        // Update error table
        const errorTable = document.getElementById('errorTable').getElementsByTagName('tbody')[0];
        errorTable.innerHTML = '';
        
        if (data.errors.recent && data.errors.recent.length > 0) {
            data.errors.recent.forEach(error => {
                const row = errorTable.insertRow();
                row.innerHTML = `
                    <td>${new Date(error.timestamp).toLocaleString()}</td>
                    <td><span class="badge bg-label-danger">${error.type}</span></td>
                    <td>${error.message}</td>
                    <td>
                        <button type="button" class="btn btn-sm btn-icon btn-text-secondary" 
                                onclick="showErrorDetails('${error.id}')" 
                                title="${__('View Details')}">
                            <i class="ti ti-eye"></i>
                        </button>
                    </td>
                `;
            });
        } else {
            const row = errorTable.insertRow();
            row.innerHTML = `
                <td colspan="4" class="text-center">${__('No recent errors')}</td>
            `;
        }

        // Update system information
        document.getElementById('server-info').textContent = `${data.system.os} | Uptime: ${data.system.uptime}`;
        document.getElementById('database-info').textContent = `${data.system.database.type} ${data.system.database.version}`;
        document.getElementById('php-info').textContent = `PHP ${data.system.php.version}`;
    };

    // متغير عام لتخزين معرف الخطأ الحالي
    let currentErrorId = null;

    // عرض تفاصيل الخطأ
    function showErrorDetails(errorId) {
        currentErrorId = errorId;
        
        // تحديث بيانات النافذة المنبثقة
        document.getElementById('errorType').textContent = errorId.type;
        document.getElementById('errorTime').textContent = errorId.timestamp;
        document.getElementById('errorMessage').textContent = errorId.message;
        document.getElementById('errorFile').textContent = errorId.file;
        document.getElementById('errorLine').textContent = errorId.line;
        
        // عرض النافذة المنبثقة
        const modal = new bootstrap.Modal(document.getElementById('errorDetailsModal'));
        modal.show();
    }

    // حذف خطأ
    async function deleteError(errorId) {
        try {
            const response = await fetch(`/api/errors/${errorId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            if (response.ok) {
                // إغلاق النافذة المنبثقة
                bootstrap.Modal.getInstance(document.getElementById('errorDetailsModal')).hide();
                
                // تحديث قائمة الأخطاء
                updateErrorsList();
                
                // عرض رسالة نجاح
                Swal.fire({
                    icon: 'success',
                    title: 'تم الحذف بنجاح',
                    text: 'تم حذف الخطأ بنجاح',
                    confirmButtonText: 'حسناً',
                    customClass: {
                        confirmButton: 'btn btn-success'
                    }
                });
            } else {
                throw new Error('فشل حذف الخطأ');
            }
        } catch (error) {
            Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: 'حدث خطأ أثناء محاولة حذف الخطأ',
                confirmButtonText: 'حسناً',
                customClass: {
                    confirmButton: 'btn btn-danger'
                }
            });
        }
    }

    // Initialize components
    initVisitorChart();
    initVisitorMap();

    // Fetch initial data
    fetch('/dashboard/monitoring/stats')
        .then(response => response.json())
        .then(data => updateStats(data))
        .catch(error => console.error('Error fetching stats:', error));

    // Set up periodic updates
    setInterval(() => {
        fetch('/dashboard/monitoring/stats')
            .then(response => response.json())
            .then(data => updateStats(data))
            .catch(error => console.error('Error fetching stats:', error));
    }, 60000); // Update every minute
});
