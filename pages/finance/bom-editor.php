<?php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../classes/MasterData.php';

requireLogin();
$canView = hasPermission('admin') || hasPermission('planner');

if (!$canView) {
    header('Location: /pages/dashboard.php');
    exit;
}

$masterData = new MasterData($db);
$products = $masterData->getProducts();
?>

<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2>BOM Management</h2>
            <p class="text-muted">Bill of Materials editor with cost roll-up</p>
        </div>
        <div class="col-auto">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createBOMModal">
                <i class="fas fa-plus"></i> New BOM
            </button>
        </div>
    </div>

    <!-- Product Selection -->
    <div class="card mb-4">
        <div class="card-body">
            <label class="form-label">Select Product:</label>
            <select id="productSelect" class="form-select">
                <option value="">-- Select Product --</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= $product['id'] ?>" data-sku="<?= htmlspecialchars($product['sku']) ?>">
                        <?= htmlspecialchars($product['name']) ?> (<?= htmlspecialchars($product['sku']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- BOM List -->
    <div id="bomListSection" style="display: none;">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">BOMs for <span id="selectedProductName"></span></h5>
            </div>
            <div class="card-body">
                <div id="bomList"></div>
            </div>
        </div>
    </div>

    <!-- BOM Details -->
    <div id="bomDetailsSection" style="display: none;">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">BOM Details - Version <span id="bomVersion"></span></h5>
                <div>
                    <span class="badge bg-secondary" id="bomStatusBadge"></span>
                    <button class="btn btn-sm btn-success ms-2" id="activateBOMBtn">Activate</button>
                    <button class="btn btn-sm btn-secondary" id="backToBOMListBtn">Back to List</button>
                </div>
            </div>
            <div class="card-body">
                <!-- Add Item Form -->
                <div class="mb-4">
                    <h6>Add Material</h6>
                    <form id="addItemForm" class="row g-3">
                        <input type="hidden" id="currentBOMId">
                        <div class="col-md-4">
                            <select id="materialSelect" class="form-select" required>
                                <option value="">-- Select Material --</option>
                                <?php foreach ($products as $product): ?>
                                    <option value="<?= $product['id'] ?>">
                                        <?= htmlspecialchars($product['name']) ?> (<?= htmlspecialchars($product['sku']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="number" id="itemQuantity" class="form-control" placeholder="Quantity" step="0.01" required>
                        </div>
                        <div class="col-md-3">
                            <input type="number" id="itemUnitCost" class="form-control" placeholder="Unit Cost (optional)" step="0.01">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Add</button>
                        </div>
                    </form>
                </div>

                <!-- BOM Items Table -->
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Seq</th>
                            <th>Material</th>
                            <th>SKU</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Unit Cost</th>
                            <th>Total Cost</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="bomItemsTable">
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6" class="text-end"><strong>Total BOM Cost:</strong></td>
                            <td colspan="2"><strong id="totalBOMCost">$0.00</strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create BOM Modal -->
<div class="modal fade" id="createBOMModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New BOM</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="createBOMForm">
                    <div class="mb-3">
                        <label class="form-label">Product</label>
                        <select id="createProductSelect" class="form-select" required>
                            <option value="">-- Select Product --</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['id'] ?>">
                                    <?= htmlspecialchars($product['name']) ?> (<?= htmlspecialchars($product['sku']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Version</label>
                        <input type="text" id="bomVersionInput" class="form-control" value="1.0" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveBOMBtn">Create BOM</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentProductId = null;
let currentBOMId = null;
let currentBOMData = null;

// Product selection
document.getElementById('productSelect').addEventListener('change', function() {
    currentProductId = this.value;
    if (currentProductId) {
        loadBOMsForProduct(currentProductId);
        document.getElementById('selectedProductName').textContent = this.options[this.selectedIndex].text;
        document.getElementById('bomListSection').style.display = 'block';
        document.getElementById('bomDetailsSection').style.display = 'none';
    } else {
        document.getElementById('bomListSection').style.display = 'none';
        document.getElementById('bomDetailsSection').style.display = 'none';
    }
});

// Load BOMs for selected product
async function loadBOMsForProduct(productId) {
    try {
        const response = await fetch(`/api/finance/boms.php?action=list&product_id=${productId}`);
        const result = await response.json();
        
        if (result.success) {
            displayBOMList(result.data);
        }
    } catch (error) {
        console.error('Error loading BOMs:', error);
    }
}

function displayBOMList(boms) {
    const container = document.getElementById('bomList');
    
    if (boms.length === 0) {
        container.innerHTML = '<p class="text-muted">No BOMs found for this product.</p>';
        return;
    }
    
    let html = '<div class="list-group">';
    boms.forEach(bom => {
        const statusColor = bom.status === 'active' ? 'success' : bom.status === 'draft' ? 'secondary' : 'warning';
        html += `
            <a href="#" class="list-group-item list-group-item-action" onclick="loadBOMDetails(${bom.id}); return false;">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="mb-1">Version ${bom.version}</h6>
                        <small class="text-muted">Created: ${bom.created_at}</small>
                    </div>
                    <span class="badge bg-${statusColor}">${bom.status}</span>
                </div>
            </a>
        `;
    });
    html += '</div>';
    
    container.innerHTML = html;
}

// Load BOM details
async function loadBOMDetails(bomId) {
    try {
        const response = await fetch(`/api/finance/boms.php?action=detail&id=${bomId}`);
        const result = await response.json();
        
        if (result.success) {
            currentBOMData = result.data;
            currentBOMId = bomId;
            displayBOMDetails(result.data);
        }
    } catch (error) {
        console.error('Error loading BOM details:', error);
    }
}

function displayBOMDetails(bom) {
    document.getElementById('bomListSection').style.display = 'none';
    document.getElementById('bomDetailsSection').style.display = 'block';
    document.getElementById('bomVersion').textContent = bom.version;
    document.getElementById('bomStatusBadge').textContent = bom.status;
    document.getElementById('currentBOMId').value = bom.id;
    
    // Show/hide activate button
    document.getElementById('activateBOMBtn').style.display = bom.status !== 'active' ? 'inline-block' : 'none';
    
    // Display items
    const tbody = document.getElementById('bomItemsTable');
    tbody.innerHTML = '';
    
    let totalCost = 0;
    
    bom.items.forEach(item => {
        const itemTotal = (item.unit_cost || 0) * item.quantity;
        totalCost += itemTotal;
        
        tbody.innerHTML += `
            <tr data-item-id="${item.id}">
                <td>${item.sequence}</td>
                <td>${item.material_name}</td>
                <td>${item.material_sku}</td>
                <td><input type="number" class="form-control form-control-sm" value="${item.quantity}" step="0.01" onchange="updateItem(${item.id}, this.value, ${item.unit_cost || 0})"></td>
                <td>${item.unit}</td>
                <td><input type="number" class="form-control form-control-sm" value="${item.unit_cost || 0}" step="0.01" onchange="updateItem(${item.id}, ${item.quantity}, this.value)"></td>
                <td>$${itemTotal.toFixed(2)}</td>
                <td><button class="btn btn-sm btn-danger" onclick="deleteItem(${item.id})"><i class="fas fa-trash"></i></button></td>
            </tr>
        `;
    });
    
    document.getElementById('totalBOMCost').textContent = `$${totalCost.toFixed(2)}`;
}

// Add BOM item
document.getElementById('addItemForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const data = {
        bom_id: currentBOMId,
        material_id: parseInt(document.getElementById('materialSelect').value),
        quantity: parseFloat(document.getElementById('itemQuantity').value),
        unit_cost: document.getElementById('itemUnitCost').value ? parseFloat(document.getElementById('itemUnitCost').value) : null
    };
    
    try {
        const response = await fetch('/api/finance/boms.php?action=add_item', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        if (result.success) {
            loadBOMDetails(currentBOMId);
            this.reset();
        } else {
            alert('Error: ' + result.error);
        }
    } catch (error) {
        alert('Error adding item');
    }
});

// Update BOM item
async function updateItem(itemId, quantity, unitCost) {
    const data = {
        item_id: itemId,
        quantity: parseFloat(quantity),
        unit_cost: parseFloat(unitCost) || null
    };
    
    try {
        const response = await fetch('/api/finance/boms.php?action=update_item', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        if (result.success) {
            loadBOMDetails(currentBOMId);
        }
    } catch (error) {
        console.error('Error updating item:', error);
    }
}

// Delete BOM item
async function deleteItem(itemId) {
    if (!confirm('Delete this item?')) return;
    
    try {
        const response = await fetch('/api/finance/boms.php?action=delete_item', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({item_id: itemId})
        });
        
        const result = await response.json();
        if (result.success) {
            loadBOMDetails(currentBOMId);
        }
    } catch (error) {
        alert('Error deleting item');
    }
}

// Activate BOM
document.getElementById('activateBOMBtn').addEventListener('click', async function() {
    if (!confirm('Activate this BOM? This will set it as the active version for production.')) return;
    
    try {
        const response = await fetch('/api/finance/boms.php?action=update_status', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({bom_id: currentBOMId, status: 'active'})
        });
        
        const result = await response.json();
        if (result.success) {
            loadBOMDetails(currentBOMId);
        }
    } catch (error) {
        alert('Error activating BOM');
    }
});

// Back to list
document.getElementById('backToBOMListBtn').addEventListener('click', function() {
    document.getElementById('bomDetailsSection').style.display = 'none';
    document.getElementById('bomListSection').style.display = 'block';
    loadBOMsForProduct(currentProductId);
});

// Create BOM
document.getElementById('saveBOMBtn').addEventListener('click', async function() {
    const productId = document.getElementById('createProductSelect').value;
    const version = document.getElementById('bomVersionInput').value;
    
    if (!productId || !version) {
        alert('Please fill all fields');
        return;
    }
    
    try {
        const response = await fetch('/api/finance/boms.php?action=create', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({product_id: parseInt(productId), version: version})
        });
        
        const result = await response.json();
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('createBOMModal')).hide();
            document.getElementById('createBOMForm').reset();
            
            // Select the product and load its BOMs
            document.getElementById('productSelect').value = productId;
            currentProductId = productId;
            loadBOMsForProduct(productId);
            document.getElementById('bomListSection').style.display = 'block';
        } else {
            alert('Error: ' + result.error);
        }
    } catch (error) {
        alert('Error creating BOM');
    }
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
