// Products Management JavaScript
let currentProductId = null;
let specRowIndex = 1;

// Load products on page load
document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
    
    // Setup form submission
    document.getElementById('productForm').addEventListener('submit', saveProduct);
    
    // Setup search on enter
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            loadProducts();
        }
    });
});

// Load products with filters
function loadProducts() {
    const search = document.getElementById('searchInput').value;
    const category = document.getElementById('categoryFilter').value;
    const status = document.getElementById('statusFilter').value;
    
    let url = '../../api/master/products/list.php?';
    if (search) url += `search=${encodeURIComponent(search)}&`;
    if (category) url += `category=${category}&`;
    if (status !== '') url += `is_active=${status}&`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayProducts(data.data);
            } else {
                showAlert('Error loading products: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading products', 'error');
        });
}

// Display products in table
function displayProducts(products) {
    const tbody = document.getElementById('productsTableBody');
    
    if (products.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-center">No products found</td></tr>';
        return;
    }
    
    let html = '';
    products.forEach(product => {
        const statusBadge = product.is_active == 1 
            ? '<span class="badge badge-success">Active</span>'
            : '<span class="badge badge-danger">Inactive</span>';
        
        const categoryBadge = getCategoryBadge(product.category);
        
        let specsPreview = '-';
        if (product.specifications) {
            try {
                const specs = typeof product.specifications === 'string' 
                    ? JSON.parse(product.specifications) 
                    : product.specifications;
                const count = Object.keys(specs).length;
                specsPreview = `<button class="btn-link" onclick="viewSpecs(${product.id}, '${escapeHtml(product.product_name)}')">${count} specification${count !== 1 ? 's' : ''}</button>`;
            } catch (e) {
                console.error('Error parsing specifications:', e);
            }
        }
        
        html += `
            <tr>
                <td>${escapeHtml(product.product_code)}</td>
                <td>${escapeHtml(product.product_name)}</td>
                <td>${categoryBadge}</td>
                <td>${product.description ? truncate(escapeHtml(product.description), 50) : '-'}</td>
                <td>${specsPreview}</td>
                <td>${statusBadge}</td>
                <td class="actions">
                    <button class="btn-action btn-edit" onclick="editProduct(${product.id})" title="Edit">
                        <span class="icon">✎</span>
                    </button>
                    <button class="btn-action btn-danger" onclick="deleteProduct(${product.id}, '${escapeHtml(product.product_name)}')" title="Delete">
                        <span class="icon">🗑</span>
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
}

// Get category badge HTML
function getCategoryBadge(category) {
    const badges = {
        'apparel': '<span class="badge badge-info">Apparel</span>',
        'accessories': '<span class="badge badge-secondary">Accessories</span>',
        'footwear': '<span class="badge badge-primary">Footwear</span>',
        'headwear': '<span class="badge badge-warning">Headwear</span>',
        'bags': '<span class="badge badge-success">Bags</span>',
        'other': '<span class="badge badge-secondary">Other</span>'
    };
    return badges[category] || category;
}

// Truncate text
function truncate(text, length) {
    if (!text || text.length <= length) return text;
    return text.substring(0, length) + '...';
}

// Open product modal for adding
function openProductModal() {
    currentProductId = null;
    document.getElementById('modalTitle').textContent = 'Add Product';
    document.getElementById('productForm').reset();
    document.getElementById('product_id').value = '';
    document.getElementById('is_active').checked = true;
    
    // Reset specifications to one empty row
    document.getElementById('specificationsContainer').innerHTML = `
        <div class="spec-row" data-index="0">
            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <input type="text" class="form-control spec-key" placeholder="Key (e.g., Material)" maxlength="100">
                </div>
                <div class="form-group" style="flex: 2;">
                    <input type="text" class="form-control spec-value" placeholder="Value (e.g., 100% Cotton)" maxlength="500">
                </div>
                <div class="form-group" style="flex: 0 0 auto;">
                    <button type="button" class="btn btn-danger btn-sm" onclick="removeSpecRow(0)" title="Remove">
                        <span class="icon">✖</span>
                    </button>
                </div>
            </div>
        </div>
    `;
    specRowIndex = 1;
    
    document.getElementById('productModal').classList.add('active');
}

// Close product modal
function closeProductModal() {
    document.getElementById('productModal').classList.remove('active');
}

// Add specification row
function addSpecRow() {
    const container = document.getElementById('specificationsContainer');
    const newRow = document.createElement('div');
    newRow.className = 'spec-row';
    newRow.setAttribute('data-index', specRowIndex);
    newRow.innerHTML = `
        <div class="form-row">
            <div class="form-group" style="flex: 1;">
                <input type="text" class="form-control spec-key" placeholder="Key (e.g., Material)" maxlength="100">
            </div>
            <div class="form-group" style="flex: 2;">
                <input type="text" class="form-control spec-value" placeholder="Value (e.g., 100% Cotton)" maxlength="500">
            </div>
            <div class="form-group" style="flex: 0 0 auto;">
                <button type="button" class="btn btn-danger btn-sm" onclick="removeSpecRow(${specRowIndex})" title="Remove">
                    <span class="icon">✖</span>
                </button>
            </div>
        </div>
    `;
    container.appendChild(newRow);
    specRowIndex++;
}

// Remove specification row
function removeSpecRow(index) {
    const row = document.querySelector(`.spec-row[data-index="${index}"]`);
    if (row) {
        // Don't allow removing the last row
        const container = document.getElementById('specificationsContainer');
        if (container.children.length > 1) {
            row.remove();
        } else {
            // Clear the inputs instead
            row.querySelector('.spec-key').value = '';
            row.querySelector('.spec-value').value = '';
        }
    }
}

// Get specifications from form
function getSpecifications() {
    const specs = {};
    const rows = document.querySelectorAll('.spec-row');
    
    rows.forEach(row => {
        const key = row.querySelector('.spec-key').value.trim();
        const value = row.querySelector('.spec-value').value.trim();
        
        if (key && value) {
            specs[key] = value;
        }
    });
    
    return Object.keys(specs).length > 0 ? specs : null;
}

// Save product
function saveProduct(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const productId = document.getElementById('product_id').value;
    
    const data = {
        product_code: formData.get('product_code'),
        product_name: formData.get('product_name'),
        category: formData.get('category'),
        description: formData.get('description'),
        specifications: getSpecifications(),
        is_active: document.getElementById('is_active').checked ? 1 : 0
    };
    
    if (productId) {
        data.product_id = productId;
    }
    
    const url = productId 
        ? '../../api/master/products/update.php'
        : '../../api/master/products/create.php';
    
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(productId ? 'Product updated successfully' : 'Product created successfully', 'success');
            closeProductModal();
            loadProducts();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error saving product', 'error');
    });
}

// Edit product
function editProduct(id) {
    fetch(`../../api/master/products/get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const product = data.data;
                currentProductId = id;
                
                document.getElementById('modalTitle').textContent = 'Edit Product';
                document.getElementById('product_id').value = product.id;
                document.getElementById('product_code').value = product.product_code;
                document.getElementById('product_name').value = product.product_name;
                document.getElementById('category').value = product.category || '';
                document.getElementById('description').value = product.description || '';
                document.getElementById('is_active').checked = product.is_active == 1;
                
                // Load specifications
                loadSpecificationsForEdit(product.specifications);
                
                document.getElementById('productModal').classList.add('active');
            } else {
                showAlert('Error loading product: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading product', 'error');
        });
}

