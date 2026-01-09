<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../classes/MasterData.php';
$md = new MasterData($GLOBALS['db'] ?? null);
$departments = $md->getDepartments(200, 0);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Master — Departments</title>
    <style>table{border-collapse:collapse;width:100%}td,th{border:1px solid #ddd;padding:8px}</style>
</head>
<body>
<h1>Departments</h1>
<p><a href="/pages/master/products.php">Products</a> | <a href="/pages/master/departments.php">Departments</a></p>

<h2>Create department</h2>
<form id="deptForm">
    <label>Code: <input name="code"></label><br>
    <label>Name: <input name="name"></label><br>
    <label>Description: <br><textarea name="description" rows="3" cols="40"></textarea></label><br>
    <button type="submit">Save</button>
    <input type="hidden" name="id">
</form>

<h2>List</h2>
<table id="departmentsTable">
    <thead><tr><th>ID</th><th>Code</th><th>Name</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($departments as $d): ?>
        <tr data-id="<?=htmlspecialchars($d['id'])?>">
            <td><?=htmlspecialchars($d['id'])?></td>
            <td><?=htmlspecialchars($d['code'] ?? '')?></td>
            <td><?=htmlspecialchars($d['name'] ?? '')?></td>
            <td>
                <button class="edit">Edit</button>
                <button class="del">Delete</button>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
document.getElementById('deptForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const data = Object.fromEntries(new FormData(e.target).entries());
    const res = await fetch('/api/master/departments.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data)});
    const json = await res.json();
    if (json.success) location.reload(); else alert('Save failed');
});

document.querySelectorAll('.edit').forEach(btn => btn.addEventListener('click', function(){
    const tr = this.closest('tr');
    const id = tr.dataset.id;
    fetch('/api/master/departments.php?id='+id).then(r=>r.json()).then(j=>{
        if (j.success && j.data) {
            const f = document.getElementById('deptForm');
            f.elements['id'].value = j.data.id;
            f.elements['code'].value = j.data.code || '';
            f.elements['name'].value = j.data.name || '';
            f.elements['description'].value = j.data.description || '';
        }
    });
}));

document.querySelectorAll('.del').forEach(btn => btn.addEventListener('click', async function(){
    if (!confirm('Delete this department?')) return;
    const tr = this.closest('tr');
    const id = tr.dataset.id;
    const res = await fetch('/api/master/departments.php?id='+id, {method:'DELETE'});
    const j = await res.json();
    if (j.success) location.reload(); else alert('Delete failed');
}));
</script>
</body>
</html>
