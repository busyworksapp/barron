<?php
require_once '../../config/config.php';
require_once '../../config/database.php';

requireLogin();

if (!hasPermission('master.view')) {
    header('Location: ../../index.php');
    exit;
}

$pageTitle = 'Products Management';
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Barron Production System</title>
    <link rel="stylesheet" href="../../assets/css/industrial.css">
    <link rel="stylesheet" href="../../assets/css/dashboard.css">
    <link rel="stylesheet" href="../../assets/css/master.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    
    <div class="dashboard-container">
        <?php include '../../includes/sidebar.php'; ?>
        
        <main class="main-content">
            <div class="page-header">
                <h1><?php echo $pageTitle; ?></h1>
                <div class="page-actions">
                    <?php if (hasPermission('master.edit')): ?>
                        <button type="button" class="btn btn-primary" onclick="openProductModal()">
                            <span class="icon">+</span> Add Product
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="search-filters">
                        <input type="text" id="searchInput" class="form-control" placeholder="Search products..." style="max-width: 300px;">
                        <select id="categoryFilter" class="form-control" style="max-width: 200px;">
                            <option value="">All Categories</option>
                            <option value="apparel">Apparel</option>
                            <option value="accessories">Accessories</option>
                            <option value="footwear">Footwear</option>
                            <option value="headwear">Headwear</option>
                            <option value="bags">Bags</option>
                            <option value="other">Other</option>
                        </select>
                        <select id="statusFilter" class="form-control" style="max-width: 150px;">
                            <option value="">All Status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                        <button type="button" class="btn btn-secondary" onclick="loadProducts()">Filter</button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Product Code</th>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Description</th>
                                    <th>Specifications</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="productsTableBody">
                                <tr>
                                    <td colspan="7" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Product Modal -->
    <div id="productModal" class="modal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h2 id="modalTitle">Add Product</h2>
                <button type="button" class="close-modal" onclick="closeProductModal()">&times;</button>
            </div>
            <form id="productForm">
                <div class="modal-body">
                    <input type="hidden" id="product_id" name="product_id">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="product_code" class="form-label required">Product Code</label>
                            <input type="text" id="product_code" name="product_code" class="form-control" required maxlength="50">
                        </div>
                        <div class="form-group">
                            <label for="category" class="form-label required">Category</label>
                            <select id="category" name="category" class="form-control" required>
                                <option value="">Select Category</option>
                                <option value="apparel">Apparel</option>
                                <option value="accessories">Accessories</option>
                                <option value="footwear">Footwear</option>
                                <option value="headwear">Headwear</option>
                                <option value="bags">Bags</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="product_name" class="form-label required">Product Name</label>
                        <input type="text" id="product_name" name="product_name" class="form-control" required maxlength="200">
                    </div>

                    <div class="form-group">
                        <label for="description" class="form-label">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Specifications</label>
                        <div id="specificationsContainer">
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
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm" onclick="addSpecRow()">
                            <span class="icon">+</span> Add Specification
                        </button>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" id="is_active" name="is_active" checked>
                            <span>Active</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeProductModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Specifications Modal -->
    <div id="specsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Product Specifications - <span id="specsProductName"></span></h2>
                <button type="button" class="close-modal" onclick="closeSpecsModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div id="specsContent">
                    <p class="text-center">Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeSpecsModal()">Close</button>
            </div>
        </div>
    </div>

    <script src="../../assets/js/products.js"></script>
</body>
</html>
