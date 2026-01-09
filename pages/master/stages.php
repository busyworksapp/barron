<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../classes/MasterData.php';

// Guard: admin only
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    die('Forbidden: admin role required');
}

$md = new MasterData($GLOBALS['db'] ?? null);
$stages = $md->getStages(200, 0);
$departments = $md->getDepartments(200, 0);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Master — Production Stages</title>
    <style>table{border-collapse:collapse;width:100%}td,th{border:1px solid #ddd;padding:8px}input,select{margin:4px 0}</style>
</head>
<body>
<h1>Production Stages</h1>
<p><a href="/pages/master/products.php">Products</a> | <a href="/pages/master/departments.php">Departments</a> | <a href="/pages/master/users.php">Users</a> | <a href="/pages/master/stages.php">Stages</a></p>

<h2>Create/Edit stage</h2>
<form id="stageForm">
    <label>Name: <input name="name" required></label><br>
    <label>Description: <br><textarea name="description" rows="3" cols="40"></textarea></label><br>
    <label>Order: <input name="stage_order" type="number" value="0"></label><br>
    <label>Department:
        <select name="department_id">
            <option value="">None</option>
            <?php foreach ($departments as $d): ?>
                <option value="<?=htmlspecialchars($d['id'])?>"><?=htmlspecialchars($d['name'])?></option>
            <?php endforeach; ?>
        </select>
    </label><br>
    <button type="submit">Save</button>
    <input type="hidden" name="id">
</form>

<h2>List</h2>
<table id="stagesTable">
    <thead><tr><th>ID</th><th>Name</th><th>Order</th><th>Dept ID</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($stages as $s): ?>
        <tr data-id="<?=htmlspecialchars($s['id'])?>">
            <td><?=htmlspecialchars($s['id'])?></td>
            <td><?=htmlspecialchars($s['name'] ?? '')?></td>
            <td><?=htmlspecialchars($s['stage_order'] ?? '0')?></td>
            <td><?=htmlspecialchars($s['department_id'] ?? '')?></td>
            <td>
                <button class="edit">Edit</button>
                <button class="del">Delete</button>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
document.getElementById('stageForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const form = e.target;
    const data = Object.fromEntries(new FormData(form).entries());
    const res = await fetch('/api/master/stages.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data)});
    const json = await res.json();
    if (json.success) location.reload(); else alert('Save failed: ' + (json.error || ''));
});

document.querySelectorAll('.edit').forEach(btn => btn.addEventListener('click', function(){
    const tr = this.closest('tr');
    const id = tr.dataset.id;
    fetch('/api/master/stages.php?id='+id).then(r=>r.json()).then(j=>{
        if (j.success && j.data) {
            const f = document.getElementById('stageForm');
            f.elements['id'].value = j.data.id;
            f.elements['name'].value = j.data.name || '';
            f.elements['description'].value = j.data.description || '';
            f.elements['stage_order'].value = j.data.stage_order || 0;
            f.elements['department_id'].value = j.data.department_id || '';
        }
    });
}));

document.querySelectorAll('.del').forEach(btn => btn.addEventListener('click', async function(){
    if (!confirm('Delete this stage?')) return;
    const tr = this.closest('tr');
    const id = tr.dataset.id;
    const res = await fetch('/api/master/stages.php?id='+id, {method:'DELETE'});
    const j = await res.json();
    if (j.success) location.reload(); else alert('Delete failed: ' + (j.error || ''));
}));
</script>
</body>
</html>
