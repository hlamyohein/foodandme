<?php include  ("includes/header.php")?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food&Me - Delicious Food Delivery</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    
    <main>
        <section class="hero">
            <div class="container hero-inner">
                <div class="hero-content">
                    <h1>Need to order food? <span>Just Click</span></h1>
                    <p class="lead">Fast delivery from restaurants and stores you love. Order now and get it delivered hot & fresh.</p>
                    <p>
                        <a class="btn btn--primary" href="views/customer/restaurants.php">
                            <i class="fas fa-utensils"></i> Order now
                        </a>
                    </p>
                </div>
                <div class="hero-visual">
                    <div class="hero-image-container">
                        <img src="assets/images/jojo.jpg"
                             alt="Delicious chicken burger from Food&Me" class="hero-image active" data-index="0">
                        <img src="assets/images/kfc.jpg" 
                             alt="Spicy chicken burger from Food&Me" class="hero-image" data-index="1">
                        <img src="assets/images/ykkobg.jpg"
                             alt="Crispy chicken burger from Food&Me" class="hero-image" data-index="2">
                        <img src="assets/images/chapayom.jpg"
                             alt="Gourmet chicken burger from Food&Me" class="hero-image" data-index="3">
                        <img src="assets/images/sp.jpg"
                             alt="Premium chicken burger from Food&Me" class="hero-image" data-index="4">
                        
                        <div class="image-dots">
                            <div class="dot active" data-index="0"></div>
                            <div class="dot" data-index="1"></div>
                            <div class="dot" data-index="2"></div>
                            <div class="dot" data-index="3"></div>
                            <div class="dot" data-index="4"></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="features container">
            <div class="feature">
                <i class="fas fa-store"></i>
                <h3>Restaurants near you</h3>
                <p>Handpicked restaurants and popular menus.</p>
            </div>
            <div class="feature">
                <i class="fas fa-shipping-fast"></i>
                <h3>Fast delivery</h3>
                <p>Quick, reliable delivery to your door.</p>
            </div>
            <div class="feature">
                <i class="fas fa-shopping-basket"></i>
                <h3>Groceries & essentials</h3>
                <p>Get everyday items delivered fast.</p>
            </div>
        </section>

        <!-- Popular Categories Section -->
<section class="categories">
    <div class="container">
        <h2 class="section-title">Popular Categories</h2>
        <div class="categories-grid">
            <div class="category-card">
                <div class="category-card__image">
                    <img src="assets/images/burger.jpg" alt="Burgers">
                </div>
                <div class="category-card__content">
                    <h3 class="category-card__title">Burgers</h3>
                    <p class="category-card__count">24 restaurants</p>
                </div>
            </div>
            
            <div class="category-card">
                <div class="category-card__image">
                    <img src="assets/images/ham&cheese.jpg" alt="Pizza">
                </div>
                <div class="category-card__content">
                    <h3 class="category-card__title">Pizza</h3>
                    <p class="category-card__count">18 restaurants</p>
                </div>
            </div>
            
            <div class="category-card">
                <div class="category-card__image">
                    <img src="assets/images/chicken.jpg" alt="Asian">
                </div>
                <div class="category-card__content">
                    <h3 class="category-card__title">Chicken</h3>
                    <p class="category-card__count">32 restaurants</p>
                </div>
            </div>
            
            <div class="category-card">
                <div class="category-card__image">
                    <img src="assets/images/noodle.jpg" alt="Desserts">
                </div>
                <div class="category-card__content">
                    <h3 class="category-card__title">Noodle</h3>
                    <p class="category-card__count">15 restaurants</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="how-it-works">
    <div class="container">
        <h2 class="section-title">How It Works</h2>
        <div class="steps">
            <div class="step">
                <div class="step__number">1</div>
                <h3 class="step__title">Choose your food</h3>
                <p>Browse hundreds of restaurants and menus to find what you crave.</p>
            </div>
            
            <div class="step">
                <div class="step__number">2</div>
                <h3 class="step__title">Place your order</h3>
                <p>Add items to your cart and complete your order with a few clicks.</p>
            </div>
            
            <div class="step">
                <div class="step__number">3</div>
                <h3 class="step__title">Track delivery</h3>
                <p>Follow your order in real-time as we prepare and deliver it.</p>
            </div>
            
            <div class="step">
                <div class="step__number">4</div>
                <h3 class="step__title">Enjoy!</h3>
                <p>Sit back, relax, and enjoy your delicious meal.</p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials">
    <div class="container">
        <h2 class="section-title">What Our Customers Say</h2>
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div class="testimonial-card__content">
                    <p>The food arrived hot and delicious. The delivery was faster than expected. Will definitely order again!</p>
                </div>
                <div class="testimonial-card__author">
                    
                    <div class="testimonial-card__info">
                        <h4>Sarah Johnson</h4>
                        <p>Regular Customer</p>
                        <div class="testimonial-card__rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-card">
                <div class="testimonial-card__content">
                    <p>I love how easy it is to find new restaurants through Food&Me. The interface is intuitive and ordering is a breeze.</p>
                </div>
                <div class="testimonial-card__author">
                    
                    <div class="testimonial-card__info">
                        <h4>Michael Chen</h4>
                        <p>Food Enthusiast</p>
                        <div class="testimonial-card__rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="testimonial-card">
                <div class="testimonial-card__content">
                    <p>As someone with dietary restrictions, I appreciate the detailed menu descriptions and filters. Makes ordering safe and easy!</p>
                </div>
                <div class="testimonial-card__author">
                    
                    <div class="testimonial-card__info">
                        <h4>Emma Rodriguez</h4>
                        <p>Health Conscious</p>
                        <div class="testimonial-card__rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mobile App Section -->
