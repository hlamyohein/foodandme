<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

$restaurant_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($restaurant_id === 0) {
    header("Location: dashboard.php");
    exit;
}

// restaurant details with address and logo
$stmt = $pdo->prepare("SELECT * FROM restaurants WHERE restaurant_id = ?");
$stmt->execute([$restaurant_id]);
$restaurant = $stmt->fetch();

// menu with options
$stmt = $pdo->prepare("
    SELECT mi.* 
    FROM menu_items mi
    WHERE mi.restaurant_id=? AND mi.is_available=1 
    ORDER BY mi.category, mi.name
");
$stmt->execute([$restaurant_id]);
$menu = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all options for each menu item
$menu_with_options = [];
foreach ($menu as $item) {
    $item_id = $item['item_id'];
    $menu_with_options[$item_id] = $item;
    
    // Get options for this menu item
    $stmt = $pdo->prepare("
        SELECT mo.* 
        FROM menu_options mo
        JOIN menu_item_options mio ON mo.option_id = mio.option_id
        WHERE mio.item_id = ?
        ORDER BY mo.option_id
    ");
    $stmt->execute([$item_id]);
    $options = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each option, get its values
    foreach ($options as &$option) {
        $stmt = $pdo->prepare("
            SELECT ovo.value_id, ovo.value_name, ovo.price_modifier
            FROM option_values ovo
            WHERE ovo.option_id = ?
            ORDER BY ovo.value_id
        ");
        $stmt->execute([$option['option_id']]);
        $option['values'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $menu_with_options[$item_id]['options'] = $options;
}

// categories
$stmt = $pdo->prepare("SELECT DISTINCT category FROM menu_items WHERE restaurant_id=? AND is_available=1");
$stmt->execute([$restaurant_id]);
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($restaurant['name'] ?? 'Menu') ?> - Food&Me</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* Restaurant header styles */
    .restaurant-header {
      background: white;
      padding: 15px 20px;
      margin-bottom: 10px;
      transition: transform 0.3s ease;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .restaurant-info {
      display: flex;
      gap: 15px;
      align-items: center;
      margin-left: 20%;
    }
    
    .restaurant-image {
      width: 10%;
      height: 10%;
      border-radius: 10px;
      object-fit: cover;
      box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .restaurant-details {
      flex: 1;
      margin-left: 20px;
    }
    
    .restaurant-name {
      margin: 0 0 5px 0;
      color: #333;
      font-size: 20px;
    }
    
    .restaurant-address {
      color: #666;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 14px;
    }
    
    .restaurant-rating {
      display: flex;
      align-items: center;
      gap: 5px;
      color: #ff6600;
      font-weight: bold;
      margin: 5px 0;
      font-size: 14px;
    }
    
    .restaurant-meta {
      display: flex;
      gap: 15px;
      margin-top: 8px;
      color: #666;
      font-size: 13px;
    }
    
    .meta-item {
      display: flex;
      align-items: center;
      gap: 3px;
    }
    
    /* Header when scrolled */
    .restaurant-header.hidden {
      transform: translateY(-100%);
    }
    
    
    .menu-filter-bar {
      position: sticky;
      top: 60px; 
      z-index: 999;
      background: #f7f7f7; 
      padding: 10px 20px; 
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      align-items: center;
      width: 100%;       
      box-sizing: border-box; 
      margin: 0;     
      
    }

    .category-tabs {
      display: flex;
      gap: 8px;
      flex: 1; 
      overflow-x: auto;
      position: sticky;
      
    }

    .category-tab {
      padding: 6px 12px;
      border-radius: 20px;
      border: 1px solid #ddd;
      cursor: pointer;
      white-space: nowrap;
    }

    .category-tab.active {
      background: rgb(255, 102, 0);
      color: #fff;
      border-color: rgb(255, 102, 0);
    }

    .search-box {
      flex-shrink: 0;
    }

    .search-box input {
      padding: 8px 12px;
      border-radius: 20px;
      border: 1px solid #ddd;
      min-width: 220px; 
    }
    
    .back-btn{
      color: white;
      text-decoration: none;
      font-size: 18px;
      font-weight: bold;
      color: #fff;
      border: none;
      padding: 8px 12px;
      border-radius: 5px;
      margin-right: 1
    }
    
    .back-btn:hover{
      color: black;
    }
    
    /* Modal styles for options */
    .option-group {
      margin: 15px 0;
      border-bottom: 1px solid #eee;
      padding-bottom: 15px;
    }
    
    .option-title {
      font-weight: bold;
      margin-bottom: 8px;
      display: block;
    }
    
    .option-required {
      color: #ff0000;
    }
    
    .option-item {
      margin: 8px 0;
      display: flex;
      align-items: center;
    }
    
    .option-item input[type="radio"],
    .option-item input[type="checkbox"] {
      margin-right: 10px;
    }
    
    .option-price {
      margin-left: auto;
      color: #666;
    }
    
    .option-error {
      color: red;
      font-size: 14px;
      margin-top: 5px;
      display: none;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
      .restaurant-info {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .restaurant-image {
        width: 60px;
        height: 60px;
      }
      
      .menu-filter-bar {
        margin: 10px 15px;
      }
      
      .search-box input {
        min-width: 150px;
      }
    }
  </style>
</head>
<body>
<!-- Sticky header with back button -->
<header class="sticky-header">
  <a href="dashboard.php" class="back-btn">⬅ Back to Restaurants</a>
  <h2><?= htmlspecialchars($restaurant['name'] ?? 'Menu') ?></h2>
  <div></div> 
</header>

<!-- Restaurant summary header -->
<?php
// ---- START: dynamic restaurant header (paste in place of old static header) ----

// assumes $pdo and $restaurant are already available from earlier code
$rest_id = (int)($restaurant['restaurant_id'] ?? 0);

// get avg rating & count
$avg_rating = null;
$rating_count = 0;
if ($rest_id) {
    $stmt = $pdo->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM reviews WHERE restaurant_id = ?");
    $stmt->execute([$rest_id]);
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($r && $r['avg_rating'] !== null) {
        $avg_rating = round((float)$r['avg_rating'], 1);
        $rating_count = (int)$r['cnt'];
    }
}

// compute simple price tier using avg menu price
$avg_price = null;
if ($rest_id) {
    $stmt = $pdo->prepare("SELECT AVG(price) AS avg_price FROM menu_items WHERE restaurant_id = ?");
    $stmt->execute([$rest_id]);
    $p = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($p && $p['avg_price'] !== null) $avg_price = (float)$p['avg_price'];
}
$price_tier = '';
if ($avg_price !== null) {
    if ($avg_price < 3000) $price_tier = '$';
    elseif ($avg_price < 8000) $price_tier = '$$';
    else $price_tier = '$$$';
}

// delivery defaults (tweak to taste)
$base_fee = 500;         // MMK
$per_km_fee = 200;       // MMK per km after base_covered_km
$base_covered_km = 1.5;  // km
$surge = 1.0;
$avg_speed_kmh = 25;

// restaurant coords & prep time
$rest_lat = $restaurant['lat'] ?? $restaurant['latitude'] ?? null;
$rest_lng = $restaurant['lng'] ?? $restaurant['longitude'] ?? null;
$prep_time = (int)($restaurant['preparation_time'] ?? 15);

// if session has customer coords, compute server-side distance/fee/eta as initial values
$userLat = $_SESSION['lat'] ?? null;
$userLng = $_SESSION['lng'] ?? null;

// haversine in PHP
function haversine_km($lat1, $lon1, $lat2, $lon2) {
    if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) return null;
    $R = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    return $R * $c;
}

$server_distance_km = null;
$server_fee = null;
$server_eta_min = null;

if ($userLat !== null && $userLng !== null && $rest_lat !== null && $rest_lng !== null) {
    $server_distance_km = round(haversine_km($userLat, $userLng, $rest_lat, $rest_lng), 2);
    $extra_km = max(0, $server_distance_km - $base_covered_km);
    $server_fee = (int)ceil(($base_fee + ($per_km_fee * $extra_km)) * $surge);
    $travel_min = ($server_distance_km / max(0.1, $avg_speed_kmh)) * 60;
    $server_eta_min = (int)ceil($prep_time + $travel_min);
}

// helper displays
$rating_html = $avg_rating ? ($avg_rating . ($rating_count ? " ({$rating_count})" : "")) : '—';
$fee_display = $server_fee === 0 ? 'Free delivery' : ($server_fee ? number_format($server_fee) . ' MMK' : 'Fee varies');
$eta_display = $server_eta_min ? ($server_eta_min . ' min') : '30-40 min';

$rest_logo = htmlspecialchars($restaurant['logo'] ?? 'default-restaurant.jpg');
$rest_name = htmlspecialchars($restaurant['name'] ?? 'Restaurant');
$rest_address = htmlspecialchars($restaurant['address'] ?? 'Address not available');
$rest_cuisine = htmlspecialchars($restaurant['cuisine_type'] ?? '');
?>
<!-- dynamic restaurant header -->
<div class="restaurant-header" id="restaurantHeader"
     data-lat="<?= htmlspecialchars($rest_lat) ?>"
     data-lng="<?= htmlspecialchars($rest_lng) ?>"
     data-prep="<?= htmlspecialchars($prep_time) ?>"
     data-base-fee="<?= htmlspecialchars($base_fee) ?>"
     data-per-km="<?= htmlspecialchars($per_km_fee) ?>"
     data-base-covered-km="<?= htmlspecialchars($base_covered_km) ?>"
     data-surge="<?= htmlspecialchars($surge) ?>"
     data-avg-speed="<?= htmlspecialchars($avg_speed_kmh) ?>">
  <div class="restaurant-info">
    <img src="../../assets/images/<?= $rest_logo ?>" 
         alt="<?= $rest_name ?>" class="restaurant-image">
    <div class="restaurant-details">
      
      <p class="restaurant-address">
        <i class="fas fa-map-marker-alt"></i>
        <?= $rest_address ?>
      </p>
      <div class="restaurant-rating">
        <i class="fas fa-star"></i>
        <span id="ratingText"><?= $rating_html ?> <?= $rest_cuisine ? "• {$rest_cuisine}" : "" ?> <?= $price_tier ? "• {$price_tier}" : "" ?></span>
      </div>
      <div class="restaurant-meta">
        <div class="meta-item">
          <i class="fas fa-clock"></i>
          <span id="etaDisplay"><?= htmlspecialchars($eta_display) ?></span>
        </div>
        <div class="meta-item">
          <i class="fas fa-tag"></i>
          <span id="feeDisplay"><?= htmlspecialchars($fee_display) ?></span>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// client-side: enhance header using browser geolocation (overrides the server-side values if available)
(function(){
  const header = document.getElementById('restaurantHeader');
  if (!header) return;

  const restLat = parseFloat(header.dataset.lat);
  const restLng = parseFloat(header.dataset.lng);
  const prep = parseFloat(header.dataset.prep) || 15;
  const baseFee = parseFloat(header.dataset.baseFee) || 500;
  const perKm = parseFloat(header.dataset.perKm) || parseFloat(header.dataset['perKm']) || parseFloat(header.dataset['per-km']) || 200;
  const baseCoveredKm = parseFloat(header.dataset.baseCoveredKm) || 1.5;
  const surge = parseFloat(header.dataset.surge) || 1.0;
  const avgSpeed = parseFloat(header.dataset.avgSpeed) || 25;

  const etaEl = document.getElementById('etaDisplay');
  const feeEl = document.getElementById('feeDisplay');

  function haversine(lat1, lon1, lat2, lon2){
    const R=6371;
    const dLat = (lat2-lat1)*Math.PI/180;
    const dLon = (lon2-lon1)*Math.PI/180;
    const a = Math.sin(dLat/2)**2 + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLon/2)**2;
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
  }

  function formatPrice(n){ try { return Number(n).toLocaleString() + ' MMK'; } catch(e) { return n + ' MMK'; } }
  function formatTimeRange(mins){
    mins = Math.max(1, Math.round(mins));
    const low = Math.max(5, Math.floor(mins / 5) * 5);
    const high = low + 10;
    return `${low}-${high} min`;
  }

  function updateForCoordinates(userLat, userLng){
    if (isNaN(restLat) || isNaN(restLng) || isNaN(userLat) || isNaN(userLng)) {
      feeEl.textContent = 'Fee varies';
      etaEl.textContent = '30-40 min';
      return;
    }
    const dist = haversine(userLat, userLng, restLat, restLng);
    const extraKm = Math.max(0, dist - baseCoveredKm);
    const fee = Math.ceil((baseFee + (perKm * extraKm)) * surge);
    const travelMin = (dist / Math.max(0.1, avgSpeed)) * 60;
    const totalMin = prep + travelMin;

    feeEl.textContent = fee === 0 ? 'Free delivery' : formatPrice(fee);
    etaEl.textContent = formatTimeRange(totalMin);
  }

  function fallback() {
    // if user denied or no geolocation, keep server-side values (already present)
    // but ensure UI has safe fallback text
    if (!etaEl.textContent) etaEl.textContent = '30-40 min';
    if (!feeEl.textContent) feeEl.textContent = 'Fee varies';
  }

  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      pos => updateForCoordinates(pos.coords.latitude, pos.coords.longitude),
      err => {
        console.warn('geolocation failed', err);
        fallback();
      },
      { enableHighAccuracy: true, timeout: 8000 }
    );
  } else {
    fallback();
  }
})();
</script>
<!-- ---- END dynamic header ---- -->


<div class="page-container">
    <main class="menu-container">
        <!-- Category Tabs -->
        <div class="menu-filter-bar">
          <div class="category-tabs" id="categoryTabs">
              <div class="category-tab active" data-category="all">All</div>
              <?php foreach ($categories as $cat): ?>
                <div class="category-tab" data-category="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></div>
              <?php endforeach; ?>
          </div>

          <!-- Search Box -->
          <div class="search-box">
            <input type="text" id="menuSearch" placeholder="Search for restaurants and cuisines...">
          </div>
        </div>
        
        <!-- Menu Grid -->
        <div class="menu-grid" id="menuGrid">
          <?php foreach ($menu_with_options as $item): ?>
            <div class="menu-card" 
                 data-category="<?= htmlspecialchars($item['category']) ?>" 
                 data-name="<?= htmlspecialchars(strtolower($item['name'])) ?>">

              <div class="menu-img">
                <img src="../../assets/images/<?= htmlspecialchars($item['image'] ?? 'default.png') ?>" 
                     alt="<?= htmlspecialchars($item['name']) ?>">
              </div>

              <div class="menu-info">
                <h3><?= htmlspecialchars($item['name']) ?></h3>
                <p><?= htmlspecialchars($item['description']) ?></p>
                <strong><?= number_format($item['price']) ?> MMK</strong>
                <button onclick="openAddToCartModal(<?= $item['item_id'] ?>, '<?= htmlspecialchars($item['name'], ENT_QUOTES) ?>', <?= $item['price'] ?>, <?= htmlspecialchars(json_encode($item['options']), ENT_QUOTES) ?>)">Add To Cart</button>
              </div>

            </div>
          <?php endforeach; ?>
        </div>
    </main>

    <!-- Cart container -->
    <aside id="cart-container" class="cart-container"></aside>
</div>

<!-- NEW MODAL with options -->
<div id="addToCartModal" class="modal-backdrop" aria-hidden="true">
  <div class="modal-content" role="dialog" aria-modal="true" aria-labelledby="modalItemName">
    <button onclick="closeModal()" 
        class="close-modal-btn" 
        type="button"
        style="position:absolute; right:15px; 
               width:50px; height:50px;  top:0px;
               background:url('../../assets/images/cancel.jpg') no-repeat center center; 
               background-size:contain; 
               border:none; cursor:pointer;">
    </button>

    <h3 id="modalItemName">Item Name</h3>
    
    <div id="modalOptionsContainer"></div>
    
    <div class="modal-qty-control">
      <button class="qty-btn" id="modalDecrementBtn" type="button">-</button>
      <span class="qty" id="modalQuantity">1</span>
      <button class="qty-btn" id="modalIncrementBtn" type="button">+</button>
    </div>
    
    <div id="optionError" class="option-error">Please select all required options</div>
    
    <button id="modalAddToCartBtn" class="checkout-btn" type="button">
      Add to Cart - <span id="modalTotalPrice">0</span> MMK
    </button>
  </div>
</div>

<script src="../../assets/js/cart.js"></script>

<!-- Scroll handling for restaurant header -->
<script>
// Scroll handling for restaurant header
const restaurantHeader = document.getElementById('restaurantHeader');
let lastScrollTop = 0;

window.addEventListener('scroll', function() {
  const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
  
  if (scrollTop > lastScrollTop && scrollTop > 100) {
    // Scrolling down - hide header
    restaurantHeader.classList.add('hidden');
  } else {
    // Scrolling up - show header
    restaurantHeader.classList.remove('hidden');
  }
  
  lastScrollTop = scrollTop;
});
</script>

<!-- Filtering Logic (replace existing block with this) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
  const search = document.getElementById('menuSearch');
  const cards = document.querySelectorAll('#menuGrid .menu-card');
  const tabs = document.querySelectorAll('.category-tab');

  const normalize = s => (s || '').toString().toLowerCase().trim();

  function filter() {
    const query = normalize(search ? search.value : '');
    const activeEl = document.querySelector('.category-tab.active');
    const activeTab = normalize(activeEl ? activeEl.dataset.category : 'all');

    cards.forEach(card => {
      const name = normalize(card.dataset.name);
      const category = normalize(card.dataset.category);

      const matchCategory = (activeTab === 'all' || category === activeTab);
      const matchSearch = (!query || name.includes(query));

      // use '' to restore original CSS display when visible
      card.style.display = (matchCategory && matchSearch) ? '' : 'none';
    });
  }

  if (search) search.addEventListener('input', filter);

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      tab.classList.add('active');
      filter();
    });
  });

  // apply initial filter (ensures default "All" or active tab is applied on load)
  filter();
});
</script>

