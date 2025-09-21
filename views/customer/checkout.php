<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Fetch user to prefill details
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Cart check
$cart = $_SESSION['cart'] ?? null;
if (!$cart || empty($cart['items'])) {
    echo "<p>Your cart is empty. <a href='dashboard.php'>Go back</a></p>";
    exit;
}

// Get restaurant_id safely from cart
$restaurant_id = $cart['restaurant_id'] ?? null;
if (!$restaurant_id) {
    $firstItem = reset($cart['items']);
    if ($firstItem && isset($firstItem['restaurant_id'])) {
        $restaurant_id = (int)$firstItem['restaurant_id'];
    }
}
if (!$restaurant_id) {
    die("Invalid restaurant selected.");
}

// Fetch restaurant for lat/lng
$stmt = $pdo->prepare("SELECT restaurant_id, name, lat, lng FROM restaurants WHERE restaurant_id = ?");
$stmt->execute([$restaurant_id]);
$restaurant = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$restaurant) {
    die("Invalid restaurant selected.");
}

$restaurant_lat = $restaurant['lat'] ?? 16.8409;
$restaurant_lng = $restaurant['lng'] ?? 96.1735;

// subtotal
$subtotal = 0;
foreach ($cart['items'] as $it) {
    $subtotal += $it['price'] * $it['qty'];
}

$default_delivery_fee = 1500;
$total_default = $subtotal + $default_delivery_fee;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Checkout - Food&Me</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
   
</head>
<body>
<div class="checkout-container">
    <div class="checkout-left">
        <h2>Review and Place Your Order</h2>
        <form id="checkoutForm" action="place_order.php" method="POST">
            <input type="hidden" name="restaurant_id" value="<?= (int)$restaurant_id ?>">

            <?php
            // Split name for the edit form fields
            $name_parts = explode(' ', $user['name'] ?? ' ', 2);
            $first_name = $name_parts[0];
            $last_name = $name_parts[1] ?? '';
            ?>

            <input type="hidden" name="customer_name" id="hidden_customer_name" value="<?= htmlspecialchars($user['name'] ?? '') ?>">
            <input type="hidden" name="customer_email" id="hidden_customer_email" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
            <input type="hidden" name="customer_phone" id="hidden_customer_phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">

            <div class="card" id="personalDetailsView">
                <div class="card-header">
                    <strong>Personal details</strong>
                    <a id="editBtn">Edit</a>
                </div>
                <div class="card-body">
                    <p id="view_name"><?= htmlspecialchars($user['name'] ?? '') ?></p>
                    <p id="view_email"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                    <p id="view_phone"><?= htmlspecialchars($user['phone'] ?? '') ?></p>
                </div>
            </div>

            <div class="card" id="personalDetailsEdit" style="display:none;">
                <div class="card-header">
                    <strong>Personal details</strong>
                    <a id="cancelBtn">Cancel</a>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="email_edit">Email</label>
                        <input type="email" id="email_edit" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name_edit">First name</label>
                            <input type="text" id="first_name_edit" class="form-control" value="<?= htmlspecialchars($first_name) ?>">
                        </div>
                        <div class="form-group">
                            <label for="last_name_edit">Last name</label>
                            <input type="text" id="last_name_edit" class="form-control" value="<?= htmlspecialchars($last_name) ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="mobile_edit">Mobile number</label>
                        <input type="tel" id="mobile_edit" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                    </div>
                    <button type="button" id="saveBtn" class="save-button">Save</button>
                </div>
            </div>
            <label for="delivery_address" style="margin-top:20px;"><strong>Delivery Address</strong></label><br>
            <textarea name="delivery_address" id="delivery_address" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>

            <input type="hidden" name="lat" id="lat">
            <input type="hidden" name="lng" id="lng">

            <div class="map-and-search">
                <div id="map"></div>
                <div class="search-panel">
                    <div style="font-weight:600; margin-bottom:6px;">Search location</div>
                    <div class="controls">
                        <input id="searchQuery" class="search-input" placeholder="Search address or place">
                        <button type="button" id="searchBtn" class="use-loc-btn">Search</button>
                    </div>
                    <button type="button" id="useLocationBtn" class="use-loc-btn" style="width:100%;">Use my current location</button>
                    <div style="margin-top:10px; font-weight:600;">Results</div>
                    <div id="results" style="max-height:200px; overflow:auto; margin-top:6px;"></div>
                    <div style="margin-top:12px; font-weight:600;">Tips</div>
                    <small>Drag the pin on the map to fine-tune location. The delivery fee will update automatically.</small>
                </div>
            </div>

            <div>
                <strong style="margin-top: 20px;">Payment Method</strong><br>
                <div class="pay">
                <label><input type="radio" name="payment_method" value="cod" required> Cash on Delivery</label>
                <label><input type="radio" name="payment_method" value="kpay" > KPay <img src="../../assets/images/kpay.jpg" alt="KPay" class="payment-icons"></label>
                <label><input type="radio" name="payment_method" value="wavepay"> WavePay <img src="../../assets/images/wavepay.jpg" alt="WavePay" class="payment-icons"></label>
                </div>
            </div>

            <div style="margin-top:12px;">
                <button type="submit" class="checkout-btn">Place Order</button>
            </div>
        </form>
    </div>

    <aside class="checkout-right">
        <h3>Your Order</h3>
        <div class="cart-box">
            <ul class="cart-list">
                <?php foreach ($cart['items'] as $it):
                    $line = $it['price'] * $it['qty'];
                ?>
                <li>
                    <?= (int)$it['qty'] ?> x <?= htmlspecialchars($it['name']) ?> — <strong><?= number_format($line,0) ?> MMK</strong>
                </li>
                <?php endforeach; ?>
            </ul>
            <div class="cart-summary" style="margin-top:12px;">
                <div><span>Subtotal</span> <strong id="subtotal"><?= number_format($subtotal,0) ?> MMK</strong></div>
                <div style="margin-top:6px;"><span>Delivery Fee</span> <strong id="deliveryFee"><?= number_format($default_delivery_fee,0) ?> MMK</strong></div>
                <div style="margin-top:8px; font-size:1.1em;"><span>Total</span> <strong id="totalAmount" style="color:rgb(255, 120, 0);font-weight:bold;"><?= number_format($total_default,0) ?> MMK</strong></div>
            </div>
        </div>
    </aside>
