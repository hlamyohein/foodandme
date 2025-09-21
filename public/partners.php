<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Partner</title>
    <style>
        /* Modern & Clean Form Styling */
        body { font-family: 'Segoe UI', sans-serif; background-color: #f4f5f7; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .container { background: #fff; padding: 40px; border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        h1 { text-align: center; color: #1a1a1a; margin-bottom: 10px; }
        p.subtitle { text-align: center; color: #666; margin-top: 0; margin-bottom: 30px; }
        
        /* Role Switcher */
        .role-switcher { display: flex; border: 1px solid #ddd; border-radius: 25px; margin-bottom: 30px; overflow: hidden; }
        .role-switcher label { flex: 1; text-align: center; padding: 12px 0; cursor: pointer; transition: background-color 0.3s, color 0.3s; font-weight: 600; color: #555; }
        .role-switcher input { display: none; }
        .role-switcher input:checked + label { background-color: rgb(255,102,0); color: #fff; }

        /* Form Fields */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 8px; color: #333; }
        .form-group input { width: 100%; padding: 12px 15px; border: 1px solid #ccc; border-radius: 8px; box-sizing: border-box; transition: border-color 0.3s; }
        .form-group input:focus { border-color: rgb(255,102,0); outline: none; }
        
        button { width: 100%; padding: 15px; background-color: rgb(255,102,0); color: #fff; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background-color 0.3s; }
        button:hover { background-color: rgb(255,102,0); }

        #rider-fields, #form-response { display: none; }
        #form-response { margin-top: 20px; padding: 15px; border-radius: 8px; text-align: center; font-weight: bold; }
        .success { background-color: #e4f8f0; color: #2d6a4f; }
        .error { background-color: #fbe9e9; color: #9d2525; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Partner with Us</h1>
        <p class="subtitle">Join our network and start growing today.</p>
        
        <form id="partner-form">
            <div class="role-switcher">
                <input type="radio" id="role-vendor" name="role" value="vendor" checked>
                <label for="role-vendor">Become a Vendor</label>
                
                <input type="radio" id="role-rider" name="role" value="delivery">
                <label for="role-rider">Become a Rider</label>
            </div>

            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" required>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone" required>
            </div>
             <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div id="vendor-fields">
                <div class="form-group">
                    <label for="restaurant_name">Restaurant Name</label>
                    <input type="text" id="restaurant_name" name="restaurant_name">
                </div>
                <div class="form-group">
                    <label for="address">Restaurant Address</label>
                    <input type="text" id="address" name="address">
                </div>
                 <div class="form-group">
                    <label for="cuisine_type">Cuisine Type (e.g., Pizza, Burmese)</label>
                    <input type="text" id="cuisine_type" name="cuisine_type">
                </div>
            </div>

            <div id="rider-fields">
                 <div class="form-group">
                    <label for="rider_address">Your Address</label>
                    <input type="text" id="rider_address" name="rider_address">
                </div>
            </div>

            <button type="submit">Submit Application</button>
            <div id="form-response"></div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleVendorRadio = document.getElementById('role-vendor');
            const roleRiderRadio = document.getElementById('role-rider');
            const vendorFields = document.getElementById('vendor-fields');
            const riderFields = document.getElementById('rider-fields');
            const partnerForm = document.getElementById('partner-form');
            const formResponse = document.getElementById('form-response');

            function toggleFields() {
                if (roleVendorRadio.checked) {
                    vendorFields.style.display = 'block';
                    vendorFields.querySelectorAll('input').forEach(input => input.required = true);
                    riderFields.style.display = 'none';
                    riderFields.querySelectorAll('input').forEach(input => input.required = false);
                } else {
                    vendorFields.style.display = 'none';
                    vendorFields.querySelectorAll('input').forEach(input => input.required = false);
                    riderFields.style.display = 'block';
                    riderFields.querySelectorAll('input').forEach(input => input.required = true);
                }
            }
            toggleFields();
            roleVendorRadio.addEventListener('change', toggleFields);
            roleRiderRadio.addEventListener('change', toggleFields);

            partnerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const submitButton = this.querySelector('button');
                submitButton.textContent = 'Submitting...';
                submitButton.disabled = true;

                // Make sure this path is correct for your server setup
                fetch('/foodandme/api/submit_partner_request.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    formResponse.style.display = 'block';
                    formResponse.textContent = data.message;
                    if (data.success) {
                        formResponse.className = 'success';
                        partnerForm.reset();
                        toggleFields();
                    } else {
                        formResponse.className = 'error';
                    }
                })
                .catch(error => {
                    formResponse.style.display = 'block';
                    formResponse.className = 'error';
                    formResponse.textContent = 'An unexpected error occurred.';
                    console.error('Error:', error);
                })
                .finally(() => {
                    submitButton.textContent = 'Submit Application';
                    submitButton.disabled = false;
                });
            });
        });
    </script>
</body>
</html>