<section class="mobile-app">
    <div class="container">
        <div class="mobile-app__content">
            <div class="mobile-app__text">
                <h2>Get the Food&Me App</h2>
                <p>Our Team is working hard to bring you the best experience possible.So stay tuned for mobile app version.</p>
                <div class="app-buttons">
                    <a href="#" class="app-button">
                        <i class="fab fa-apple"></i>
                        <div>
                            <span>Download on the</span>
                            <strong>App Store</strong>
                        </div>
                    </a>
                    <a href="#" class="app-button">
                        <i class="fab fa-google-play"></i>
                        <div>
                            <span>GET IT ON</span>
                            <strong>Google Play</strong>
                        </div>
                    </a>
                </div>
            </div>
            <div class="mobile-app__image">
                <img src="assets/images/app-screen.png" alt="Food&Me Mobile App">
            </div>
        </div>
    </div>
</section>
    </main>

   <?php include ("includes/footer.php")?>
    <script>

        const navToggle = document.getElementById('navToggle');
        const mainNav = document.getElementById('main-nav');
        
        navToggle.addEventListener('click', () => {
            navToggle.classList.toggle('active');
            mainNav.classList.toggle('active');
            navToggle.setAttribute('aria-expanded', 
                navToggle.getAttribute('aria-expanded') === 'false' ? 'true' : 'false');
        });
        
        // Close mobile nav when clicking on a link
        document.querySelectorAll('.main-nav__list a').forEach(link => {
            link.addEventListener('click', () => {
                navToggle.classList.remove('active');
                mainNav.classList.remove('active');
                navToggle.setAttribute('aria-expanded', 'false');
            });
        });
        
        // Location button functionality
        document.getElementById("choose-location").addEventListener("click", function() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    alert("Your Location:\nLatitude: " + position.coords.latitude +
                            "\nLongitude: " + position.coords.longitude);
                }, function(error) {
                    alert("Unable to retrieve your location. Please allow location access.");
                });
            } else {
                alert("Geolocation is not supported by your browser.");
            }
        });
        
        // Newsletter form validation
        document.querySelector('.newsletter-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input').value;
            if (email) {
                alert('Thank you for subscribing to our newsletter!');
                this.querySelector('input').value = '';
            }
        });
        
        
        const images = document.querySelectorAll('.hero-image');
        const dots = document.querySelectorAll('.dot');
        let currentIndex = 0;
        let slideInterval;
        
        function showImage(index) {
            // Hide all images
            images.forEach(img => img.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            // Show the selected image
            images[index].classList.add('active');
            dots[index].classList.add('active');
            
            currentIndex = index;
        }
        
        function nextImage() {
            let nextIndex = (currentIndex + 1) % images.length;
            showImage(nextIndex);
        }
        
        
        function startSlideshow() {
            slideInterval = setInterval(nextImage, 1000); 
        }
        
        
        dots.forEach(dot => {
            dot.addEventListener('click', function() {
                let index = parseInt(this.getAttribute('data-index'));
                showImage(index);
                // Reset the timer when manually changing image
                clearInterval(slideInterval);
                startSlideshow();
            });
        });
        
        // Start the slideshow when page loads
        startSlideshow();
        
        // Pause slideshow when hovering over image container
        const imageContainer = document.querySelector('.hero-image-container');
        imageContainer.addEventListener('mouseenter', () => {
            clearInterval(slideInterval);
        });
        
        imageContainer.addEventListener('mouseleave', () => {
            startSlideshow();
        });
    </script>
    <script>
        // Category card hover effect
document.querySelectorAll('.category-card').forEach(card => {
    card.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-5px)';
        this.style.boxShadow = '0 8px 20px rgba(0,0,0,0.1)';
    });
    
    card.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
        this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.05)';
    });
});

// Simple animation for how it works steps
function animateSteps() {
    const steps = document.querySelectorAll('.step');
    steps.forEach((step, index) => {
        setTimeout(() => {
            step.style.opacity = 1;
            step.style.transform = 'translateY(0)';
        }, index * 200);
    });
}

// Initialize step animations
document.addEventListener('DOMContentLoaded', function() {
    animateSteps();
});
    </script>
</body>
</html>