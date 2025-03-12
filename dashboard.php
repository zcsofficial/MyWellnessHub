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

// Fetch user data
$user_query = "SELECT username, weight FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);
$username = $user['username'];
$weight = $user['weight'] ?? 70; // Default to 70 kg if not set
mysqli_stmt_close($stmt);

// MET values for activities
$met_values = [
    'Running' => 8,
    'Cycling' => 6,
    'Yoga' => 3,
    'Swimming' => 7
];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['activity_submit'])) {
        $activity_type = trim($_POST['activity_type']);
        $duration = (int)$_POST['duration'];
        $date = $_POST['date'];

        if (empty($activity_type) || $duration <= 0 || empty($date)) {
            $errors[] = "All activity fields are required and duration must be positive.";
        } else {
            // Calculate calories burned: MET * weight (kg) * duration (hours)
            $met = $met_values[$activity_type] ?? 5; // Default MET 5 if unknown
            $calories_burned = $met * $weight * ($duration / 60);

            $query = "INSERT INTO activity_logs (user_id, activity_type, duration, calories_burned, date) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "isids", $user_id, $activity_type, $duration, $calories_burned, $date);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Activity logged successfully! Burned " . round($calories_burned, 1) . " kcal.";
            } else {
                $errors[] = "Failed to log activity: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    } elseif (isset($_POST['nutrition_submit'])) {
        $food_name = trim($_POST['food_name']);
        $calories = (float)$_POST['calories'];
        $protein = !empty($_POST['protein']) ? (float)$_POST['protein'] : NULL;
        $carbs = !empty($_POST['carbs']) ? (float)$_POST['carbs'] : NULL;
        $fats = !empty($_POST['fats']) ? (float)$_POST['fats'] : NULL;
        $date = $_POST['date'];

        if (empty($food_name) || $calories < 0 || empty($date)) {
            $errors[] = "Food name, calories (non-negative), and date are required.";
        } else {
            $query = "INSERT INTO nutrition_logs (user_id, food_name, calories, protein, carbs, fats, date) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "isdddds", $user_id, $food_name, $calories, $protein, $carbs, $fats, $date);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Nutrition logged successfully!";
            } else {
                $errors[] = "Failed to log nutrition: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    } elseif (isset($_POST['wellness_submit'])) {
        $mood = trim($_POST['mood']);
        $notes = trim($_POST['notes']);
        $date = $_POST['date'];

        if (empty($mood) || empty($date)) {
            $errors[] = "Mood and date are required.";
        } else {
            $query = "INSERT INTO wellness_logs (user_id, mood, notes, date) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "isss", $user_id, $mood, $notes, $date);
            if (mysqli_stmt_execute($stmt)) {
                $success = "Wellness logged successfully!";
            } else {
                $errors[] = "Failed to log wellness: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// Fetch recent logs
$activity_logs = mysqli_query($conn, "SELECT * FROM activity_logs WHERE user_id = $user_id ORDER BY date DESC LIMIT 5");
$nutrition_logs = mysqli_query($conn, "SELECT * FROM nutrition_logs WHERE user_id = $user_id ORDER BY date DESC LIMIT 5");
$wellness_logs = mysqli_query($conn, "SELECT * FROM wellness_logs WHERE user_id = $user_id ORDER BY date DESC LIMIT 5");

// Close connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - MyWellness Hub</title>
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
        input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 18px;
            height: 18px;
            background: #4ABDAC;
            border-radius: 50%;
            cursor: pointer;
        }
        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
        }
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background-color: #A2A8D3;
            border-radius: 4px;
        }
        .scrollbar-thin::-webkit-scrollbar-track {
            background: #f1f1f1;
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
<body class="bg-white min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <aside id="sidebar" class="w-64 h-screen bg-white border-r border-gray-200 fixed sidebar sidebar-hidden md:sidebar-open z-50">
            <div class="p-4 md:p-6">
                <a href="index.php" class="font-['Pacifico'] text-xl md:text-2xl text-primary">MyWellness Hub</a>
            </div>
            <nav class="mt-4 md:mt-6">
                <a href="dashboard.php" class="flex items-center px-4 md:px-6 py-2 md:py-3 text-slate bg-primary/10">
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
                <a href="ai.php" class="flex items-center px-6 py-3 text-slate bg-primary/10">
                    <i class="ri-robot-line mr-3"></i> AI Analysis
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

            <!-- Dashboard Content -->
            <div class="p-4 md:p-6 lg:p-8">
                <!-- Header -->
                <header class="flex flex-col md:flex-row justify-between items-center mb-4 md:mb-6 lg:mb-8">
                    <div class="text-center md:text-left">
                        <h1 class="text-lg md:text-xl lg:text-2xl font-semibold text-slate">Welcome back, <?php echo htmlspecialchars($username); ?>!</h1>
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

                <!-- Activity and Wellness -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mb-4 md:mb-6">
                    <!-- Activity Card -->
                    <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border border-gray-100">
                        <div class="flex justify-between items-center mb-3 md:mb-4">
                            <h2 class="text-sm md:text-base lg:text-lg font-semibold text-slate flex items-center">
                                <i class="ri-heart-pulse-line text-primary mr-1 md:mr-2"></i> Daily Activity
                            </h2>
                        </div>
                        <form method="POST" class="space-y-3 md:space-y-4">
                            <input type="hidden" name="activity_submit" value="1">
                            <div>
                                <label class="block text-slate text-xs md:text-sm">Type</label>
                                <select name="activity_type" class="w-full p-2 border border-lavender/20 rounded text-xs md:text-sm">
                                    <option value="Running">Running</option>
                                    <option value="Cycling">Cycling</option>
                                    <option value="Yoga">Yoga</option>
                                    <option value="Swimming">Swimming</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate text-xs md:text-sm">Duration (minutes)</label>
                                <input type="number" name="duration" min="1" class="w-full p-2 border border-lavender/20 rounded text-xs md:text-sm" required>
                            </div>
                            <div>
                                <label class="block text-slate text-xs md:text-sm">Date</label>
                                <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" class="w-full p-2 border border-lavender/20 rounded text-xs md:text-sm" required>
                            </div>
                            <button type="submit" class="w-full py-2 bg-primary text-white rounded-button hover:bg-primary/90 transition-colors text-xs md:text-sm">Log Activity</button>
                        </form>
                        <h3 class="text-xs md:text-sm lg:text-base font-semibold text-slate mt-4 md:mt-6">Recent Activities</h3>
                        <ul class="mt-2 space-y-1 md:space-y-2 max-h-24 md:max-h-32 overflow-y-auto scrollbar-thin text-xs md:text-sm">
                            <?php while ($row = mysqli_fetch_assoc($activity_logs)): ?>
                                <li class="text-slate/80"><?php echo $row['date'] . ": " . $row['activity_type'] . " (" . $row['duration'] . " min, " . round($row['calories_burned'], 1) . " kcal)"; ?></li>
                            <?php endwhile; ?>
                        </ul>
                    </div>

                    <!-- Wellness Card -->
                    <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border border-gray-100">
                        <div class="flex justify-between items-center mb-3 md:mb-4">
                            <h2 class="text-sm md:text-base lg:text-lg font-semibold text-slate flex items-center">
                                <i class="ri-mental-health-line text-primary mr-1 md:mr-2"></i> Mood Tracking
                            </h2>
                        </div>
                        <form method="POST" class="space-y-3 md:space-y-4">
                            <input type="hidden" name="wellness_submit" value="1">
                            <div>
                                <label class="block text-slate text-xs md:text-sm">Mood</label>
                                <select name="mood" class="w-full p-2 border border-lavender/20 rounded text-xs md:text-sm">
                                    <option value="Happy">Happy</option>
                                    <option value="Stressed">Stressed</option>
                                    <option value="Calm">Calm</option>
                                    <option value="Tired">Tired</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-slate text-xs md:text-sm">Notes (optional)</label>
                                <textarea name="notes" class="w-full p-2 border border-lavender/20 rounded text-xs md:text-sm" rows="2"></textarea>
                            </div>
                            <div>
                                <label class="block text-slate text-xs md:text-sm">Date</label>
                                <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" class="w-full p-2 border border-lavender/20 rounded text-xs md:text-sm" required>
                            </div>
                            <button type="submit" class="w-full py-2 bg-secondary text-white rounded-button hover:bg-secondary/90 transition-colors text-xs md:text-sm">Log Mood</button>
                        </form>
                        <h3 class="text-xs md:text-sm lg:text-base font-semibold text-slate mt-4 md:mt-6">Recent Moods</h3>
                        <ul class="mt-2 space-y-1 md:space-y-2 max-h-24 md:max-h-32 overflow-y-auto scrollbar-thin text-xs md:text-sm">
                            <?php while ($row = mysqli_fetch_assoc($wellness_logs)): ?>
                                <li class="text-slate/80"><?php echo $row['date'] . ": " . $row['mood']; ?></li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </div>

                <!-- Nutrition and Placeholder Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6 mb-4 md:mb-6">
                    <!-- Nutrition Card -->
                    <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border border-gray-100">
                        <h3 class="text-sm md:text-base lg:text-lg font-semibold text-slate mb-3 md:mb-4 flex items-center">
                            <i class="ri-restaurant-line text-primary mr-1 md:mr-2"></i> Nutrition Overview
                        </h3>
                        <form method="POST" class="space-y-3 md:space-y-4">
                            <input type="hidden" name="nutrition_submit" value="1">
                            <div>
                                <label class="block text-slate text-xs md:text-sm">Food Name</label>
                                <input type="text" name="food_name" class="w-full p-2 border border-lavender/20 rounded text-xs md:text-sm" required>
                            </div>
                            <div>
                                <label class="block text-slate text-xs md:text-sm">Calories</label>
                                <input type="number" name="calories" min="0" step="0.1" class="w-full p-2 border border-lavender/20 rounded text-xs md:text-sm" required>
                            </div>
                            <div>
                                <label class="block text-slate text-xs md:text-sm">Protein (g, optional)</label>
                                <input type="number" name="protein" step="0.1" class="w-full p-2 border border-lavender/20 rounded text-xs md:text-sm">
                            </div>
                            <div>
                                <label class="block text-slate text-xs md:text-sm">Carbs (g, optional)</label>
                                <input type="number" name="carbs" step="0.1" class="w-full p-2 border border-lavender/20 rounded text-xs md:text-sm">
                            </div>
                            <div>
                                <label class="block text-slate text-xs md:text-sm">Fats (g, optional)</label>
                                <input type="number" name="fats" step="0.1" class="w-full p-2 border border-lavender/20 rounded text-xs md:text-sm">
                            </div>
                            <div>
                                <label class="block text-slate text-xs md:text-sm">Date</label>
                                <input type="date" name="date" value="<?php echo date('Y-m-d'); ?>" class="w-full p-2 border border-lavender/20 rounded text-xs md:text-sm" required>
                            </div>
                            <button type="submit" class="w-full py-2 bg-primary text-white rounded-button hover:bg-primary/90 transition-colors text-xs md:text-sm">Log Nutrition</button>
                        </form>
                        <h3 class="text-xs md:text-sm lg:text-base font-semibold text-slate mt-4 md:mt-6">Recent Nutrition</h3>
                        <ul class="mt-2 space-y-1 md:space-y-2 max-h-24 md:max-h-32 overflow-y-auto scrollbar-thin text-xs md:text-sm">
                            <?php while ($row = mysqli_fetch_assoc($nutrition_logs)): ?>
                                <li class="text-slate/80"><?php echo $row['date'] . ": " . $row['food_name'] . " (" . $row['calories'] . " kcal)"; ?></li>
                            <?php endwhile; ?>
                        </ul>
                    </div>

                    <!-- Water Intake Placeholder -->
                    <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border border-gray-100">
                        <h3 class="text-sm md:text-base lg:text-lg font-semibold text-slate mb-3 md:mb-4">Water Intake</h3>
                        <div class="flex items-center justify-center mb-4 md:mb-6">
                            <div class="relative w-20 md:w-24 lg:w-32 h-20 md:h-24 lg:h-32">
                                <svg class="transform rotate-180" viewBox="0 0 36 36">
                                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                          fill="none" stroke="#E5E7EB" stroke-width="3" />
                                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                          fill="none" stroke="#4ABDAC" stroke-width="3" stroke-dasharray="75, 100" />
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <div class="text-center">
                                        <div class="text-lg md:text-xl lg:text-2xl font-bold text-primary">75%</div>
                                        <div class="text-xs md:text-sm text-gray-500">1.5L / 2L</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button class="rounded-button w-full py-2 bg-primary text-white hover:bg-primary/90 text-xs md:text-sm">Add Water</button>
                    </div>

                    <!-- Sleep Quality Placeholder -->
                    <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border border-gray-100">
                        <h3 class="text-sm md:text-base lg:text-lg font-semibold text-slate mb-3 md:mb-4">Sleep Quality</h3>
                        <div class="text-center text-gray-500 mb-4 md:mb-6 text-xs md:text-sm">
                            <p>Sleep tracking not yet implemented.</p>
                            <p>Score: N/A | Duration: N/A</p>
                        </div>
                        <button class="rounded-button w-full py-2 bg-primary text-white hover:bg-primary/90 text-xs md:text-sm">Log Sleep (Coming Soon)</button>
                    </div>
                </div>

                <!-- Goals Placeholder -->
                <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border border-gray-100">
                    <h3 class="text-sm md:text-base lg:text-lg font-semibold text-slate mb-3 md:mb-4">Weekly Goals Progress</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 lg:gap-6">
                        <div>
                            <div class="flex justify-between text-xs md:text-sm mb-2">
                                <span class="text-gray-600">Steps</span>
                                <span>8,500 / 10,000</span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full">
                                <div class="h-2 bg-primary rounded-full" style="width: 85%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs md:text-sm mb-2">
                                <span class="text-gray-600">Exercise</span>
                                <span>4 / 5 days</span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full">
                                <div class="h-2 bg-secondary rounded-full" style="width: 80%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs md:text-sm mb-2">
                                <span class="text-gray-600">Meditation</span>
                                <span>25 / 30 min</span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full">
                                <div class="h-2 bg-lavender rounded-full" style="width: 83%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs md:text-sm mb-2">
                                <span class="text-gray-600">Sleep</span>
                                <span>7.5 / 8 hours</span>
                            </div>
                            <div class="h-2 bg-gray-200 rounded-full">
                                <div class="h-2 bg-slate rounded-full" style="width: 94%"></div>
                            </div>
                        </div>
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