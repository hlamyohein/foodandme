<?php
// deliveries.php
session_start();
require __DIR__ . '../../config/db.php'; // adjust path

// ===== Fetch All Deliveries =====
$stmt = $pdo->query("SELECT d.*, o.order_id, r.name as rider_name 
                     FROM deliveries d
                     LEFT JOIN orders o ON d.order_id=o.order_id
                     LEFT JOIN riders r ON d.rider_id=r.rider_id
                     ORDER BY d.created_at DESC");
$deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Deliveries Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
        h1 { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 20px; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        th, td { padding: 12px 15px; text-align: left; }
        th { background: #1e1e2d; color: #fff; }
        tr:nth-child(even) { background: #f9f9f9; }
        form { margin: 20px 0; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        input, select { width: 100%; padding: 8px; margin-bottom: 10px; border-radius: 6px; border: 1px solid #ccc; }
        button { padding: 10px 16px; border: none; border-radius: 6px; background: #4e73df; color: #fff; font-weight: bold; cursor: pointer; }
        button:hover { background: #2e59d9; }
        .actions a, .actions button { margin-right: 8px; font-size: 12px; }
        .edit-btn { background: #36b9cc; color:#fff; border:none; border-radius:4px; padding:6px 10px; }
        .delete-btn { background: #e74a3b; color:#fff; border:none; border-radius:4px; padding:6px 10px; }
        .back-btn { display: inline-block; margin-bottom: 20px; padding: 10px 16px; background: #6c757d; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; }
        .back-btn:hover { background: #5a6268; }
    </style>
</head>
<body>
<a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
<h1>Deliveries Management</h1>

<!-- Add Delivery Form -->
<form id="addForm">
    <h2>Add Delivery</h2>
    <input type="number" name="order_id" placeholder="Order ID" required>
    <input type="number" name="rider_id" placeholder="Rider ID" required>
    <select name="status">
        <option value="pending">Pending</option>
        <option value="delivered">Delivered</option>
        <option value="canceled">Canceled</option>
    </select>
    <input type="date" name="delivery_date" required>
    <button type="submit">Add Delivery</button>
</form>

<!-- Deliveries Table -->
<table class="table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Order ID</th>
            <th>Rider</th>
            <th>Status</th>
            <th>Delivery Date</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($deliveries as $d): ?>
        <tr data-id="<?= $d['delivery_id'] ?>">
            <td><?= $d['delivery_id'] ?></td>
            <td><?= $d['order_id'] ?></td>
            <td><?= htmlspecialchars($d['rider_name']) ?></td>
            <td><?= ucfirst($d['status']) ?></td>
            <td><?= $d['delivery_date'] ?></td>
            <td><?= date("Y-m-d", strtotime($d['created_at'])) ?></td>
            <td class="actions">
                <button class="edit-btn"
                        data-id="<?= $d['delivery_id'] ?>"
                        data-order="<?= $d['order_id'] ?>"
                        data-rider="<?= $d['rider_id'] ?>"
                        data-status="<?= $d['status'] ?>"
                        data-date="<?= $d['delivery_date'] ?>">✏️ Edit</button>
                <button class="delete-btn" data-id="<?= $d['delivery_id'] ?>">❌ Delete</button>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form id="editForm">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Delivery</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="delivery_id" id="deliveryId">
            <input type="number" name="order_id" id="deliveryOrder" placeholder="Order ID" class="form-control mb-2" required>
            <input type="number" name="rider_id" id="deliveryRider" placeholder="Rider ID" class="form-control mb-2" required>
            <select name="status" id="deliveryStatus" class="form-control mb-2">
                <option value="pending">Pending</option>
                <option value="delivered">Delivered</option>
                <option value="canceled">Canceled</option>
            </select>
            <input type="date" name="delivery_date" id="deliveryDate" class="form-control mb-2" required>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
// --- Edit Modal ---
document.addEventListener('click', function(e){
    if(e.target.classList.contains('edit-btn')){
        const btn = e.target;
        document.getElementById('deliveryId').value = btn.dataset.id;
        document.getElementById('deliveryOrder').value = btn.dataset.order;
        document.getElementById('deliveryRider').value = btn.dataset.rider;
        document.getElementById('deliveryStatus').value = btn.dataset.status;
        document.getElementById('deliveryDate').value = btn.dataset.date;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }
});

// --- AJAX Edit ---
document.getElementById('editForm').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);
    fetch('update_delivery.php', { method:'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            const d = data.delivery;
            const row = document.querySelector(`tr[data-id='${d.id}']`);
            row.children[1].textContent = d.order_id;
            row.children[2].textContent = d.rider_name;
            row.children[3].textContent = d.status.charAt(0).toUpperCase()+d.status.slice(1);
            row.children[4].textContent = d.delivery_date;
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
        } else alert('Update failed!');
    }).catch(err=>{console.error(err); alert('Something went wrong');});
});

// --- AJAX Add ---
document.getElementById('addForm').addEventListener('submit', function(e){
    e.preventDefault();
    const formData = new FormData(this);
    fetch('add_delivery_ajax.php', { method:'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        if(data.success){
            const d = data.delivery;
            const tbody = document.querySelector('table tbody');
            const newRow = document.createElement('tr');
            newRow.setAttribute('data-id', d.id);
            newRow.innerHTML = `
                <td>${d.id}</td>
                <td>${d.order_id}</td>
                <td>${d.rider_name}</td>
                <td>${d.status.charAt(0).toUpperCase()+d.status.slice(1)}</td>
                <td>${d.delivery_date}</td>
                <td>${d.created_at}</td>
                <td class="actions">
                    <button class="edit-btn"
                        data-id="${d.id}"
                        data-order="${d.order_id}"
                        data-rider="${d.rider_id}"
                        data-status="${d.status}"
                        data-date="${d.delivery_date}">✏️ Edit</button>
                    <button class="delete-btn" data-id="${d.id}">❌ Delete</button>
                </td>`;
            tbody.prepend(newRow);
            this.reset();
        } else alert('Failed to add delivery');
    }).catch(err=>{console.error(err); alert('Something went wrong');});
});

// --- AJAX Delete ---
document.addEventListener('click', function(e){
    if(e.target.classList.contains('delete-btn')){
        if(confirm('Delete this delivery?')){
            const id = e.target.dataset.id;
            fetch('delete_delivery_ajax.php', {
                method: 'POST',
                body: new URLSearchParams({delivery_id: id})
            })
            .then(res => res.json())
            .then(data => {
                if(data.success){
                    document.querySelector(`tr[data-id='${id}']`).remove();
                } else alert('Failed to delete.');
            }).catch(err=>{console.error(err); alert('Something went wrong');});
        }
    }
});
</script>
</body>
</html>