<!-- Modal functionality -->
<script>
// Global variables for options
let currentItemOptions = [];
let selectedOptions = {};

function openAddToCartModal(id, name, price, options = []) {
    currentItemId = id;
    currentItemPrice = Number(price) || 0;
    currentQuantity = 1;
    currentItemOptions = Array.isArray(options) ? options : [];
    selectedOptions = {};

    if (modalItemName) modalItemName.textContent = name;
    
    // Render options
    renderOptions();
    
    updateModalDisplay();
    if (modal) modal.classList.add('visible');
}

function renderOptions() {
    const container = document.getElementById('modalOptionsContainer');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (currentItemOptions.length === 0) {
        return;
    }
    
    currentItemOptions.forEach(option => {
        const optionGroup = document.createElement('div');
        optionGroup.className = 'option-group';
        
        const title = document.createElement('span');
        title.className = 'option-title';
        title.textContent = option.option_name;
        if (option.is_required == 1) {
            title.innerHTML += ' <span class="option-required">*</span>';
        }
        
        optionGroup.appendChild(title);
        
        if (option.option_type === 'single_select') {
            renderRadioOptions(optionGroup, option);
        } else if (option.option_type === 'multi_select') {
            renderCheckboxOptions(optionGroup, option);
        }
        
        container.appendChild(optionGroup);
    });
}

