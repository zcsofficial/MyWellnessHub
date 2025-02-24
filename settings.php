<?php
// Start session
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include database configuration
require_once 'config.php';

// Initialize variables
$user_id = $_SESSION['user_id'];
$errors = [];
$success = '';

// Fetch user data with prepared statement
$user_query = "SELECT username, email, weight, email_notifications FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);
$username = $user['username'];
$email = $user['email'];
$weight = $user['weight'] ?? '';
$email_notifications = $user['email_notifications'] ?? 0;
mysqli_stmt_close($stmt);

// Google Fit OAuth settings
$google_client_id = 'YOUR_GOOGLE_CLIENT_ID'; // Replace with your Google Client ID
$google_client_secret = 'YOUR_GOOGLE_CLIENT_SECRET'; // Replace with your Google Client Secret
$google_redirect_uri = 'http://localhost/wellness_hub/settings.php'; // Adjust to your domain
$google_auth_url = "https://accounts.google.com/o/oauth2/v2/auth?client_id=$google_client_id&redirect_uri=" . urlencode($google_redirect_uri) . "&response_type=code&scope=https://www.googleapis.com/auth/fitness.activity.read";

// Fitbit OAuth settings
$fitbit_client_id = 'YOUR_FITBIT_CLIENT_ID'; // Replace with your Fitbit Client ID
$fitbit_client_secret = 'YOUR_FITBIT_CLIENT_SECRET'; // Replace with your Fitbit Client Secret
$fitbit_redirect_uri = 'http://localhost/wellness_hub/settings.php'; // Adjust to your domain
$fitbit_auth_url = "https://www.fitbit.com/oauth2/authorize?client_id=$fitbit_client_id&redirect_uri=" . urlencode($fitbit_redirect_uri) . "&response_type=code&scope=activity";

// Handle Google OAuth callback
if (isset($_GET['code']) && !isset($_GET['fitbit'])) {
    $code = $_GET['code'];
    $token_url = "https://oauth2.googleapis.com/token";
    $post_data = [
        'code' => $code,
        'client_id' => $google_client_id,
        'client_secret' => $google_client_secret,
        'redirect_uri' => $google_redirect_uri,
        'grant_type' => 'authorization_code'
    ];
    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $token_data = json_decode($response, true);
    if (isset($token_data['access_token'])) {
        $success = "Google Fit connected successfully!";
        // Store token in database (uncomment and adjust as needed)
        // $query = "UPDATE users SET google_fit_token = ? WHERE user_id = ?";
        // $stmt = mysqli_prepare($conn, $query);
        // mysqli_stmt_bind_param($stmt, "si", $token_data['access_token'], $user_id);
        // mysqli_stmt_execute($stmt);
        // mysqli_stmt_close($stmt);
    } else {
        $errors[] = "Failed to connect Google Fit: " . ($token_data['error_description'] ?? 'Unknown error');
    }
}

// Handle Fitbit OAuth callback
if (isset($_GET['code']) && isset($_GET['fitbit'])) {
    $code = $_GET['code'];
    $token_url = "https://api.fitbit.com/oauth2/token";
    $post_data = [
        'code' => $code,
        'client_id' => $fitbit_client_id,
        'client_secret' => $fitbit_client_secret,
        'redirect_uri' => $fitbit_redirect_uri,
        'grant_type' => 'authorization_code'
    ];
    $ch = curl_init($token_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . base64_encode($fitbit_client_id . ':' . $fitbit_client_secret)]);
    $response = curl_exec($ch);
    curl_close($ch);
    $token_data = json_decode($response, true);
    if (isset($token_data['access_token'])) {
        $success = "Fitbit connected successfully!";
        // Store token in database (uncomment and adjust as needed)
        // $query = "UPDATE users SET fitbit_token = ? WHERE user_id = ?";
        // $stmt = mysqli_prepare($conn, $query);
        // mysqli_stmt_bind_param($stmt, "si", $token_data['access_token'], $user_id);
        // mysqli_stmt_execute($stmt);
        // mysqli_stmt_close($stmt);
    } else {
        $errors[] = "Failed to connect Fitbit: " . ($token_data['error_description'] ?? 'Unknown error');
    }
}

// Handle form submissions with prepared statements
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $new_weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : NULL;
        if ($new_weight !== NULL && ($new_weight < 1 || $new_weight > 500)) {
            $errors[] = "Weight must be between 1 and 500 kg.";
        } else {
            $query = "UPDATE users SET weight = ? WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "di", $new_weight, $user_id);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Profile updated successfully!";
                $weight = $new_weight;
            } else {
                $errors[] = "Failed to update profile: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    } elseif (isset($_POST['update_notifications'])) {
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $query = "UPDATE users SET email_notifications = ? WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ii", $email_notifications, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $success = "Notification settings updated!";
        } else {
            $errors[] = "Failed to update notifications: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    }
}

