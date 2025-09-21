<?php
session_start();
define('IS_VALID_ENTRY_POINT', true);
require_once '../../config/db.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

function getUser($pdo, $user_id) {
    $stmt = $pdo->prepare("SELECT name, address FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

$user = getUser($pdo, $user_id);

// Handle page routing
$page = $_GET['page'] ?? 'restaurants';

// Get filters
$q = trim($_GET['q'] ?? '');
$selectedCuisine = trim($_GET['cuisine'] ?? '');

// Fetch distinct cuisines
$cuisines = $pdo->query("
    SELECT DISTINCT cuisine_type 
    FROM restaurants 
    WHERE status='active' 
      AND cuisine_type IS NOT NULL 
      AND cuisine_type <> '' 
    ORDER BY cuisine_type ASC
")->fetchAll(PDO::FETCH_COLUMN);

// Fetch restaurants
if ($q !== '') {
    $like = '%' . $q . '%';
    $sql = "
        SELECT DISTINCT r.*
        FROM restaurants r
        LEFT JOIN menu_items m ON m.restaurant_id = r.restaurant_id
        WHERE r.status = 'active'
          AND (
            r.name LIKE :like
            OR r.cuisine_type LIKE :like
            OR m.name LIKE :like
          )
    ";
    if ($selectedCuisine !== '') {
        $sql .= " AND r.cuisine_type = :cuisine";
    }
    $sql .= " ORDER BY r.name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':like', $like, PDO::PARAM_STR);
    if ($selectedCuisine !== '') {
        $stmt->bindValue(':cuisine', $selectedCuisine, PDO::PARAM_STR);
    }
    $stmt->execute();
    $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    if ($selectedCuisine !== '') {
        $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE status='active' AND cuisine_type = ? ORDER BY name ASC");
        $stmt->execute([$selectedCuisine]);
        $restaurants = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $restaurants = $pdo->query("SELECT * FROM restaurants WHERE status='active' ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Customer Dashboard</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body>

<header class="res-sticky-dash">
   <div class="container header-inner">
        <a href="/foodandme/index.php" class="brand">
            <div class="customer_brand__logo">
                <i class="fas fa-utensils"></i>
            </div>
            <span class="customer_brand__name">Food<span>&amp;</span>Me</span>
        </a>

        <!-- SEARCH FORM -->
        <div class="search-row" aria-label="Search restaurants and cuisines">
            <form class="search-form" action="dashboard.php" method="get" role="search" autocomplete="off">
                <input type="hidden" name="page" value="<?= htmlspecialchars($page) ?>">
                <?php if ($selectedCuisine !== ''): ?>
                    <input type="hidden" name="cuisine" value="<?= htmlspecialchars($selectedCuisine) ?>">
                <?php endif; ?>
                <input
                    type="search"
                    name="q"
                    class="search-input"
                    placeholder="Search restaurants & cuisines as you like..........."
                    value="<?= htmlspecialchars($q) ?>"
                >
                <button type="submit" class="search-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
            <div class="search-suggestions" style="display:none"></div>
        </div>

        <!-- USER MENU -->
        <div class="user-menu">
            <div class="user-menu-toggle" id="userMenuToggle">
                <span><?= htmlspecialchars(explode(' ', $user['name'] ?? 'User')[0]) ?></span>
                <i class="fa-solid fa-chevron-down"></i>
            </div>
            <div class="dropdown-content" id="dropdownContent">
                <ul>
                    <li><a href="dashboard.php?page=profile"><i class="fa-regular fa-user"></i> Profile</a></li>
                    <li><a href="dashboard.php?page=orders"><i class="fa-solid fa-receipt"></i> Orders & reordering</a></li>
                    <li><a href="dashboard.php?page=help"><i class="fa-regular fa-circle-question"></i> Help Center</a></li>
                    <?php if ($page !== 'restaurants'): ?>
                        <li><a href="dashboard.php?page=restaurants"><i class="fa-solid fa-arrow-left"></i> Back To Restaurants</a></li>
                    <?php endif; ?>
                    <li class="divider"><a href="../../logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</header>

<main class="container">
<?php
if ($q !== '') {
    echo '<p>Showing results for <strong>' . htmlspecialchars($q) . '</strong>. <a href="dashboard.php">Clear</a></p>';
}

switch ($page) {
    case 'profile':
        include 'profile.php';
        break;
    case 'orders':
        include 'orders.php';
        break;
    case 'help':
        include 'help_center.php';
        break;
    default:
        echo '<div style="color: #ff8000;"><h2>Enjoy Our Partners Restaurants</h2></div>';

        // Cuisine filter
        if ($cuisines) {
            echo '<div class="cuisine-filter">';
            echo '<a href="dashboard.php?page=restaurants" class="cuisine-btn reset">All</a>';
            foreach ($cuisines as $cuisine) {
                $active = ($cuisine === $selectedCuisine) ? 'active' : '';
                echo '<a href="dashboard.php?page=restaurants&cuisine=' . urlencode($cuisine) . '" class="cuisine-btn ' . $active . '">' . htmlspecialchars($cuisine) . '</a>';
            }
            
            echo '</div>';
            
        }

        echo '<div class="restaurant-list">';
        if (count($restaurants) === 0) {
            echo '<p>No restaurants found.</p>';
        } else {
            foreach ($restaurants as $res) {
                $logo = htmlspecialchars($res['logo'] ?? 'default_restaurant.png');
                $lat = htmlspecialchars($res['lat'] ?? 0);
                $lng = htmlspecialchars($res['lng'] ?? 0);
                $prep = htmlspecialchars($res['preparation_time'] ?? 15);

                echo '<a href="restaurants.php?id=' . $res['restaurant_id'] . '" 
                        class="restaurant-card-link restaurant-card-dynamic" 
                        data-lat="' . $lat . '" 
                        data-lng="' . $lng . '"
                        data-prep-time="' . $prep . '">';

                echo '  <div class="restaurant-card">';
                echo '    <div class="card-image" style="background-image: url(\'../../assets/images/' . $logo . '\');"></div>';
                echo '    <div class="card-content">';
                echo '      <h3 class="card-title">' . htmlspecialchars($res['name']) . '</h3>';
                echo '      <p class="card-cuisine">' . htmlspecialchars($res['cuisine_type']) . '</p>';
                echo '      <div class="card-details">';
                echo '        <span class="detail-item"><i class="fa-solid fa-clock"></i> <span class="dynamic-time">...</span></span>';
                echo '        <span class="detail-item"><i class="fa-solid fa-motorcycle"></i> <span class="dynamic-fee">...</span></span>';
                echo '      </div>';
                echo '    </div>';
                echo '  </div>';
                echo '</a>';
            }
        }
        echo '</div>';
        break;
}
?>
</main>

<script src="../../assets/js/dashboard-live-search.js"></script>
<script>
// user menu dropdown
document.addEventListener('DOMContentLoaded', function () {
    const userMenuToggle = document.getElementById('userMenuToggle');
    const dropdownContent = document.getElementById('dropdownContent');
    const userMenu = document.querySelector('.user-menu');

    userMenuToggle.addEventListener('click', function (event) {
        event.stopPropagation();
        dropdownContent.classList.toggle('show');
        userMenu.classList.toggle('open');
    });

    window.addEventListener('click', function (event) {
        if (!userMenu.contains(event.target)) {
            dropdownContent.classList.remove('show');
            userMenu.classList.remove('open');
        }
    });

    // haversine + delivery time
    function haversine(lat1, lon1, lat2, lon2) {
        const R = 6371;
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat / 2) ** 2 +
                  Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                  Math.sin(dLon / 2) ** 2;
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        return R * c;
    }

    function formatTimeRange(minutes) {
        const lower = Math.floor(minutes / 5) * 5;
        const upper = lower + 10;
        return `${lower}-${upper} min`;
    }

    function updateAllCardDetails(userLat, userLng) {
        const restaurantCards = document.querySelectorAll('.restaurant-card-dynamic');
        const averageSpeedKmh = 20;

        restaurantCards.forEach(card => {
            const resLat = parseFloat(card.dataset.lat);
            const resLng = parseFloat(card.dataset.lng);
            const prepTime = parseInt(card.dataset.prepTime);
            const feeSpan = card.querySelector('.dynamic-fee');
            const timeSpan = card.querySelector('.dynamic-time');

            if (!isNaN(resLat) && !isNaN(resLng) && feeSpan && timeSpan) {
                const baseFee = 500;
                const perKmRate = 500;
                const maxBaseDistance = 5;
                const distKm = haversine(resLat, resLng, userLat, userLng);
                const fee = (distKm <= maxBaseDistance) ? baseFee : Math.round(distKm * perKmRate);
                feeSpan.innerText = "K " + fee.toLocaleString();

                const travelTimeMinutes = (distKm / averageSpeedKmh) * 60;
                const totalTimeMinutes = prepTime + travelTimeMinutes;
                timeSpan.innerText = formatTimeRange(totalTimeMinutes);
            }
        });
    }

    function handleLocationError() {
        document.querySelectorAll('.dynamic-fee').forEach(span => span.innerText = "Fee varies");
        document.querySelectorAll('.dynamic-time').forEach(span => span.innerText = "30-40 min");
    }

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            pos => updateAllCardDetails(pos.coords.latitude, pos.coords.longitude),
            handleLocationError,
            { enableHighAccuracy: true }
        );
    } else {
        handleLocationError();
    }
});
</script>
</body>
</html>
