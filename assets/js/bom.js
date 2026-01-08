// BOM Management JavaScript
let bomData = [];
let productsData = [];
let componentCounter = 0;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    loadStats();
    loadProducts();
    loadBOMs();
    
    // Search functionality
    document.getElementById('searchInput').addEventListener('input', debounce(loadBOMs, 300));
    
    // Filter functionality
    document.getElementById('statusFilter').addEventListener('change', loadBOMs);
    document.getElementById('productFilter').addEventListener('change', loadBOMs);
    
    // Form submission
    document.getElementById('bomForm').addEventListener('submit', handleBOMSubmit);
});

// Load statistics
function loadStats() {
    fetch('../../api/finance/bom/stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('activeBOMCount').textContent = data.data.active_count || 0;
                document.getElementById('approvedBOMCount').textContent = data.data.approved_count || 0;
                document.getElementById('draftBOMCount').textContent = data.data.draft_count || 0;
                document.getElementById('avgBOMCost').textContent = 'R' + parseFloat(data.data.avg_cost || 0).toFixed(2);
            }
        })
        .catch(error => console.error('Error loading stats:', error));
}

// Load products for dropdowns
function loadProducts() {
    fetch('../../api/master/products/list.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                productsData = data.data;
                
                // Populate product filter
                const productFilter = document.getElementById('productFilter');
                productFilter.innerHTML = '<option value="">All Products</option>';
                data.data.forEach(product => {
                    productFilter.innerHTML += `<option value="${product.id}">${product.product_name}</option>`;
                });
                
                // Populate product select in form
                const productSelect = document.getElementById('productId');
                productSelect.innerHTML = '<option value="">Select Product</option>';
                data.data.forEach(product => {
                    productSelect.innerHTML += `<option value="${product.id}">${product.product_name}</option>`;
                });
            }
        })
        .catch(error => console.error('Error loading products:', error));
}

// Load BOMs
function loadBOMs() {
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const productId = document.getElementById('productFilter').value;
    
    let url = '../../api/finance/bom/list.php?';
    if (search) url += `search=${encodeURIComponent(search)}&`;
    if (status) url += `status=${status}&`;
    if (productId) url += `product_id=${productId}&`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                bomData = data.data;
                displayBOMs(data.data);
            }
        })
        .catch(error => {
            console.error('Error loading BOMs:', error);
            document.getElementById('bomTableBody').innerHTML = '<tr><td colspan="8" class="text-center">Error loading BOMs</td></tr>';
        });
}

