<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../classes/BOMManager.php';

requireLogin();
$canView = hasPermission('admin') || hasPermission('manager');

if (!$canView) {
    header('Location: /pages/dashboard.php');
    exit;
}

$bomManager = new BOMManager($db);
?>

<div class="container mt-4">
    <h2>Job Costing & Financial Reports</h2>
    <p class="text-muted">Cost analysis, financial summaries, and accounting exports</p>

    <!-- Date Range Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" id="startDate" class="form-control" value="<?= date('Y-m-01') ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" id="endDate" class="form-control" value="<?= date('Y-m-t') ?>">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Load Summary</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Financial Summary -->
    <div id="summarySection" class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Jobs</h6>
                    <h3 id="totalJobs">-</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Revenue</h6>
                    <h3 id="totalRevenue">$0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Costs</h6>
                    <h3 id="totalCosts">$0</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Profit</h6>
                    <h3 id="totalProfit">$0</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Cost Breakdown Chart -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Cost Breakdown</h5>
        </div>
        <div class="card-body">
            <canvas id="costBreakdownChart" style="max-height: 300px;"></canvas>
        </div>
    </div>

    <!-- Job Costing Lookup -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Individual Job Costing</h5>
        </div>
        <div class="card-body">
            <form id="jobCostingForm" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Job ID</label>
                    <input type="number" id="jobIdInput" class="form-control" placeholder="Enter Job ID" required>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Calculate</button>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-secondary w-100" id="exportJobBtn">Export for Accounting</button>
                </div>
            </form>
            
            <div id="jobCostDetails" class="mt-3" style="display: none;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Cost Type</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody id="jobCostTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Material Requirements Calculator -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Material Requirements Planning (MRP)</h5>
        </div>
        <div class="card-body">
            <form id="mrpForm" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Product ID</label>
                    <input type="number" id="mrpProductId" class="form-control" placeholder="Product ID" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Production Quantity</label>
                    <input type="number" id="mrpQuantity" class="form-control" placeholder="Quantity" step="0.01" required>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Calculate Requirements</button>
                </div>
            </form>
            
            <div id="mrpResults" class="mt-3" style="display: none;">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th>SKU</th>
                            <th>Unit Qty</th>
                            <th>Total Qty</th>
                            <th>Unit</th>
                            <th>Unit Cost</th>
                            <th>Total Cost</th>
                        </tr>
                    </thead>
                    <tbody id="mrpTableBody">
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let costChart = null;

// Load financial summary
document.getElementById('filterForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    try {
        const response = await fetch(`/api/finance/costing.php?action=summary&start_date=${startDate}&end_date=${endDate}`);
        const result = await response.json();
        
        if (result.success) {
            displaySummary(result.data);
        }
    } catch (error) {
        console.error('Error loading summary:', error);
    }
});

function displaySummary(data) {
    document.getElementById('totalJobs').textContent = data.total_jobs;
    document.getElementById('totalRevenue').textContent = '$' + data.total_revenue.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('totalCosts').textContent = '$' + data.total_costs.toLocaleString('en-US', {minimumFractionDigits: 2});
    document.getElementById('totalProfit').textContent = '$' + data.total_profit.toLocaleString('en-US', {minimumFractionDigits: 2});
    
    // Update chart
    const ctx = document.getElementById('costBreakdownChart');
    
    if (costChart) {
        costChart.destroy();
    }
    
    costChart = new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Materials', 'Labor', 'Overhead'],
            datasets: [{
                data: [
                    data.cost_breakdown.materials,
                    data.cost_breakdown.labor,
                    data.cost_breakdown.overhead
                ],
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': $' + context.parsed.toLocaleString('en-US', {minimumFractionDigits: 2});
                        }
                    }
                }
            }
        }
    });
}

// Job costing
document.getElementById('jobCostingForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const jobId = document.getElementById('jobIdInput').value;
    
    try {
        const response = await fetch(`/api/finance/costing.php?action=job&job_id=${jobId}`);
        const result = await response.json();
        
        if (result.success) {
            displayJobCost(result.data);
            document.getElementById('jobCostDetails').style.display = 'block';
        } else {
            alert('Error: ' + result.error);
        }
    } catch (error) {
        alert('Error loading job cost');
    }
});

function displayJobCost(cost) {
    const tbody = document.getElementById('jobCostTableBody');
    tbody.innerHTML = `
        <tr>
            <td>Materials</td>
            <td>$${cost.materials.toFixed(2)}</td>
        </tr>
        <tr>
            <td>Labor</td>
            <td>$${cost.labor.toFixed(2)}</td>
        </tr>
        <tr>
            <td>Overhead</td>
            <td>$${cost.overhead.toFixed(2)}</td>
        </tr>
        <tr class="table-info">
            <td><strong>Total Cost</strong></td>
            <td><strong>$${cost.total.toFixed(2)}</strong></td>
        </tr>
    `;
}

// Export job costing
document.getElementById('exportJobBtn').addEventListener('click', async function() {
    const jobId = document.getElementById('jobIdInput').value;
    if (!jobId) {
        alert('Please enter a Job ID first');
        return;
    }
    
    try {
        const response = await fetch(`/api/finance/costing.php?action=export&job_id=${jobId}`);
        const result = await response.json();
        
        if (result.success) {
            // Download as JSON
            const dataStr = JSON.stringify(result.data, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            const url = URL.createObjectURL(dataBlob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `job_${jobId}_costing_${new Date().toISOString().split('T')[0]}.json`;
            link.click();
            URL.revokeObjectURL(url);
        } else {
            alert('Error: ' + result.error);
        }
    } catch (error) {
        alert('Error exporting job cost');
    }
});

// Material requirements planning
document.getElementById('mrpForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const productId = document.getElementById('mrpProductId').value;
    const quantity = document.getElementById('mrpQuantity').value;
    
    try {
        const response = await fetch(`/api/finance/boms.php?action=requirements&product_id=${productId}&quantity=${quantity}`);
        const result = await response.json();
        
        if (result.success) {
            displayMRPResults(result.data);
            document.getElementById('mrpResults').style.display = 'block';
        } else {
            alert('Error: ' + result.error);
        }
    } catch (error) {
        alert('Error calculating requirements');
    }
});

function displayMRPResults(requirements) {
    const tbody = document.getElementById('mrpTableBody');
    
    if (requirements.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No active BOM found for this product</td></tr>';
        return;
    }
    
    let totalCost = 0;
    tbody.innerHTML = '';
    
    requirements.forEach(req => {
        totalCost += req.total_cost;
        tbody.innerHTML += `
            <tr>
                <td>${req.material_name}</td>
                <td>${req.sku}</td>
                <td>${req.unit_quantity}</td>
                <td>${req.total_quantity}</td>
                <td>${req.unit}</td>
                <td>$${(req.unit_cost || 0).toFixed(2)}</td>
                <td>$${req.total_cost.toFixed(2)}</td>
            </tr>
        `;
    });
    
    tbody.innerHTML += `
        <tr class="table-success">
            <td colspan="6" class="text-end"><strong>Total Material Cost:</strong></td>
            <td><strong>$${totalCost.toFixed(2)}</strong></td>
        </tr>
    `;
}

// Load initial summary
document.getElementById('filterForm').dispatchEvent(new Event('submit'));
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
