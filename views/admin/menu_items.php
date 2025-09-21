<?php
// menu_items.php
session_start();// adjust path
include "includes/header.php";
require_once __DIR__ . "../../config/db.php";
// ===== Fetch All Menu Items with Restaurant Name =====
if (isset($_GET['restaurant_id']) && is_numeric($_GET['restaurant_id'])) {
    $restaurant_id = (int) $_GET['restaurant_id'];
    $stmt = $pdo->prepare("
        SELECT m.*, r.name AS restaurant_name 
        FROM menu_items m 
        JOIN restaurants r ON m.restaurant_id = r.restaurant_id 
        WHERE m.restaurant_id = ? 
        ORDER BY m.created_at DESC
    ");
    $stmt->execute([$restaurant_id]);
    $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch only this restaurant for dropdown
    $restaurants = $pdo->prepare("SELECT restaurant_id, name FROM restaurants WHERE restaurant_id=?");
    $restaurants->execute([$restaurant_id]);
    $restaurants = $restaurants->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmt = $pdo->query("
        SELECT m.*, r.name AS restaurant_name 
        FROM menu_items m 
        JOIN restaurants r ON m.restaurant_id = r.restaurant_id 
        ORDER BY m.created_at DESC
    ");
    $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $restaurants = $pdo->query("SELECT restaurant_id, name FROM restaurants ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

/*$stmt = $pdo->query("
    SELECT m.*, r.name AS restaurant_name 
    FROM menu_items m 
    JOIN restaurants r ON m.restaurant_id = r.restaurant_id 
    ORDER BY m.created_at DESC
");*/
$menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===== Fetch Restaurants for dropdown =====
$restaurants = $pdo->query("SELECT restaurant_id, name FROM restaurants ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Menu Items Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; padding: 20px; }
        h1 { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; background: #fff; margin-top: 20px; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        th, td { padding: 12px 15px; text-align: left; }
        th { background: #1e1e2d; color: #fff; }
        tr:nth-child(even) { background: #f9f9f9; }
        form { margin: 20px 0; background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.1); }
        input, select, textarea { width: 100%; padding: 8px; margin-bottom: 10px; border-radius: 6px; border: 1px solid #ccc; }
        button { padding: 10px 16px; border: none; border-radius: 6px; background: #4e73df; color: #fff; font-weight: bold; cursor: pointer; }
        button:hover { background: #2e59d9; }
        .actions button { margin-right: 8px; padding: 6px 10px; border-radius: 4px; font-size: 12px; border: none; }
        .edit-btn { background: #36b9cc; color: #fff; }
        .delete-btn { background: #e74a3b; color: #fff; }
        .back-btn { display: inline-block; margin-bottom: 20px; padding: 10px 16px; background: #6c757d; color: #fff; text-decoration: none; border-radius: 6px; font-weight: bold; }
        .back-btn:hover { background: #5a6268; }
    </style>
</head>
<body>
    <div class="main-content">
    <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
    <h1>Menu Items Management</h1>
    <?php if (isset($restaurant_id)): ?>
    <h3>Showing menu for: 
        <?= htmlspecialchars($restaurants[0]['name'] ?? '') ?>
    </h3>
    <a href="menu_items.php" class="btn btn-secondary mb-3">Show All Items</a>
<?php endif; ?>

    <!-- Add New Menu Item Form -->
    <form id="addForm">
        <h2>Add Menu Item</h2>
        <select name="restaurant_id" required>
            <option value="">Select Restaurant</option>
            <?php foreach ($restaurants as $res): ?>
                <option value="<?= $res['restaurant_id'] ?>"><?= htmlspecialchars($res['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="name" placeholder="Item Name" required>
        <textarea name="description" placeholder="Description"></textarea>
        <input type="number" step="0.01" name="price" placeholder="Price" required>
        <select name="status">
            <option value="available">Available</option>
            <option value="unavailable">Unavailable</option>
        </select>
        <button type="submit">Add Item</button>
    </form>

    <!-- Menu Items Table -->
    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Restaurant</th>
                <th>Name</th>
                <th>Price</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($menu_items as $m): ?>
                <tr data-id="<?= $m['menu_item_id'] ?>">
                    <td><?= $m['menu_item_id'] ?></td>
                    <td><?= htmlspecialchars($m['restaurant_name']) ?></td>
                    <td><?= htmlspecialchars($m['name']) ?></td>
                    <td><?= number_format($m['price'], 2) ?></td>
                    <td><?= ucfirst($m['status']) ?></td>
                    <td><?= date("Y-m-d", strtotime($m['created_at'])) ?></td>
                    <td class="actions">
                        <button class="edit-btn"
                            data-id="<?= $m['menu_item_id'] ?>"
                            data-restaurant="<?= $m['restaurant_id'] ?>"
                            data-name="<?= htmlspecialchars($m['name']) ?>"
                            data-description="<?= htmlspecialchars($m['description']) ?>"
                            data-price="<?= $m['price'] ?>"
                            data-status="<?= $m['status'] ?>">✏️ Edit</button>
                        <button class="delete-btn" data-id="<?= $m['menu_item_id'] ?>">❌ Delete</button>
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
              <h5 class="modal-title">Edit Menu Item</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="menu_item_id" id="menuItemId">
                <select name="restaurant_id" id="menuItemRestaurant" class="form-control mb-2">
                    <?php foreach ($restaurants as $res): ?>
                        <option value="<?= $res['restaurant_id'] ?>"><?= htmlspecialchars($res['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="name" id="menuItemName" placeholder="Item Name" required class="form-control mb-2">
                <textarea name="description" id="menuItemDescription" placeholder="Description" class="form-control mb-2"></textarea>
                <input type="number" step="0.01" name="price" id="menuItemPrice" placeholder="Price" required class="form-control mb-2">
                <select name="status" id="menuItemStatus" class="form-control mb-2">
                    <option value="available">Available</option>
                    <option value="unavailable">Unavailable</option>
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
    // --- Edit Modal Binding ---
    function bindEditButtons() {
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', () => {
                document.getElementById('menuItemId').value = button.dataset.id;
                document.getElementById('menuItemRestaurant').value = button.dataset.restaurant;
                document.getElementById('menuItemName').value = button.dataset.name;
                document.getElementById('menuItemDescription').value = button.dataset.description;
                document.getElementById('menuItemPrice').value = button.dataset.price;
                document.getElementById('menuItemStatus').value = button.dataset.status;
                new bootstrap.Modal(document.getElementById('editModal')).show();
            });
        });
    }
    bindEditButtons();

    // --- AJAX Edit ---
    document.getElementById('editForm').addEventListener('submit', function(e){
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'update');

        fetch('menu_item_crud.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success){
                const r = data.menu_item;
                const row = document.querySelector(`tr[data-id='${r.id}']`);
                row.children[1].textContent = r.restaurant_name;
                row.children[2].textContent = r.name;
                row.children[3].textContent = parseFloat(r.price).toFixed(2);
                row.children[4].textContent = r.status.charAt(0).toUpperCase() + r.status.slice(1);
                bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            } else {
                alert('Update failed!');
            }
        });
    });

    // --- AJAX Add ---
    document.getElementById('addForm').addEventListener('submit', function(e){
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'add');

        fetch('menu_item_crud.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success){
                const r = data.menu_item;
                const tbody = document.querySelector('table tbody');
                const newRow = document.createElement('tr');
                newRow.setAttribute('data-id', r.id);
                newRow.innerHTML = `
                    <td>${r.id}</td>
                    <td>${r.restaurant_name}</td>
                    <td>${r.name}</td>
                    <td>${parseFloat(r.price).toFixed(2)}</td>
                    <td>${r.status.charAt(0).toUpperCase() + r.status.slice(1)}</td>
                    <td>${r.created_at}</td>
                    <td class="actions">
                        <button class="edit-btn"
                            data-id="${r.id}"
                            data-restaurant="${r.restaurant_id}"
                            data-name="${r.name}"
                            data-description="${r.description}"
                            data-price="${r.price}"
                            data-status="${r.status}">✏️ Edit</button>
                        <button class="delete-btn" data-id="${r.id}">❌ Delete</button>
                    </td>
                `;
                tbody.prepend(newRow);
                bindEditButtons();
                bindDeleteButtons();
                this.reset();
            } else {
                alert('Failed to add menu item');
            }
        });
    });

    // --- AJAX Delete ---
    function bindDeleteButtons() {
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                if(confirm("Delete this menu item?")) {
                    const fd = new FormData();
                    fd.append('action', 'delete');
                    fd.append('menu_item_id', this.dataset.id);

                    fetch('menu_item_crud.php', { method: 'POST', body: fd })
                    .then(res => res.json())
                    .then(data => {
                        if(data.success) {
                            document.querySelector(`tr[data-id='${data.id}']`).remove();
                        } else {
                            alert("Delete failed");
                        }
                    });
                }
            });
        });
    }
    bindDeleteButtons();
    </script>
</body>
</html>
