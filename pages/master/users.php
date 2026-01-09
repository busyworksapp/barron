<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../classes/MasterData.php';

// Guard: admin only
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    die('Forbidden: admin role required');
}

$md = new MasterData($GLOBALS['db'] ?? null);
$users = $md->getUsers(200, 0);
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Master — Users</title>
    <style>table{border-collapse:collapse;width:100%}td,th{border:1px solid #ddd;padding:8px}input,select{margin:4px 0}</style>
</head>
<body>
<h1>Users</h1>
<p><a href="/pages/master/products.php">Products</a> | <a href="/pages/master/departments.php">Departments</a> | <a href="/pages/master/users.php">Users</a> | <a href="/pages/master/stages.php">Stages</a></p>

<h2>Create/Edit user</h2>
<form id="userForm">
    <label>Username: <input name="username" required></label><br>
    <label>Email: <input name="email" type="email"></label><br>
    <label>Full Name: <input name="full_name"></label><br>
    <label>Password: <input name="password" type="password" placeholder="Leave empty to keep unchanged"></label><br>
    <label>Role: 
        <select name="role">
            <option value="operator">Operator</option>
            <option value="planner">Planner</option>
            <option value="manager">Manager</option>
            <option value="admin">Admin</option>
        </select>
    </label><br>
    <button type="submit">Save</button>
    <input type="hidden" name="id">
</form>

<h2>List</h2>
<table id="usersTable">
    <thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Role</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr data-id="<?=htmlspecialchars($u['id'])?>">
            <td><?=htmlspecialchars($u['id'])?></td>
            <td><?=htmlspecialchars($u['username'] ?? '')?></td>
            <td><?=htmlspecialchars($u['email'] ?? '')?></td>
            <td><?=htmlspecialchars($u['full_name'] ?? '')?></td>
            <td><?=htmlspecialchars($u['role'] ?? '')?></td>
            <td>
                <button class="edit">Edit</button>
                <button class="del">Delete</button>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
document.getElementById('userForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const form = e.target;
    const data = Object.fromEntries(new FormData(form).entries());
    // Don't send empty password on update
    if (data.id && !data.password) delete data.password;
    const res = await fetch('/api/master/users.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(data)});
    const json = await res.json();
    if (json.success) location.reload(); else alert('Save failed: ' + (json.error || ''));
});

document.querySelectorAll('.edit').forEach(btn => btn.addEventListener('click', function(){
    const tr = this.closest('tr');
    const id = tr.dataset.id;
    fetch('/api/master/users.php?id='+id).then(r=>r.json()).then(j=>{
        if (j.success && j.data) {
            const f = document.getElementById('userForm');
            f.elements['id'].value = j.data.id;
            f.elements['username'].value = j.data.username || '';
            f.elements['email'].value = j.data.email || '';
            f.elements['full_name'].value = j.data.full_name || '';
            f.elements['role'].value = j.data.role || 'operator';
            f.elements['password'].value = '';
        }
    });
}));

document.querySelectorAll('.del').forEach(btn => btn.addEventListener('click', async function(){
    if (!confirm('Delete this user?')) return;
    const tr = this.closest('tr');
    const id = tr.dataset.id;
    const res = await fetch('/api/master/users.php?id='+id, {method:'DELETE'});
    const j = await res.json();
    if (j.success) location.reload(); else alert('Delete failed: ' + (j.error || ''));
}));
</script>
</body>
</html>
