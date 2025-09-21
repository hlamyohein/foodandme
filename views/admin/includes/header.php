<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

//require_once __DIR__ . "../../config/db.php";
require_once '../../config/db.php';
// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: /foodandme/views/admin/adminlogin.php");
    exit;
}

// Fetch user info
$stmt = $pdo->prepare("SELECT name FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Determine current page
$current = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar toggle -->
 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
<span id="sidebarToggle">&#9776;</span>

<div id="sidebar" class="sidebar">
    <div class="sidebar-header profile">
        <!-- <img class="profile-info" src="https://i.pravatar.cc/50" alt="profile">
        <span class="pac">FoodApp Admin</span> -->
                <div class="customer_brand__logo">
                    <i class="fas fa-utensils"></i>
                </div>
                <span class="pac" style="margin-left: 20px;font-size: 30px;">Food<span>&amp;</span>Me</span>
    </div>

    <ul>
        <li><a href="dashboard.php" class="<?= $current=='dashboard.php'?'active':'' ?>"><img src="iconn/dashboards.png"><span>Dashboard</span></a></li>
        <li><a href="restaurants.php" class="<?= $current=='restaurants.php'?'active':'' ?>"><img src="iconn/restaur.png"><span>Restaurants</span></a></li>
        <li><a href="order.php" class="<?= $current=='order.php'?'active':'' ?>"><img src="iconn/order.png"><span>Orders</span></a></li>
        <li><a href="deliveries.php" class="<?= $current=='deliveries.php'?'active':'' ?>"><img src="iconn/delivery.png"><span>Deliveries</span></a></li>
        <li><a href="users.php" class="<?= $current=='users.php'?'active':'' ?>"><img src="iconn/cust.png"><span>Customers</span></a></li>
        <li><a href="reports.php" class="<?= $current=='reports.php'?'active':'' ?>"><img src="iconn/report.png"><span>Review</span></a></li>
        <li><a href="settingadmin.php" class="<?= $current=='settingadmin.php'?'active':'' ?>"><img src="iconn/setting.png"><span>Settings</span></a></li>
        <li><a href="riders.php"><img src="iconn/deliboy.png"><span>Rider</span></a></li>
        <li><a href="logout.php" style="color:red; font-weight:bold;"><img src="iconn/logout.png"><span>Logout</span></a></li>
    </ul>

    <!--<div class="profile">
        <div class="profile-info">
            <strong>
             //isset($user['name']) && $user['name'] !== '' ? htmlspecialchars($user['name']) : 'Admin'; ?>
            </strong>
        </div>
    </div>-->
</div>

<style>
/* ===== General ===== */
body {
    margin: 0;
    font-family: "Segoe UI", Arial, sans-serif;
    background: #fffaf5;
}

.customer_brand__logo {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            color: #ff6a00;
            border-radius: 50%;
            font-size: 1.2rem;
            
        }
        .customer_brand__name {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--white);
          
        }

        .customer_brand__name span {
            color: var(--dark);
        }
/* ===== Toggle Button ===== */
#sidebarToggle {
    position: fixed;
    top: 15px;
    left: 25px;
    font-size: 26px;
    background: #ff7f50;
    border: none;
    color: #fff;
    cursor: pointer;
    border-radius: 8px;
    padding: 6px 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    z-index: 1001;
    transition: all 0.3s ease;
}
#sidebarToggle:hover {
    background: #ff6a00;
}

/* ===== Sidebar ===== */
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 250px;
    height: 100%;
    background: linear-gradient(180deg, #ff9a3d, #ff6a00);
    color: white;
    overflow-y: auto;
    transition: width 0.3s ease, background 0.3s ease;
    padding: 20px 10px;
    box-sizing: border-box;
    box-shadow: 2px 0 12px rgba(0,0,0,0.15);
}

.sidebar.minimized {
    width: 80px;
    padding-top: 30px;
}

.sidebar.minimized .pac,
.sidebar.minimized ul li a span {
    display: none;
}

/* Sidebar Header */
.sidebar-header {
    text-align: center;
    font-weight: bold;
    font-size: 18px;
    margin: 100px 0 20px 0; /* Top 25px, Bottom 20px */
    color: #fff;
    letter-spacing: 1px;
}

/* ===== Profile Card ===== */
.profile {
    display: flex;
    align-items: center;
    backdrop-filter: blur(8px);
    margin: 10px;
    border-radius: 15px;
    margin: 55px 0 20px 0; /* Top 25px, Bottom 20px */
    padding: 12px;
    transition: all 0.3s ease;
}
.profile:hover {
    background: rgba(255,255,255,0.25);
}
.profile img {
    width: 42px;
    border-radius: 50%;
    margin-right: 10px;
    border: 2px solid #fff;
}
.profile-info strong {
    color: #fff;
    font-size: 14px;
}

/* ===== Sidebar Menu ===== */
.sidebar ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.sidebar ul li {
    margin: 10px 0;
}
.sidebar ul li a {
    color: #fff; /* text white */
    text-decoration: none;
    font-weight: 500;
    display: flex;
    align-items: center;
    padding: 10px 14px;
    border-radius: 10px;
    transition: all 0.3s ease;
    font-size: 14px;
}
.sidebar ul li a[href="logout.php"] img {
    filter: invert(100%) brightness(200%); /* white icon before click */
}

.sidebar ul li a img {
    width: 26px;
    height: 26px;
    margin-right: 12px;
    transition: all 0.3s ease;
    /* For black icon → white */
    filter: invert(100%) brightness(200%);
}

.sidebar ul li a.active img {
    /* Active color (orange) */
    filter: invert(43%) sepia(85%) saturate(5098%) hue-rotate(10deg) brightness(101%) contrast(104%);
}

.sidebar ul li a:hover {
    background: rgba(255,255,255,0.2);
    transform: translateX(4px);
}
.sidebar ul li a:hover img {
    transform: scale(1.1);
}

/* Active Link */
.sidebar ul li a.active {
    background: #fff;
    color: #ff6a00;
    font-weight: bold;
    box-shadow: 0 3px 8px rgba(0,0,0,0.15);
}
.sidebar ul li a.active img {
    filter: brightness(0) saturate(100%) invert(43%) sepia(85%) saturate(5098%) hue-rotate(10deg) brightness(101%) contrast(104%);
}


/* ===== Main Content ===== */
.main-content {
    margin-left: 250px;
    padding: 20px;
    transition: margin-left 0.3s;
}
.sidebar.minimized ~ .main-content {
    margin-left: 80px;
}

/* ===== Scrollbar Hidden but Scrollable ===== */
.sidebar::-webkit-scrollbar {
    width: 0;
    background: transparent;
}
.sidebar {
    -ms-overflow-style: none;  
    scrollbar-width: none;
}
</style>

<script>
const sidebar = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');

// Load state from localStorage
if (localStorage.getItem('sidebarMinimized') === 'true') {
    sidebar.classList.add('minimized');
}

// Toggle and save state
toggleBtn.addEventListener('click', () => {
    sidebar.classList.toggle('minimized');
    localStorage.setItem('sidebarMinimized', sidebar.classList.contains('minimized'));
});
</script>