function renderRadioOptions(container, option) {
    option.values.forEach(value => {
        const optionItem = document.createElement('div');
        optionItem.className = 'option-item';
        
        const radio = document.createElement('input');
        radio.type = 'radio';
        radio.name = `option_${option.option_id}`;
        radio.value = value.value_id;
        radio.id = `opt_${option.option_id}_${value.value_id}`;
        radio.addEventListener('change', () => {
            if (option.is_required == 1 || radio.checked) {
                selectedOptions[option.option_id] = radio.checked ? [value] : [];
                updateModalDisplay();
            }
        });
        
        const label = document.createElement('label');
        label.htmlFor = `opt_${option.option_id}_${value.value_id}`;
        label.textContent = value.value_name;
        
        const priceSpan = document.createElement('span');
        priceSpan.className = 'option-price';
        priceSpan.textContent = value.price_modifier > 0 ? `+${value.price_modifier} MMK` : '';
        
        optionItem.appendChild(radio);
        optionItem.appendChild(label);
        optionItem.appendChild(priceSpan);
        container.appendChild(optionItem);
    });
}

function renderCheckboxOptions(container, option) {
    option.values.forEach(value => {
        const optionItem = document.createElement('div');
        optionItem.className = 'option-item';
        
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.name = `option_${option.option_id}[]`;
        checkbox.value = value.value_id;
        checkbox.id = `opt_${option.option_id}_${value.value_id}`;
        checkbox.addEventListener('change', () => {
            if (!selectedOptions[option.option_id]) {
                selectedOptions[option.option_id] = [];
            }
            
            if (checkbox.checked) {
                selectedOptions[option.option_id].push(value);
            } else {
                selectedOptions[option.option_id] = selectedOptions[option.option_id].filter(
                    item => item.value_id != value.value_id
                );
            }
            
            updateModalDisplay();
        });
        
        const label = document.createElement('label');
        label.htmlFor = `opt_${option.option_id}_${value.value_id}`;
        label.textContent = value.value_name;
        
        const priceSpan = document.createElement('span');
        priceSpan.className = 'option-price';
        priceSpan.textContent = value.price_modifier > 0 ? `+${value.price_modifier} MMK` : '';
        
        optionItem.appendChild(checkbox);
        optionItem.appendChild(label);
        optionItem.appendChild(priceSpan);
        container.appendChild(optionItem);
    });
}