// Display BOMs in table
function displayBOMs(boms) {
    const tbody = document.getElementById('bomTableBody');
    
    if (boms.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No BOMs found</td></tr>';
        return;
    }
    
    tbody.innerHTML = boms.map(bom => `
        <tr>
            <td><strong>${bom.bom_number}</strong></td>
            <td>${bom.product_name}</td>
            <td><span class="badge badge-info">${bom.version}</span></td>
            <td>${getStatusBadge(bom.status)}</td>
            <td>${bom.component_count || 0} items</td>
            <td><strong>R${parseFloat(bom.total_cost || 0).toFixed(2)}</strong></td>
            <td>${formatDate(bom.created_at)}</td>
            <td>
                <button class="btn-icon" onclick="viewBOM(${bom.id})" title="View Details">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn-icon" onclick="editBOM(${bom.id})" title="Edit">
                    <i class="fas fa-edit"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

// Get status badge
function getStatusBadge(status) {
    const badges = {
        'draft': '<span class="badge badge-warning">Draft</span>',
        'active': '<span class="badge badge-success">Active</span>',
        'obsolete': '<span class="badge badge-secondary">Obsolete</span>'
    };
    return badges[status] || status;
}

// Open BOM modal (create new)
function openBOMModal() {
    document.getElementById('modalTitle').textContent = 'Create Bill of Materials';
    document.getElementById('bomForm').reset();
    document.getElementById('bomId').value = '';
    document.getElementById('bomNumber').value = generateBOMNumber();
    document.getElementById('componentsContainer').innerHTML = '';
    componentCounter = 0;
    
    // Add initial component
    addComponent();
    
    calculateTotalCost();
    document.getElementById('bomModal').style.display = 'block';
}

// Close BOM modal
function closeBOMModal() {
    document.getElementById('bomModal').style.display = 'none';
}

// Generate BOM number
function generateBOMNumber() {
    const date = new Date();
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const random = String(Math.floor(Math.random() * 10000)).padStart(4, '0');
    return `BOM${year}${month}${random}`;
}

// Add component to BOM
function addComponent() {
    componentCounter++;
    const container = document.getElementById('componentsContainer');
    
    const componentHtml = `
        <div class="component-item" id="component${componentCounter}">
            <div class="component-row">
                <div class="component-field">
                    <label>Component Name/Part Number *</label>
                    <input type="text" class="component-name" name="components[${componentCounter}][name]" required placeholder="Enter component name">
                </div>
                <div class="component-field">
                    <label>Quantity *</label>
                    <input type="number" class="component-quantity" name="components[${componentCounter}][quantity]" required min="0" step="0.01" value="1" onchange="calculateComponentCost(${componentCounter})">
                </div>
                <div class="component-field">
                    <label>Unit</label>
                    <select class="component-unit" name="components[${componentCounter}][unit]">
                        <option value="pcs">Pieces</option>
                        <option value="kg">Kilograms</option>
                        <option value="m">Meters</option>
                        <option value="l">Liters</option>
                        <option value="box">Boxes</option>
                        <option value="set">Sets</option>
                    </select>
                </div>
                <div class="component-field">
                    <label>Unit Cost (R) *</label>
                    <input type="number" class="component-unitcost" name="components[${componentCounter}][unit_cost]" required min="0" step="0.01" value="0" onchange="calculateComponentCost(${componentCounter})">
                </div>
                <div class="component-field">
                    <label>Total Cost</label>
                    <input type="text" class="component-totalcost" id="componentTotal${componentCounter}" readonly value="R0.00">
                </div>
                <div class="component-remove">
                    <button type="button" class="btn-remove" onclick="removeComponent(${componentCounter})" title="Remove Component">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', componentHtml);
}

// Remove component
function removeComponent(id) {
    const component = document.getElementById(`component${id}`);
    if (component) {
        component.remove();
        calculateTotalCost();
    }
}

// Calculate component cost
function calculateComponentCost(id) {
    const component = document.getElementById(`component${id}`);
    if (!component) return;
    
    const quantity = parseFloat(component.querySelector('.component-quantity').value) || 0;
    const unitCost = parseFloat(component.querySelector('.component-unitcost').value) || 0;
    const totalCost = quantity * unitCost;
    
    component.querySelector('.component-totalcost').value = 'R' + totalCost.toFixed(2);
    calculateTotalCost();
}

// Calculate total BOM cost
function calculateTotalCost() {
    let materialCost = 0;
    
    // Sum all component costs
    document.querySelectorAll('.component-item').forEach(component => {
        const quantity = parseFloat(component.querySelector('.component-quantity').value) || 0;
        const unitCost = parseFloat(component.querySelector('.component-unitcost').value) || 0;
        materialCost += quantity * unitCost;
    });
    
    const laborCost = 0; // Can be extended to add labor costs
    const overheadPercentage = parseFloat(document.getElementById('overheadPercentage').value) || 0;
    const overheadCost = (materialCost + laborCost) * (overheadPercentage / 100);
    const totalCost = materialCost + laborCost + overheadCost;
    
    document.getElementById('materialCost').textContent = 'R' + materialCost.toFixed(2);
    document.getElementById('laborCost').textContent = 'R' + laborCost.toFixed(2);
    document.getElementById('overheadCost').textContent = 'R' + overheadCost.toFixed(2);
    document.getElementById('totalCost').textContent = 'R' + totalCost.toFixed(2);
}

// Handle BOM form submission
function handleBOMSubmit(e) {
    e.preventDefault();
    
    const bomId = document.getElementById('bomId').value;
    const formData = new FormData(e.target);
    
    // Collect components data
    const components = [];
    document.querySelectorAll('.component-item').forEach(component => {
        const name = component.querySelector('.component-name').value;
        const quantity = component.querySelector('.component-quantity').value;
        const unit = component.querySelector('.component-unit').value;
        const unitCost = component.querySelector('.component-unitcost').value;
        
        if (name && quantity && unitCost) {
            components.push({
                component_name: name,
                quantity: quantity,
                unit: unit,
                unit_cost: unitCost,
                total_cost: (parseFloat(quantity) * parseFloat(unitCost)).toFixed(2)
            });
        }
    });
    
    // Add components as JSON
    formData.append('components', JSON.stringify(components));
    
    // Calculate and add total cost
    const totalCostText = document.getElementById('totalCost').textContent;
    const totalCost = parseFloat(totalCostText.replace('R', '')) || 0;
    formData.append('total_cost', totalCost);
    
    const url = bomId ? '../../api/finance/bom/update.php' : '../../api/finance/bom/create.php';
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            closeBOMModal();
            loadStats();
            loadBOMs();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error saving BOM:', error);
        alert('Error saving BOM');
    });
}

