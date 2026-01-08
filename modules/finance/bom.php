<?php
require_once '../../config/config.php';
require_once '../../classes/Auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    header('Location: ../../login.php');
    exit;
}

if (!$auth->hasPermission('finance.view_bom')) {
    header('Location: ../../dashboard.php');
    exit;
}

$pageTitle = 'Bill of Materials (BOM)';
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
?>

<div class="main-content">
    <div class="page-header">
        <h1>Bill of Materials (BOM)</h1>
        <p>Manage product component structures and cost analysis</p>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #3498db;">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Active BOMs</span>
                <span class="stat-value" id="activeBOMCount">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #2ecc71;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Approved BOMs</span>
                <span class="stat-value" id="approvedBOMCount">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #f39c12;">
                <i class="fas fa-sync-alt"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Draft BOMs</span>
                <span class="stat-value" id="draftBOMCount">0</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #9b59b6;">
                <i class="fas fa-dollar-sign"></i>
            </div>
            <div class="stat-info">
                <span class="stat-label">Avg BOM Cost</span>
                <span class="stat-value" id="avgBOMCost">R0.00</span>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="filter-section">
        <div class="filter-group">
            <input type="text" id="searchInput" placeholder="Search BOM name, product, version..." class="search-input">
        </div>
        <div class="filter-group">
            <select id="statusFilter" class="filter-select">
                <option value="">All Status</option>
                <option value="draft">Draft</option>
                <option value="active">Active</option>
                <option value="obsolete">Obsolete</option>
            </select>
        </div>
        <div class="filter-group">
            <select id="productFilter" class="filter-select">
                <option value="">All Products</option>
            </select>
        </div>
        <?php if ($auth->hasPermission('finance.edit_bom')): ?>
        <div class="filter-group">
            <button class="btn btn-primary" onclick="openBOMModal()">
                <i class="fas fa-plus"></i> Create BOM
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- BOM Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>BOM Number</th>
                    <th>Product</th>
                    <th>Version</th>
                    <th>Status</th>
                    <th>Components</th>
                    <th>Total Cost</th>
                    <th>Created Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="bomTableBody">
                <tr>
                    <td colspan="8" class="text-center">Loading BOMs...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Create/Edit BOM Modal -->
<div id="bomModal" class="modal">
    <div class="modal-content modal-large">
        <span class="close" onclick="closeBOMModal()">&times;</span>
        <h2 id="modalTitle">Create Bill of Materials</h2>
        
        <form id="bomForm">
            <input type="hidden" id="bomId" name="bom_id">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="bomNumber">BOM Number *</label>
                    <input type="text" id="bomNumber" name="bom_number" required readonly>
                </div>
                <div class="form-group">
                    <label for="productId">Product *</label>
                    <select id="productId" name="product_id" required>
                        <option value="">Select Product</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="version">Version *</label>
                    <input type="text" id="version" name="version" required placeholder="e.g., 1.0, 2.1">
                </div>
                <div class="form-group">
                    <label for="status">Status *</label>
                    <select id="status" name="status" required>
                        <option value="draft">Draft</option>
                        <option value="active">Active</option>
                        <option value="obsolete">Obsolete</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="2" placeholder="Optional BOM description"></textarea>
            </div>

            <div class="form-section">
                <div class="section-header">
                    <h3>BOM Components</h3>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="addComponent()">
                        <i class="fas fa-plus"></i> Add Component
                    </button>
                </div>
                
                <div id="componentsContainer">
                    <!-- Components will be added dynamically -->
                </div>

                <div class="cost-summary">
                    <div class="cost-row">
                        <span class="cost-label">Material Cost:</span>
                        <span class="cost-value" id="materialCost">R0.00</span>
                    </div>
                    <div class="cost-row">
                        <span class="cost-label">Labor Cost:</span>
                        <span class="cost-value" id="laborCost">R0.00</span>
                    </div>
                    <div class="cost-row">
                        <span class="cost-label">Overhead (%):</span>
                        <input type="number" id="overheadPercentage" name="overhead_percentage" value="0" min="0" max="100" step="0.1" style="width: 80px; text-align: right;" onchange="calculateTotalCost()">
                    </div>
                    <div class="cost-row">
                        <span class="cost-label">Overhead Amount:</span>
                        <span class="cost-value" id="overheadCost">R0.00</span>
                    </div>
                    <div class="cost-row total-cost">
                        <span class="cost-label">Total BOM Cost:</span>
                        <span class="cost-value" id="totalCost">R0.00</span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="3" placeholder="Additional notes or change log"></textarea>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeBOMModal()">Cancel</button>
                <?php if ($auth->hasPermission('finance.edit_bom')): ?>
                <button type="submit" class="btn btn-primary">Save BOM</button>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- View BOM Details Modal -->
<div id="viewBOMModal" class="modal">
    <div class="modal-content modal-large">
        <span class="close" onclick="closeViewBOMModal()">&times;</span>
        <h2>BOM Details</h2>
        <div id="bomDetailsContent">
            <!-- Content loaded dynamically -->
        </div>
    </div>
</div>

<style>
.form-section {
    margin-top: 24px;
    padding: 16px;
    background-color: #f8f9fa;
    border-radius: 4px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.section-header h3 {
    margin: 0;
    font-size: 18px;
    color: #2c3e50;
}

.component-item {
    background-color: white;
    padding: 12px;
    margin-bottom: 12px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}

.component-row {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1fr 1fr 60px;
    gap: 12px;
    align-items: end;
}

.component-field {
    display: flex;
    flex-direction: column;
}

.component-field label {
    font-size: 12px;
    margin-bottom: 4px;
    color: #666;
}

.component-field input,
.component-field select {
    padding: 8px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 14px;
}

.component-remove {
    display: flex;
    align-items: flex-end;
}

.btn-remove {
    padding: 8px 12px;
    background-color: #e74c3c;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    height: 36px;
}

.btn-remove:hover {
    background-color: #c0392b;
}

.cost-summary {
    margin-top: 16px;
    padding: 16px;
    background-color: white;
    border-radius: 4px;
    border: 2px solid #3498db;
}

.cost-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 14px;
}

.cost-row.total-cost {
    border-top: 2px solid #2c3e50;
    margin-top: 8px;
    padding-top: 12px;
    font-weight: bold;
    font-size: 16px;
}

.cost-label {
    color: #555;
}

.cost-value {
    color: #2c3e50;
    font-weight: 500;
}

.bom-details {
    padding: 16px;
}

.detail-section {
    margin-bottom: 24px;
}

.detail-section h3 {
    font-size: 16px;
    color: #2c3e50;
    margin-bottom: 12px;
    border-bottom: 2px solid #3498db;
    padding-bottom: 8px;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.detail-item {
    display: flex;
    flex-direction: column;
}

.detail-label {
    font-size: 12px;
    color: #666;
    margin-bottom: 4px;
}

.detail-value {
    font-size: 14px;
    color: #2c3e50;
    font-weight: 500;
}

.components-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 12px;
}

.components-table th,
.components-table td {
    padding: 8px;
    text-align: left;
    border-bottom: 1px solid #dee2e6;
}

.components-table th {
    background-color: #f8f9fa;
    font-weight: 600;
    font-size: 12px;
    color: #555;
}

.components-table td {
    font-size: 14px;
}
</style>

<script src="../../assets/js/bom.js"></script>

<?php require_once '../../includes/footer.php'; ?>
