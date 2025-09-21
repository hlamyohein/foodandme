<?php

if (!defined('IS_VALID_ENTRY_POINT')) {
    die('Direct access not allowed.');
}

$userId = $_SESSION['user_id'] ;
$message = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $phone = trim($_POST['phone']);
    $fullName = $firstName . ' ' . $lastName;

    // Basic validation
    if (!empty($firstName) && !empty($phone)) {
        $updateStmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE user_id = ?");
        if ($updateStmt->execute([$fullName, $phone, $userId])) {
            $message = "Profile updated successfully!";
            // Refresh user data to show updated info
            $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $message = "Error updating profile.";
        }
    } else {
        $message = "Please fill in all required fields.";
    }
}

// Split current name for the form
$name_parts = explode(' ', $user['name'] ?? '', 2);
$current_first_name = $name_parts[0];
$current_last_name = $name_parts[1] ?? '';
?>

<h2>My profile</h2>

<?php if ($message): ?>
    <div class="success-message"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form action="dashboard.php?page=profile" method="POST" class="profile-form">
    <div class="form-row">
        <div class="form-group">
            <label for="first_name">First name</label>
            <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($current_first_name) ?>" required>
        </div>
        <div class="form-group">
            <label for="last_name">Last name</label>
            <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($current_last_name) ?>">
        </div>
    </div>
    <div class="form-group">
        <label for="phone">Mobile number</label>
        <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" required>
    </div>
    <div class="form-group">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" readonly style="background-color:#f0f0f0;">
        <small>Email address cannot be changed.</small>
    </div>

    <button type="submit" class="save-btn">Save</button>
</form>