// Load specifications for editing
function loadSpecificationsForEdit(specificationsJson) {
    const container = document.getElementById('specificationsContainer');
    container.innerHTML = '';
    specRowIndex = 0;
    
    let specs = {};
    if (specificationsJson) {
        try {
            specs = typeof specificationsJson === 'string' 
                ? JSON.parse(specificationsJson) 
                : specificationsJson;
        } catch (e) {
            console.error('Error parsing specifications:', e);
        }
    }
    
    if (Object.keys(specs).length === 0) {
        // Add one empty row
        addSpecRow();
    } else {
        // Add rows for each specification
        Object.entries(specs).forEach(([key, value]) => {
            const newRow = document.createElement('div');
            newRow.className = 'spec-row';
            newRow.setAttribute('data-index', specRowIndex);
            newRow.innerHTML = `
                <div class="form-row">
                    <div class="form-group" style="flex: 1;">
                        <input type="text" class="form-control spec-key" placeholder="Key (e.g., Material)" maxlength="100" value="${escapeHtml(key)}">
                    </div>
                    <div class="form-group" style="flex: 2;">
                        <input type="text" class="form-control spec-value" placeholder="Value (e.g., 100% Cotton)" maxlength="500" value="${escapeHtml(value)}">
                    </div>
                    <div class="form-group" style="flex: 0 0 auto;">
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeSpecRow(${specRowIndex})" title="Remove">
                            <span class="icon">✖</span>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newRow);
            specRowIndex++;
        });
    }
}

// Delete product
function deleteProduct(id, name) {
    if (!confirm(`Are you sure you want to delete product "${name}"?\n\nThis action cannot be undone.`)) {
        return;
    }
    
    fetch('../../api/master/products/delete.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ product_id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Product deleted successfully', 'success');
            loadProducts();
        } else {
            showAlert('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Error deleting product', 'error');
    });
}

// View specifications
function viewSpecs(id, name) {
    document.getElementById('specsProductName').textContent = name;
    document.getElementById('specsContent').innerHTML = '<p class="text-center">Loading...</p>';
    
    fetch(`../../api/master/products/get.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySpecs(data.data.specifications);
                document.getElementById('specsModal').classList.add('active');
            } else {
                showAlert('Error loading specifications: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('Error loading specifications', 'error');
        });
}

// Display specifications
function displaySpecs(specificationsJson) {
    const container = document.getElementById('specsContent');
    
    let specs = {};
    if (specificationsJson) {
        try {
            specs = typeof specificationsJson === 'string' 
                ? JSON.parse(specificationsJson) 
                : specificationsJson;
        } catch (e) {
            console.error('Error parsing specifications:', e);
        }
    }
    
    if (Object.keys(specs).length === 0) {
        container.innerHTML = '<p class="text-muted">No specifications available</p>';
        return;
    }
    
    let html = '<table class="table table-striped"><tbody>';
    Object.entries(specs).forEach(([key, value]) => {
        html += `
            <tr>
                <td style="font-weight: bold; width: 30%;">${escapeHtml(key)}</td>
                <td>${escapeHtml(value)}</td>
            </tr>
        `;
    });
    html += '</tbody></table>';
    
    container.innerHTML = html;
}

// Close specifications modal
function closeSpecsModal() {
    document.getElementById('specsModal').classList.remove('active');
}

// Show alert message
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    alertDiv.style.position = 'fixed';
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '10000';
    alertDiv.style.minWidth = '300px';
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

// Escape HTML to prevent XSS
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
