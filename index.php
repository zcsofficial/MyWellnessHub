<?php
// Start session to handle user login state
session_start();

// Include database config
require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyWellness Hub - Track Your Health Journey</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4ABDAC',
                        secondary: '#FCBB6D',
                        lavender: '#A2A8D3',
                        slate: '#606C88'
                    },
                    borderRadius: {
                        'button': '8px'
                    }
                }
            }
        }
    </script>
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, rgba(74, 189, 172, 0.1) 0%, rgba(162, 168, 211, 0.1) 100%);
        }
        .feature-card {
            transition: all 0.3s ease;
        }
        .feature-card:hover {
            transform: translateY(-5px);
        }
        .sidebar {
            transition: transform 0.3s ease-in-out;
        }
        .sidebar-hidden {
            transform: translateX(-100%);
        }
        .sidebar-open {
            transform: translateX(0);
        }
        @media (min-width: 768px) {
            .sidebar {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 16rem; /* 256px = w-64 */
            }
        }
        @media (max-width: 767px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body class="bg-white font-['Inter'] min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <aside id="sidebar" class="w-64 h-screen bg-white border-r border-gray-200 fixed sidebar sidebar-hidden md:sidebar-open z-50">
            <div class="p-4 md:p-6">
                <a href="index.php" class="font-['Pacifico'] text-xl md:text-2xl text-primary">MyWellness Hub</a>
            </div>
            <nav class="mt-4 md:mt-6">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="dashboard.php" class="flex items-center px-4 md:px-6 py-2 md:py-3 text-slate hover:bg-gray-50">
                        <div class="w-5 h-5 flex items-center justify-center mr-2 md:mr-3">
                            <i class="ri-dashboard-line"></i>
                        </div>
                        Dashboard
                    </a>
                    <a href="reports.php" class="flex items-center px-4 md:px-6 py-2 md:py-3 text-slate hover:bg-gray-50">
                        <div class="w-5 h-5 flex items-center justify-center mr-2 md:mr-3">
                            <i class="ri-bar-chart-line"></i>
                        </div>
                        Reports
                    </a>
                    <a href="settings.php" class="flex items-center px-4 md:px-6 py-2 md:py-3 text-slate hover:bg-gray-50">
                        <div class="w-5 h-5 flex items-center justify-center mr-2 md:mr-3">
                            <i class="ri-settings-3-line"></i>
                        </div>
                        Settings
                    </a>
                    <a href="logout.php" class="flex items-center px-4 md:px-6 py-2 md:py-3 text-slate hover:bg-gray-50">
                        <div class="w-5 h-5 flex items-center justify-center mr-2 md:mr-3">
                            <i class="ri-logout-box-line"></i>
                        </div>
                        Logout
                    </a>
                <?php else: ?>
                    <a href="index.php" class="flex items-center px-4 md:px-6 py-2 md:py-3 text-slate bg-primary/10">
                        <div class="w-5 h-5 flex items-center justify-center mr-2 md:mr-3">
                            <i class="ri-home-line"></i>
                        </div>
                        Home
                    </a>
                    <a href="login.php" class="flex items-center px-4 md:px-6 py-2 md:py-3 text-slate hover:bg-gray-50">
                        <div class="w-5 h-5 flex items-center justify-center mr-2 md:mr-3">
                            <i class="ri-login-box-line"></i>
                        </div>
                        Sign In
                    </a>
                    <a href="register.php" class="flex items-center px-4 md:px-6 py-2 md:py-3 text-slate hover:bg-gray-50">
                        <div class="w-5 h-5 flex items-center justify-center mr-2 md:mr-3">
                            <i class="ri-user-add-line"></i>
                        </div>
                        Register
                    </a>
                <?php endif; ?>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 main-content">
            <!-- Hamburger Menu for Mobile -->
            <div class="md:hidden flex justify-between items-center p-4 bg-white border-b border-gray-200">
                <button id="hamburger-btn" class="text-slate focus:outline-none">
                    <i class="ri-menu-line text-xl"></i>
                </button>
                <a href="index.php" class="font-['Pacifico'] text-xl text-primary">MyWellness Hub</a>
            </div>

            <!-- Hero Section -->
            <section class="pt-12 md:pt-16 lg:pt-24 pb-8 md:pb-12 lg:pb-16 relative overflow-hidden">
                <div class="absolute inset-0 z-0">
                    <img src="https://public.readdy.ai/ai/img_res/af810a3de3867177ef88aaad638d956d.jpg" class="w-full h-full object-cover opacity-10" alt="Background">
                </div>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 md:gap-8 lg:gap-10 items-center">
                        <div class="text-center lg:text-left">
                            <h1 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-slate mb-4 md:mb-6">Transform Your Health Journey with Smart Tracking</h1>
                            <p class="text-sm sm:text-base md:text-lg text-slate/80 mb-6 md:mb-8">Connect your wearables, track your activities, and get personalized insights to achieve your wellness goals.</p>
                            <div class="flex flex-col sm:flex-row gap-3 md:gap-4 justify-center lg:justify-start">
                                <a href="register.php" class="px-4 sm:px-6 md:px-8 py-2 md:py-3 bg-primary text-white rounded-button hover:bg-primary/90 transition-colors text-sm sm:text-base md:text-lg">Start Free Trial</a>
                                <button class="px-4 sm:px-6 md:px-8 py-2 md:py-3 border-2 border-secondary text-secondary rounded-button hover:bg-secondary/10 transition-colors text-sm sm:text-base md:text-lg">Watch Demo</button>
                            </div>
                        </div>
                        <div class="mt-6 lg:mt-0">
                            <img src="https://public.readdy.ai/ai/img_res/fc0e7e0b1a40090a5e14fe800a929d04.jpg" class="w-full max-w-xs sm:max-w-sm md:max-w-md mx-auto rounded-xl shadow-2xl" alt="Dashboard Preview">
                        </div>
                    </div>
                </div>
            </section>

            <!-- Features Section -->
            <section id="features" class="py-8 md:py-12 lg:py-20 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-8 md:mb-12 lg:mb-16">
                        <h2 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold text-slate mb-3 md:mb-4">Comprehensive Health Tracking</h2>
                        <p class="text-sm sm:text-base md:text-lg text-slate/80 max-w-2xl mx-auto">Everything you need to monitor and improve your wellness journey, all in one place.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 md:gap-8">
                        <div class="feature-card p-4 md:p-6 rounded-lg border border-lavender/20 bg-white shadow-sm">
                            <div class="w-10 h-10 md:w-12 md:h-12 flex items-center justify-center bg-primary/10 rounded-full mb-3 md:mb-4 mx-auto">
                                <i class="ri-heart-pulse-line text-primary text-xl md:text-2xl"></i>
                            </div>
                            <h3 class="text-base md:text-lg lg:text-xl font-semibold text-slate mb-2 text-center">Activity Tracking</h3>
                            <p class="text-slate/80 text-sm md:text-base text-center">Monitor steps, distance, calories burned, and active minutes.</p>
                        </div>
                        <div class="feature-card p-4 md:p-6 rounded-lg border border-lavender/20 bg-white shadow-sm">
                            <div class="w-10 h-10 md:w-12 md:h-12 flex items-center justify-center bg-primary/10 rounded-full mb-3 md:mb-4 mx-auto">
                                <i class="ri-pulse-line text-primary text-xl md:text-2xl"></i>
                            </div>
                            <h3 class="text-base md:text-lg lg:text-xl font-semibold text-slate mb-2 text-center">Health Metrics</h3>
                            <p class="text-slate/80 text-sm md:text-base text-center">Track vital signs, sleep, and stress levels.</p>
                        </div>
                        <div class="feature-card p-4 md:p-6 rounded-lg border border-lavender/20 bg-white shadow-sm">
                            <div class="w-10 h-10 md:w-12 md:h-12 flex items-center justify-center bg-primary/10 rounded-full mb-3 md:mb-4 mx-auto">
                                <i class="ri-mental-health-line text-primary text-xl md:text-2xl"></i>
                            </div>
                            <h3 class="text-base md:text-lg lg:text-xl font-semibold text-slate mb-2 text-center">Smart Insights</h3>
                            <p class="text-slate/80 text-sm md:text-base text-center">Personalized recommendations for your goals.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Integrations Section -->
            <section id="integrations" class="py-8 md:py-12 lg:py-20 gradient-bg">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-8 md:mb-12 lg:mb-16">
                        <h2 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold text-slate mb-3 md:mb-4">Connect Your Favorite Devices</h2>
                        <p class="text-sm sm:text-base md:text-lg text-slate/80 max-w-2xl mx-auto">Seamlessly integrate with popular fitness trackers.</p>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6 md:gap-8">
                        <div class="flex flex-col items-center p-3 md:p-4 lg:p-6 bg-white rounded-lg shadow-sm">
                            <i class="ri-google-fill text-2xl md:text-3xl lg:text-4xl text-slate mb-2 md:mb-4"></i>
                            <span class="text-slate font-medium text-xs sm:text-sm md:text-base">Google Fit</span>
                        </div>
                        <div class="flex flex-col items-center p-3 md:p-4 lg:p-6 bg-white rounded-lg shadow-sm">
                            <i class="ri-apple-fill text-2xl md:text-3xl lg:text-4xl text-slate mb-2 md:mb-4"></i>
                            <span class="text-slate font-medium text-xs sm:text-sm md:text-base">Apple Health</span>
                        </div>
                        <div class="flex flex-col items-center p-3 md:p-4 lg:p-6 bg-white rounded-lg shadow-sm">
                            <i class="ri-fitbit-fill text-2xl md:text-3xl lg:text-4xl text-slate mb-2 md:mb-4"></i>
                            <span class="text-slate font-medium text-xs sm:text-sm md:text-base">Fitbit</span>
                        </div>
                        <div class="flex flex-col items-center p-3 md:p-4 lg:p-6 bg-white rounded-lg shadow-sm">
                            <i class="ri-watch-line text-2xl md:text-3xl lg:text-4xl text-slate mb-2 md:mb-4"></i>
                            <span class="text-slate font-medium text-xs sm:text-sm md:text-base">Garmin</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Benefits Section -->
            <section id="benefits" class="py-8 md:py-12 lg:py-20 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="text-center mb-8 md:mb-12 lg:mb-16">
                        <h2 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold text-slate mb-3 md:mb-4">Why Choose MyWellness Hub?</h2>
                        <p class="text-sm sm:text-base md:text-lg text-slate/80 max-w-2xl mx-auto">Comprehensive tracking and personalized insights.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 md:gap-8">
                        <div class="text-center p-4 md:p-6">
                            <div class="w-12 h-12 md:w-14 md:h-14 lg:w-16 lg:h-16 mx-auto flex items-center justify-center bg-lavender/10 rounded-full mb-3 md:mb-4">
                                <i class="ri-shield-check-line text-primary text-xl md:text-2xl lg:text-3xl"></i>
                            </div>
                            <h3 class="text-base md:text-lg lg:text-xl font-semibold text-slate mb-2">Data Privacy</h3>
                            <p class="text-slate/80 text-sm md:text-base">Encrypted and secure health data.</p>
                        </div>
                        <div class="text-center p-4 md:p-6">
                            <div class="w-12 h-12 md:w-14 md:h-14 lg:w-16 lg:h-16 mx-auto flex items-center justify-center bg-lavender/10 rounded-full mb-3 md:mb-4">
                                <i class="ri-customer-service-2-line text-primary text-xl md:text-2xl lg:text-3xl"></i>
                            </div>
                            <h3 class="text-base md:text-lg lg:text-xl font-semibold text-slate mb-2">24/7 Support</h3>
                            <p class="text-slate/80 text-sm md:text-base">Help whenever you need it.</p>
                        </div>
                        <div class="text-center p-4 md:p-6">
                            <div class="w-12 h-12 md:w-14 md:h-14 lg:w-16 lg:h-16 mx-auto flex items-center justify-center bg-lavender/10 rounded-full mb-3 md:mb-4">
                                <i class="ri-line-chart-line text-primary text-xl md:text-2xl lg:text-3xl"></i>
                            </div>
                            <h3 class="text-base md:text-lg lg:text-xl font-semibold text-slate mb-2">Progress Tracking</h3>
                            <p class="text-slate/80 text-sm md:text-base">Visual insights to stay motivated.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="py-8 md:py-12 lg:py-20 bg-gradient-to-r from-primary/10 to-lavender/10">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="max-w-2xl mx-auto text-center">
                        <h2 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold text-slate mb-4 md:mb-6">Start Your Wellness Journey Today</h2>
                        <p class="text-sm sm:text-base md:text-lg text-slate/80 mb-6 md:mb-8">Join thousands transforming their health.</p>
                        <a href="register.php" class="px-4 sm:px-6 md:px-8 py-2 md:py-3 bg-secondary text-white rounded-button hover:bg-secondary/90 transition-colors text-sm sm:text-base md:text-lg">Get Started Free</a>
                    </div>
                </div>
            </section>

            <!-- Footer -->
            <footer class="bg-slate py-6 md:py-8 lg:py-12">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 md:gap-8">
                        <div>
                            <a href="index.php" class="text-xl md:text-2xl font-['Pacifico'] text-white mb-3 md:mb-4 block">MyWellness Hub</a>
                            <p class="text-lavender/80 text-xs sm:text-sm md:text-base">Your complete wellness tracking solution.</p>
                        </div>
                        <div>
                            <h4 class="text-white font-semibold mb-3 md:mb-4 text-sm md:text-base lg:text-lg">Product</h4>
                            <ul class="space-y-2 text-xs sm:text-sm md:text-base">
                                <li><a href="#features" class="text-lavender/80 hover:text-white transition-colors">Features</a></li>
                                <li><a href="#integrations" class="text-lavender/80 hover:text-white transition-colors">Integrations</a></li>
                                <li><a href="#" class="text-lavender/80 hover:text-white transition-colors">Pricing</a></li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="text-white font-semibold mb-3 md:mb-4 text-sm md:text-base lg:text-lg">Company</h4>
                            <ul class="space-y-2 text-xs sm:text-sm md:text-base">
                                <li><a href="#" class="text-lavender/80 hover:text-white transition-colors">About</a></li>
                                <li><a href="#" class="text-lavender/80 hover:text-white transition-colors">Blog</a></li>
                                <li><a href="#" class="text-lavender/80 hover:text-white transition-colors">Careers</a></li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="text-white font-semibold mb-3 md:mb-4 text-sm md:text-base lg:text-lg">Connect</h4>
                            <div class="flex space-x-3 md:space-x-4">
                                <a href="#" class="text-lavender hover:text-white transition-colors"><i class="ri-twitter-fill text-lg md:text-xl"></i></a>
                                <a href="#" class="text-lavender hover:text-white transition-colors"><i class="ri-facebook-fill text-lg md:text-xl"></i></a>
                                <a href="#" class="text-lavender hover:text-white transition-colors"><i class="ri-instagram-fill text-lg md:text-xl"></i></a>
                            </div>
                        </div>
                    </div>
                    <div class="mt-6 md:mt-8 lg:mt-12 pt-4 md:pt-6 border-t border-white/10 text-center">
                        <p class="text-lavender/80 text-xs sm:text-sm">© 2025 MyWellness Hub. All rights reserved.</p>
                    </div>
                </div>
            </footer>
        </main>
    </div>

    <!-- Smooth Scrolling and Hamburger Menu Script -->
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Hamburger Menu Toggle
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const sidebar = document.getElementById('sidebar');
        let isSidebarOpen = false;

        hamburgerBtn.addEventListener('click', () => {
            isSidebarOpen = !isSidebarOpen;
            if (isSidebarOpen) {
                sidebar.classList.remove('sidebar-hidden');
                sidebar.classList.add('sidebar-open');
            } else {
                sidebar.classList.remove('sidebar-open');
                sidebar.classList.add('sidebar-hidden');
            }
        });

        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', (e) => {
            if (isSidebarOpen && !sidebar.contains(e.target) && !hamburgerBtn.contains(e.target)) {
                isSidebarOpen = false;
                sidebar.classList.remove('sidebar-open');
                sidebar.classList.add('sidebar-hidden');
            }
        });
    </script>
</body>
</html>