// Edit BOM
function editBOM(id) {
    fetch(`../../api/finance/bom/get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const bom = data.data;
                
                document.getElementById('modalTitle').textContent = 'Edit Bill of Materials';
                document.getElementById('bomId').value = bom.id;
                document.getElementById('bomNumber').value = bom.bom_number;
                document.getElementById('productId').value = bom.product_id;
                document.getElementById('version').value = bom.version;
                document.getElementById('status').value = bom.status;
                document.getElementById('description').value = bom.description || '';
                document.getElementById('overheadPercentage').value = bom.overhead_percentage || 0;
                document.getElementById('notes').value = bom.notes || '';
                
                // Load components
                document.getElementById('componentsContainer').innerHTML = '';
                componentCounter = 0;
                
                if (bom.components && bom.components.length > 0) {
                    bom.components.forEach(comp => {
                        componentCounter++;
                        const componentHtml = `
                            <div class="component-item" id="component${componentCounter}">
                                <div class="component-row">
                                    <div class="component-field">
                                        <label>Component Name/Part Number *</label>
                                        <input type="text" class="component-name" name="components[${componentCounter}][name]" required value="${comp.component_name}">
                                    </div>
                                    <div class="component-field">
                                        <label>Quantity *</label>
                                        <input type="number" class="component-quantity" name="components[${componentCounter}][quantity]" required min="0" step="0.01" value="${comp.quantity}" onchange="calculateComponentCost(${componentCounter})">
                                    </div>
                                    <div class="component-field">
                                        <label>Unit</label>
                                        <select class="component-unit" name="components[${componentCounter}][unit]">
                                            <option value="pcs" ${comp.unit === 'pcs' ? 'selected' : ''}>Pieces</option>
                                            <option value="kg" ${comp.unit === 'kg' ? 'selected' : ''}>Kilograms</option>
                                            <option value="m" ${comp.unit === 'm' ? 'selected' : ''}>Meters</option>
                                            <option value="l" ${comp.unit === 'l' ? 'selected' : ''}>Liters</option>
                                            <option value="box" ${comp.unit === 'box' ? 'selected' : ''}>Boxes</option>
                                            <option value="set" ${comp.unit === 'set' ? 'selected' : ''}>Sets</option>
                                        </select>
                                    </div>
                                    <div class="component-field">
                                        <label>Unit Cost (R) *</label>
                                        <input type="number" class="component-unitcost" name="components[${componentCounter}][unit_cost]" required min="0" step="0.01" value="${comp.unit_cost}" onchange="calculateComponentCost(${componentCounter})">
                                    </div>
                                    <div class="component-field">
                                        <label>Total Cost</label>
                                        <input type="text" class="component-totalcost" id="componentTotal${componentCounter}" readonly value="R${parseFloat(comp.total_cost).toFixed(2)}">
                                    </div>
                                    <div class="component-remove">
                                        <button type="button" class="btn-remove" onclick="removeComponent(${componentCounter})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                        document.getElementById('componentsContainer').insertAdjacentHTML('beforeend', componentHtml);
                    });
                } else {
                    addComponent();
                }
                
                calculateTotalCost();
                document.getElementById('bomModal').style.display = 'block';
            }
        })
        .catch(error => console.error('Error loading BOM:', error));
}

