/**
 * Barron Production Management System
 * Defect Trend Chart Component
 * 
 * Reusable Chart.js component for displaying defect trends
 * Can be embedded in multiple pages with different configurations
 */

class DefectTrendChart {
    constructor(canvasId, options = {}) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) {
            console.error(`Canvas element with id '${canvasId}' not found`);
            return;
        }
        
        this.chart = null;
        this.options = {
            period: options.period || 'week', // 'day' or 'week'
            showCritical: options.showCritical !== false,
            showResolved: options.showResolved !== false,
            height: options.height || 300,
            dateFrom: options.dateFrom || null,
            dateTo: options.dateTo || null,
            ...options
        };
        
        this.initialize();
    }
    
    /**
     * Initialize chart
     */
    async initialize() {
        await this.loadData();
        this.render();
    }
    
    /**
     * Load trend data from API
     */
    async loadData() {
        try {
            let url = `/api/defects/defects.php?action=trend&period=${this.options.period}`;
            
            if (this.options.dateFrom && this.options.dateTo) {
                url += `&date_from=${this.options.dateFrom}&date_to=${this.options.dateTo}`;
            }
            
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success) {
                this.data = data.trend;
            } else {
                console.error('Failed to load trend data:', data.message);
                this.data = [];
            }
        } catch (error) {
            console.error('Error loading trend data:', error);
            this.data = [];
        }
    }
    
    /**
     * Render chart
     */
    render() {
        if (!this.data || this.data.length === 0) {
            this.canvas.parentElement.innerHTML = '<p class="text-center text-muted">No trend data available</p>';
            return;
        }
        
        // Destroy existing chart
        if (this.chart) {
            this.chart.destroy();
        }
        
        // Prepare datasets
        const datasets = [
            {
                label: 'Total Defects',
                data: this.data.map(d => d.total_defects),
                borderColor: 'rgb(13, 110, 253)',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6
            }
        ];
        
        if (this.options.showCritical) {
            datasets.push({
                label: 'Critical Defects',
                data: this.data.map(d => d.critical_defects),
                borderColor: 'rgb(220, 53, 69)',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6
            });
        }
        
        if (this.options.showResolved) {
            datasets.push({
                label: 'Resolved Defects',
                data: this.data.map(d => d.resolved_defects || 0),
                borderColor: 'rgb(25, 135, 84)',
                backgroundColor: 'rgba(25, 135, 84, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6
            });
        }
        
        // Create chart
        this.chart = new Chart(this.canvas, {
            type: 'line',
            data: {
                labels: this.data.map(d => d.date_label),
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            usePointStyle: true,
                            padding: 15
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            title: (context) => {
                                return context[0].label;
                            },
                            label: (context) => {
                                const label = context.dataset.label || '';
                                const value = context.parsed.y;
                                return `${label}: ${value}`;
                            },
                            footer: (context) => {
                                // Calculate total for this data point
                                const total = context.reduce((sum, item) => sum + item.parsed.y, 0);
                                return `Total: ${total}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxRotation: 45,
                            minRotation: 0
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                return Number.isInteger(value) ? value : null;
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    }
                }
            }
        });
        
        // Set canvas height
        this.canvas.parentElement.style.height = `${this.options.height}px`;
    }
    
    /**
     * Update chart with new period
     */
    async updatePeriod(period) {
        this.options.period = period;
        await this.loadData();
        this.render();
    }
    
    /**
     * Update chart with new date range
     */
    async updateDateRange(dateFrom, dateTo) {
        this.options.dateFrom = dateFrom;
        this.options.dateTo = dateTo;
        await this.loadData();
        this.render();
    }
    
    /**
     * Toggle dataset visibility
     */
    toggleDataset(label) {
        if (!this.chart) return;
        
        const dataset = this.chart.data.datasets.find(ds => ds.label === label);
        if (dataset) {
            dataset.hidden = !dataset.hidden;
            this.chart.update();
        }
    }
    
    /**
     * Export chart as image
     */
    exportAsImage(filename = 'defect-trend.png') {
        if (!this.chart) return;
        
        const url = this.canvas.toDataURL('image/png');
        const link = document.createElement('a');
        link.download = filename;
        link.href = url;
        link.click();
    }
    
    /**
     * Destroy chart instance
     */
    destroy() {
        if (this.chart) {
            this.chart.destroy();
            this.chart = null;
        }
    }
}

/**
 * Helper function to create chart with default options
 */
function createDefectTrendChart(canvasId, options = {}) {
    return new DefectTrendChart(canvasId, options);
}

/**
 * Export for use in other modules
 */
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { DefectTrendChart, createDefectTrendChart };
}

/**
 * Example usage:
 * 
 * // Basic usage
 * const chart = new DefectTrendChart('myCanvas');
 * 
 * // With options
 * const chart = new DefectTrendChart('myCanvas', {
 *     period: 'day',
 *     showCritical: true,
 *     showResolved: true,
 *     height: 400
 * });
 * 
 * // Update period
 * chart.updatePeriod('week');
 * 
 * // Update date range
 * chart.updateDateRange('2024-01-01', '2024-01-31');
 * 
 * // Toggle dataset
 * chart.toggleDataset('Critical Defects');
 * 
 * // Export as image
 * chart.exportAsImage('my-trend-chart.png');
 * 
 * // Destroy chart
 * chart.destroy();
 */
