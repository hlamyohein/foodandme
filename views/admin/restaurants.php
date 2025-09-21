<?php
session_start();
require_once "../../config/db.php";
include "includes/header.php";

// Fetch restaurants with vendor info
$restaurants = $pdo->query("SELECT r.*, u.name AS vendor_name 
                             FROM restaurants r 
                             JOIN users u ON r.user_id = u.user_id 
                             ORDER BY r.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Restaurants Approval</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { font-family: Arial, sans-serif; margin:20px; background:#f4f6f9;}
.table th, .table td { vertical-align: middle; }
.actions button { margin-right:5px; }
</style>
</head>
<body>
<div class="main-content">
<h1>Restaurants Approval</h1>

<h2>Restaurants List</h2>
<table class="table table-bordered">
<thead>
<tr>
<th>ID</th><th>Vendor Name</th><th>User ID</th><th>Name</th><th>Phone</th><th>Cuisine</th><th>Status</th><th>Created</th><th>Actions</th>
</tr>
</thead>
<tbody id="restaurantsTable">
<?php foreach($restaurants as $r): ?>
<tr data-id="<?= $r['restaurant_id'] ?>">
<td><?= $r['restaurant_id'] ?></td>
<td><?= htmlspecialchars($r['vendor_name']) ?></td>
<td><?= $r['user_id'] ?></td>
<td><?= htmlspecialchars($r['name']) ?></td>
<td><?= htmlspecialchars($r['phone']) ?></td>
<td><?= htmlspecialchars($r['cuisine_type']) ?></td>
<td><span class="badge bg-<?= $r['status']=='active'?'success':'secondary' ?>"><?= ucfirst($r['status']) ?></span></td>
<td><?= date("Y-m-d", strtotime($r['created_at'])) ?></td>
<td class="actions">
<?php if($r['status']=='active'): ?>
<button class="btn btn-sm btn-warning edit-btn"
 data-id="<?= $r['restaurant_id'] ?>" 
 data-user="<?= $r['user_id'] ?>" 
 data-name="<?= htmlspecialchars($r['name']) ?>" 
 data-address="<?= htmlspecialchars($r['address']) ?>" 
 data-phone="<?= htmlspecialchars($r['phone']) ?>" 
 data-cuisine="<?= htmlspecialchars($r['cuisine_type']) ?>" 
 data-status="<?= $r['status'] ?>">✏️ Edit</button>
<button class="btn btn-sm btn-danger deactivate-btn" data-id="<?= $r['restaurant_id'] ?>">🚫 Deactivate</button>
<?php else: ?>
<button class="btn btn-sm btn-success activate-btn" data-id="<?= $r['restaurant_id'] ?>">✅ Approve</button>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<!-- Edit Restaurant Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
<div class="modal-dialog">
<form id="editForm">
<div class="modal-content">
<div class="modal-header">
<h5 class="modal-title">Edit Restaurant</h5>
<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
<input type="hidden" name="restaurant_id" id="editRestaurantId">
<input type="number" name="user_id" id="editUserId" readonly class="form-control mb-2">
<input type="text" name="name" id="editName" class="form-control mb-2">
<input type="text" name="address" id="editAddress" class="form-control mb-2">
<input type="text" name="phone" id="editPhone" class="form-control mb-2">
<input type="text" name="cuisine_type" id="editCuisine" class="form-control mb-2">
<select name="status" id="editStatus" class="form-control mb-2">
<option value="active">Active</option>
<option value="inactive">Inactive</option>
</select>
</div>
<div class="modal-footer">
<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
<button type="submit" class="btn btn-primary">Save Changes</button>
</div>
</div>
</form>
</div>
</div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// --- Edit Modal ---
document.addEventListener('click', function(e){
if(e.target.classList.contains('edit-btn')){
    const btn=e.target;
    document.getElementById('editRestaurantId').value=btn.dataset.id;
    document.getElementById('editUserId').value=btn.dataset.user;
    document.getElementById('editName').value=btn.dataset.name;
    document.getElementById('editAddress').value=btn.dataset.address;
    document.getElementById('editPhone').value=btn.dataset.phone;
    document.getElementById('editCuisine').value=btn.dataset.cuisine;
    document.getElementById('editStatus').value=btn.dataset.status;
    const modal=new bootstrap.Modal(document.getElementById('editModal'));
    modal.show();
}
});

// --- Save Edit ---
document.getElementById('editForm').addEventListener('submit', function(e){
e.preventDefault();
const fd=new FormData(this); fd.append('action','edit_restaurant');
fetch('restaurant_crud.php',{method:'POST', body:fd}).then(r=>r.json()).then(data=>{
    if(data.success){ alert("Restaurant updated!"); location.reload(); }
    else alert(data.message);
});
});

// --- Approve / Deactivate ---
document.addEventListener('click', function(e){
if(e.target.classList.contains('activate-btn')){
    const id=e.target.dataset.id;
    const fd=new FormData(); fd.append('action','activate'); fd.append('restaurant_id',id);
    fetch('restaurant_crud.php',{method:'POST', body:fd}).then(r=>r.json()).then(data=>{
        if(data.success) location.reload(); else alert(data.message);
    });
}
if(e.target.classList.contains('deactivate-btn')){
    const id=e.target.dataset.id;
    if(!confirm("Mark as inactive?")) return;
    const fd=new FormData(); fd.append('action','delete'); fd.append('restaurant_id',id);
    fetch('restaurant_crud.php',{method:'POST', body:fd}).then(r=>r.json()).then(data=>{
        if(data.success) location.reload(); else alert(data.message);
    });
}
});
</script>
</body>
</html>