// View BOM details
function viewBOM(id) {
    fetch(`../../api/finance/bom/get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayBOMDetails(data.data);
            }
        })
        .catch(error => console.error('Error loading BOM details:', error));
}

// Display BOM details
function displayBOMDetails(bom) {
    const content = `
        <div class="bom-details">
            <div class="detail-section">
                <h3>Basic Information</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <span class="detail-label">BOM Number</span>
                        <span class="detail-value">${bom.bom_number}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Product</span>
                        <span class="detail-value">${bom.product_name}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Version</span>
                        <span class="detail-value">${bom.version}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Status</span>
                        <span class="detail-value">${getStatusBadge(bom.status)}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Total Cost</span>
                        <span class="detail-value" style="color: #27ae60; font-size: 18px;"><strong>R${parseFloat(bom.total_cost).toFixed(2)}</strong></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Created Date</span>
                        <span class="detail-value">${formatDate(bom.created_at)}</span>
                    </div>
                </div>
                ${bom.description ? `<div class="detail-item" style="margin-top: 12px;">
                    <span class="detail-label">Description</span>
                    <span class="detail-value">${bom.description}</span>
                </div>` : ''}
            </div>

            <div class="detail-section">
                <h3>Components (${bom.components ? bom.components.length : 0} items)</h3>
                <table class="components-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Component Name</th>
                            <th>Quantity</th>
                            <th>Unit</th>
                            <th>Unit Cost</th>
                            <th>Total Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${bom.components ? bom.components.map((comp, index) => `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${comp.component_name}</td>
                                <td>${comp.quantity}</td>
                                <td>${comp.unit}</td>
                                <td>R${parseFloat(comp.unit_cost).toFixed(2)}</td>
                                <td><strong>R${parseFloat(comp.total_cost).toFixed(2)}</strong></td>
                            </tr>
                        `).join('') : '<tr><td colspan="6" class="text-center">No components</td></tr>'}
                    </tbody>
                </table>
            </div>

            <div class="detail-section">
                <h3>Cost Breakdown</h3>
                <div class="cost-summary">
                    <div class="cost-row">
                        <span class="cost-label">Material Cost:</span>
                        <span class="cost-value">R${calculateMaterialCost(bom.components).toFixed(2)}</span>
                    </div>
                    <div class="cost-row">
                        <span class="cost-label">Overhead (${bom.overhead_percentage || 0}%):</span>
                        <span class="cost-value">R${((calculateMaterialCost(bom.components) * (bom.overhead_percentage || 0)) / 100).toFixed(2)}</span>
                    </div>
                    <div class="cost-row total-cost">
                        <span class="cost-label">Total BOM Cost:</span>
                        <span class="cost-value">R${parseFloat(bom.total_cost).toFixed(2)}</span>
                    </div>
                </div>
            </div>

            ${bom.notes ? `<div class="detail-section">
                <h3>Notes</h3>
                <p style="white-space: pre-wrap;">${bom.notes}</p>
            </div>` : ''}
        </div>
    `;
    
    document.getElementById('bomDetailsContent').innerHTML = content;
    document.getElementById('viewBOMModal').style.display = 'block';
}

// Close view BOM modal
function closeViewBOMModal() {
    document.getElementById('viewBOMModal').style.display = 'none';
}

// Calculate material cost from components
function calculateMaterialCost(components) {
    if (!components) return 0;
    return components.reduce((sum, comp) => sum + parseFloat(comp.total_cost || 0), 0);
}

// Format date
function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Close modals on outside click
window.onclick = function(event) {
    const bomModal = document.getElementById('bomModal');
    const viewBOMModal = document.getElementById('viewBOMModal');
    
    if (event.target === bomModal) {
        closeBOMModal();
    }
    if (event.target === viewBOMModal) {
        closeViewBOMModal();
    }
};