function validateOptions() {
    let isValid = true;
    
    currentItemOptions.forEach(option => {
        if (option.is_required == 1) {
            if (!selectedOptions[option.option_id] || selectedOptions[option.option_id].length === 0) {
                isValid = false;
            }
        }
    });
    
    return isValid;
}

function calculateOptionPrice() {
    let optionTotal = 0;
    
    Object.values(selectedOptions).forEach(optionArray => {
        optionArray.forEach(option => {
            optionTotal += Number(option.price_modifier) || 0;
        });
    });
    
    return optionTotal;
}

function updateModalDisplay() {
    if (modalQuantity) modalQuantity.textContent = currentQuantity;
    
    const optionPrice = calculateOptionPrice();
    const totalPrice = (currentItemPrice + optionPrice) * currentQuantity;
    
    if (modalTotalPrice) modalTotalPrice.textContent = totalPrice.toLocaleString();
    if (modalDecrementBtn) modalDecrementBtn.disabled = currentQuantity <= 1;
    
    // Hide error if all required options are selected
    const errorElement = document.getElementById('optionError');
    if (errorElement) {
        errorElement.style.display = validateOptions() ? 'none' : 'block';
    }
}

// Update the modalAddToCartBtn onclick event
if (modalAddToCartBtn) {
    modalAddToCartBtn.onclick = () => {
        if (!currentItemId) return;
        
        if (!validateOptions()) {
            document.getElementById('optionError').style.display = 'block';
            return;
        }
        
        updateCart('add_with_qty', currentItemId, currentQuantity, selectedOptions);
        closeModal();
    };
}
</script>

<!-- Floating cart button -->
<button id="floating-cart-btn" title="Open cart" aria-label="Open cart" onclick="toggleCart()" type="button">
  <svg id="cart-svg" width="24" height="24" viewBox="0 0 24 24">
    <path fill="currentColor" d="M7 18c-1.104 0-2 .896-2 2s.896 2 2 2 2-.896 2-2-.896-2-2-2zm10 0c-1.104 0-2 .896-2 2s.896 2 2 2 2-.896 2-2-.896-2-2-2zM7.16 14l.84-2h8.99c.54 0 1.02-.35 1.17-.86l1.98-6.14a1 1 0 0 0-.96-1.3H5.21l-.94-2.5A1 1 0 0 0 3.33 1H1v2h1.89l3.6 9.6c.15.4.53.66.95.66h9.53v-2H8.53l-.12-.26L7.16 14z"/>
  </svg>
  <span id="cart-badge">0</span>
</button>

</body>
</html>