// Close connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - MyWellness Hub</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
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
<body class="bg-white min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <aside id="sidebar" class="w-64 h-screen bg-white border-r border-gray-200 fixed sidebar sidebar-hidden md:sidebar-open z-50">
            <div class="p-4 md:p-6">
                <a href="index.php" class="font-['Pacifico'] text-xl md:text-2xl text-primary">MyWellness Hub</a>
            </div>
            <nav class="mt-4 md:mt-6">
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
                <a href="settings.php" class="flex items-center px-4 md:px-6 py-2 md:py-3 text-slate bg-primary/10">
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
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 main-content">
            <!-- Hamburger Menu for Mobile -->
            <div class="md:hidden flex justify-between items-center p-4 bg-white border-b border-gray-200">
                <button id="hamburger-btn" class="text-slate focus:outline-none">
                    <i class="ri-menu-line text-xl"></i>
                </button>
                <div class="flex items-center space-x-2">
                    <img src="https://public.readdy.ai/ai/img_res/6d13389090137366a30fed5b15c5dfaf.jpg" 
                         class="w-6 h-6 rounded-full object-cover" alt="Profile">
                    <span class="text-slate text-xs"><?php echo htmlspecialchars($username); ?></span>
                </div>
            </div>

            <!-- Settings Content -->
            <div class="p-4 md:p-6 lg:p-8">
                <!-- Header -->
                <header class="flex flex-col md:flex-row justify-between items-center mb-4 md:mb-6 lg:mb-8">
                    <div class="text-center md:text-left">
                        <h1 class="text-lg md:text-xl lg:text-2xl font-semibold text-slate">Settings, <?php echo htmlspecialchars($username); ?>!</h1>
                        <p class="text-gray-500 text-xs md:text-sm lg:text-base"><?php echo date('l, F j, Y'); ?></p>
                    </div>
                    <div class="hidden md:flex items-center space-x-3 md:space-x-4 mt-3 md:mt-0">
                        <button class="rounded-button bg-white border border-gray-200 p-1 md:p-2">
                            <div class="w-5 h-5 md:w-6 md:h-6 flex items-center justify-center">
                                <i class="ri-notification-3-line text-slate text-base md:text-lg"></i>
                            </div>
                        </button>
                        <div class="flex items-center space-x-2">
                            <img src="https://public.readdy.ai/ai/img_res/6d13389090137366a30fed5b15c5dfaf.jpg" 
                                 class="w-6 h-6 md:w-8 md:h-8 lg:w-10 lg:h-10 rounded-full object-cover" alt="Profile">
                            <span class="text-slate text-xs md:text-sm lg:text-base"><?php echo htmlspecialchars($username); ?></span>
                        </div>
                    </div>
                </header>

                <!-- Messages -->
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 text-red-700 p-3 md:p-4 rounded mb-4 md:mb-6">
                        <ul class="list-disc list-inside text-xs md:text-sm">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <?php if (!empty($success)): ?>
                    <div class="bg-green-100 text-green-700 p-3 md:p-4 rounded mb-4 md:mb-6 text-xs md:text-sm"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <!-- Settings Sections -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
                    <!-- Wearable Connections -->
                    <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border border-gray-100">
                        <h2 class="text-sm md:text-base lg:text-lg font-semibold text-slate mb-3 md:mb-4">Connect Wearables</h2>
                        <div class="space-y-3 md:space-y-4">
                            <div class="flex justify-between items-center">
                                <span class="text-slate text-xs md:text-sm">Google Fit</span>
                                <a href="<?php echo $google_auth_url; ?>" class="rounded-button px-3 md:px-4 py-1 md:py-2 bg-primary text-white hover:bg-primary/90 text-xs md:text-sm">Connect</a>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate text-xs md:text-sm">Fitbit</span>
                                <a href="<?php echo $fitbit_auth_url; ?>&fitbit=1" class="rounded-button px-3 md:px-4 py-1 md:py-2 bg-primary text-white hover:bg-primary/90 text-xs md:text-sm">Connect</a>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-slate text-xs md:text-sm">Garmin</span>
                                <button class="rounded-button px-3 md:px-4 py-1 md:py-2 bg-gray-300 text-gray-600 text-xs md:text-sm cursor-not-allowed" disabled>Coming Soon</button>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Settings -->
                    <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border border-gray-100">
                        <h2 class="text-sm md:text-base lg:text-lg font-semibold text-slate mb-3 md:mb-4">Profile</h2>
                        <form method="POST" class="space-y-3 md:space-y-4">
                            <input type="hidden" name="update_profile" value="1">
                            <div>
                                <label class="block text-slate text-xs md:text-sm">Email</label>
                                <input type="email" value="<?php echo htmlspecialchars($email); ?>" class="w-full p-2 border border-lavender/20 rounded text-xs md:text-sm" disabled>
                            </div>
                            <div>
                                <label class="block text-slate text-xs md:text-sm">Weight (kg)</label>
                                <input type="number" name="weight" step="0.1" value="<?php echo $weight !== NULL ? $weight : ''; ?>" 
                                       class="w-full p-2 border border-lavender/20 rounded text-xs md:text-sm" placeholder="Enter your weight">
                            </div>
                            <button type="submit" class="w-full py-2 bg-primary text-white rounded-button hover:bg-primary/90 text-xs md:text-sm">Update Profile</button>
                        </form>
                    </div>

                    <!-- Notification Settings -->
                    <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border border-gray-100">
                        <h2 class="text-sm md:text-base lg:text-lg font-semibold text-slate mb-3 md:mb-4">Notifications</h2>
                        <form method="POST" class="space-y-3 md:space-y-4">
                            <input type="hidden" name="update_notifications" value="1">
                            <div class="flex items-center justify-between">
                                <label class="text-slate text-xs md:text-sm">Email Notifications</label>
                                <input type="checkbox" name="email_notifications" <?php echo $email_notifications ? 'checked' : ''; ?> 
                                       class="h-4 md:h-5 w-4 md:w-5 text-primary border-lavender/20 rounded">
                            </div>
                            <button type="submit" class="w-full py-2 bg-primary text-white rounded-button hover:bg-primary/90 text-xs md:text-sm">Save Notifications</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Hamburger Menu Script -->
    <script>
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