</div>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
// START: Added JavaScript for Personal Details functionality
document.addEventListener('DOMContentLoaded', function() {
    const editBtn = document.getElementById('editBtn');
    const saveBtn = document.getElementById('saveBtn');
    const cancelBtn = document.getElementById('cancelBtn');

    const viewDiv = document.getElementById('personalDetailsView');
    const editDiv = document.getElementById('personalDetailsEdit');

    editBtn.addEventListener('click', function(e) {
        e.preventDefault();
        viewDiv.style.display = 'none';
        editDiv.style.display = 'block';
    });

    cancelBtn.addEventListener('click', function(e) {
        e.preventDefault();
        // No need to reset form fields, just hide the edit view
        editDiv.style.display = 'none';
        viewDiv.style.display = 'block';
    });

    saveBtn.addEventListener('click', function() {
        // Get new values from the edit form
        const newFirstName = document.getElementById('first_name_edit').value;
        const newLastName = document.getElementById('last_name_edit').value;
        const newFullName = (newFirstName + ' ' + newLastName).trim();
        const newEmail = document.getElementById('email_edit').value;
        const newPhone = document.getElementById('mobile_edit').value;

        // Update the text in the read-only view
        document.getElementById('view_name').textContent = newFullName;
        document.getElementById('view_email').textContent = newEmail;
        document.getElementById('view_phone').textContent = newPhone;

        // Update the hidden input fields that will be submitted with the order
        document.getElementById('hidden_customer_name').value = newFullName;
        document.getElementById('hidden_customer_email').value = newEmail;
        document.getElementById('hidden_customer_phone').value = newPhone;

        // Switch back to the read-only view
        editDiv.style.display = 'none';
        viewDiv.style.display = 'block';
    });
});
// END: Added JavaScript

const restaurantLat = <?= json_encode((float)$restaurant_lat) ?>;
const restaurantLng = <?= json_encode((float)$restaurant_lng) ?>;
const subtotal = <?= json_encode((float)$subtotal) ?>;
const defaultFee = <?= json_encode((int)$default_delivery_fee) ?>;

