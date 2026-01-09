<?php
// Minimal admin page for products. Integrate with project layout as needed.
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../classes/MasterData.php';
$md = new MasterData($GLOBALS['db'] ?? null);
$products = $md->getProducts(200, 0);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Master — Products</title>
    <style>table{border-collapse:collapse;width:100%}td,th{border:1px solid #ddd;padding:8px}</style>
</head>
<body>
<h1>Products</h1>
<p><a href="/pages/master/departments.php">Departments</a> | <a href="/pages/master/products.php">Products</a></p>

<h2>Create product</h2>
<form id="productForm">
    <label>SKU: <input name="sku"></label><br>
    <label>Name: <input name="name"></label><br>
    <label>Unit: <input name="unit"></label><br>
    <label>Description: <br><textarea name="description" rows="3" cols="40"></textarea></label><br>
    <button type="submit">Save</button>
    <input type="hidden" name="id">
</form>

<h2>List</h2>
<table id="productsTable">
    <thead><tr><th>ID</th><th>SKU</th><th>Name</th><th>Unit</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($products as $p): ?>
        <tr data-id="<?=htmlspecialchars($p['id'])?>">
            <td><?=htmlspecialchars($p['id'])?></td>
            <td><?=htmlspecialchars($p['sku'] ?? '')?></td>
            <td><?=htmlspecialchars($p['name'] ?? '')?></td>
            <td><?=htmlspecialchars($p['unit'] ?? '')?></td>
            <td>
                <button class="edit">Edit</button>
                <button class="del">Delete</button>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
document.getElementById('productForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const form = e.target;
    const data = Object.fromEntries(new FormData(form).entries());
    const res = await fetch('/api/master/products.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data)});
    const json = await res.json();
    if (json.success) location.reload(); else alert('Save failed');
});

document.querySelectorAll('.edit').forEach(btn => btn.addEventListener('click', function(){
    const tr = this.closest('tr');
    const id = tr.dataset.id;
    fetch('/api/master/products.php?id='+id).then(r=>r.json()).then(j=>{
        if (j.success && j.data) {
            const f = document.getElementById('productForm');
            f.elements['id'].value = j.data.id;
            f.elements['sku'].value = j.data.sku || '';
            f.elements['name'].value = j.data.name || '';
            f.elements['unit'].value = j.data.unit || '';
            f.elements['description'].value = j.data.description || '';
        }
    });
}));

document.querySelectorAll('.del').forEach(btn => btn.addEventListener('click', async function(){
    if (!confirm('Delete this product?')) return;
    const tr = this.closest('tr');
    const id = tr.dataset.id;
    const res = await fetch('/api/master/products.php?id='+id, {method:'DELETE'});
    const j = await res.json();
    if (j.success) location.reload(); else alert('Delete failed');
}));
</script>
</body>
</html>