let map = L.map('map').setView([restaurantLat, restaurantLng], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

let marker = null;
function setMarker(lat, lng, draggable=true) {
    document.getElementById('lat').value = lat;
    document.getElementById('lng').value = lng;
    if (!marker) {
        marker = L.marker([lat, lng], { draggable }).addTo(map);
        marker.on('dragend', function() {
            const p = marker.getLatLng();
            reverseGeocodeAndUpdate(p.lat, p.lng);
            updateDeliveryFee(p.lat, p.lng);
        });
    } else {
        marker.setLatLng([lat, lng]);
    }
    map.setView([lat, lng], 16);
}

function reverseGeocodeAndUpdate(lat, lng) {
    fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`)
        .then(r => r.json())
        .then(data => {
            if (data && data.display_name) {
                document.getElementById('delivery_address').value = data.display_name;
            }
        }).catch(()=>{});
}

function haversine(lat1, lon1, lat2, lon2) {
    const R = 6371;
    const dLat = (lat2-lat1) * Math.PI/180;
    const dLon = (lon2-lon1) * Math.PI/180;
    const a = Math.sin(dLat/2)*Math.sin(dLat/2) + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLon/2)*Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

function updateDeliveryFee(userLat, userLng) {
    const distKm = haversine(restaurantLat, restaurantLng, userLat, userLng);
    const fee = Math.max(1500, Math.round(distKm * 500)); // Adjusted fee calculation slightly
    document.getElementById('deliveryFee').innerText = fee.toLocaleString() + " MMK";
    document.getElementById('totalAmount').innerText = (subtotal + fee).toLocaleString() + " MMK";
}

document.getElementById('useLocationBtn').addEventListener('click', () => {
    navigator.geolocation.getCurrentPosition(pos => {
        const lat = pos.coords.latitude, lng = pos.coords.longitude;
        setMarker(lat, lng);
        reverseGeocodeAndUpdate(lat, lng);
        updateDeliveryFee(lat, lng);
    }, err => {
        alert('Unable to get your location.');
    }, { enableHighAccuracy: true });
});

if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(pos => {
        setMarker(pos.coords.latitude, pos.coords.longitude);
        reverseGeocodeAndUpdate(pos.coords.latitude, pos.coords.longitude);
        updateDeliveryFee(pos.coords.latitude, pos.coords.longitude);
    }, () => {
        setMarker(restaurantLat, restaurantLng);
        // Do not update address or fee if location fails, keep defaults
    }, { enableHighAccuracy: true });
} else {
    setMarker(restaurantLat, restaurantLng);
}

const searchBtn = document.getElementById('searchBtn');
const searchQuery = document.getElementById('searchQuery');
const resultsDiv = document.getElementById('results');

function renderResults(list) {
    resultsDiv.innerHTML = '';
    if (!Array.isArray(list) || list.length === 0) {
        resultsDiv.innerHTML = '<div style="padding:8px;color:#666">No results</div>';
        return;
    }
    list.forEach(r => {
        const div = document.createElement('div');
        div.className = 'result-item';
        div.innerHTML = `<div style="font-weight:600;">${r.display_name.split(',')[0]}</div><small>${r.display_name}</small>`;
        div.addEventListener('click', () => {
            const lat = parseFloat(r.lat), lon = parseFloat(r.lon);
            setMarker(lat, lon);
            document.getElementById('delivery_address').value = r.display_name;
            updateDeliveryFee(lat, lon);
            resultsDiv.innerHTML = '';
        });
        resultsDiv.appendChild(div);
    });
}

function doSearch(q) {
    if (!q || q.trim().length === 0) return;
    const url = `https://nominatim.openstreetmap.org/search?format=jsonv2&q=${encodeURIComponent(q)}&limit=6&countrycodes=MM`; // Limited search to Myanmar
    fetch(url)
        .then(r => r.json())
        .then(list => renderResults(list))
        .catch(() => {
            resultsDiv.innerHTML = '<div style="padding:8px;color:#666">Search failed</div>';
        });
}

searchBtn.addEventListener('click', () => doSearch(searchQuery.value));
searchQuery.addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {
        e.preventDefault();
        doSearch(searchQuery.value);
    }
});
</script>
</body>
</html>