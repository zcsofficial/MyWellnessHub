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

// Fetch username for header
$user_query = "SELECT username FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);
$username = $user['username'];
mysqli_stmt_close($stmt);

// Fetch data for graphs (last 30 days)
$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');

// Activity data (calories burned)
$activity_query = "SELECT date, SUM(calories_burned) as total_calories 
                   FROM activity_logs 
                   WHERE user_id = ? AND date BETWEEN ? AND ? 
                   GROUP BY date 
                   ORDER BY date ASC";
$stmt = mysqli_prepare($conn, $activity_query);
mysqli_stmt_bind_param($stmt, "iss", $user_id, $start_date, $end_date);
mysqli_stmt_execute($stmt);
$activity_result = mysqli_stmt_get_result($stmt);
$activity_dates = [];
$activity_calories = [];
while ($row = mysqli_fetch_assoc($activity_result)) {
    $activity_dates[] = date('D', strtotime($row['date'])); // Short day name (e.g., Mon)
    $activity_calories[] = round($row['total_calories'], 1);
}
mysqli_stmt_close($stmt);

// Nutrition data (calories and macros)
$nutrition_query = "SELECT SUM(calories) as total_calories, SUM(protein) as total_protein, 
                    SUM(carbs) as total_carbs, SUM(fats) as total_fats 
                    FROM nutrition_logs 
                    WHERE user_id = ? AND date BETWEEN ? AND ?";
$stmt = mysqli_prepare($conn, $nutrition_query);
mysqli_stmt_bind_param($stmt, "iss", $user_id, $start_date, $end_date);
mysqli_stmt_execute($stmt);
$nutrition_result = mysqli_stmt_get_result($stmt);
$nutrition = mysqli_fetch_assoc($nutrition_result);
$nutrition_categories = ['Protein', 'Carbs', 'Fats'];
$nutrition_values = [
    round($nutrition['total_protein'] ?? 0, 1),
    round($nutrition['total_carbs'] ?? 0, 1),
    round($nutrition['total_fats'] ?? 0, 1)
];
mysqli_stmt_close($stmt);

// Wellness data (mood scores)
$wellness_query = "SELECT date, mood 
                   FROM wellness_logs 
                   WHERE user_id = ? AND date BETWEEN ? AND ? 
                   ORDER BY date ASC";
$stmt = mysqli_prepare($conn, $wellness_query);
mysqli_stmt_bind_param($stmt, "iss", $user_id, $start_date, $end_date);
mysqli_stmt_execute($stmt);
$wellness_result = mysqli_stmt_get_result($stmt);
$wellness_dates = [];
$wellness_scores = [];
$mood_map = ['Happy' => 8, 'Calm' => 6, 'Tired' => 4, 'Stressed' => 2]; // Simplified scoring
while ($row = mysqli_fetch_assoc($wellness_result)) {
    $wellness_dates[] = date('D', strtotime($row['date']));
    $wellness_scores[] = $mood_map[$row['mood']] ?? 5; // Default 5 if mood not mapped
}
mysqli_stmt_close($stmt);

// Close connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - MyWellness Hub</title>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.5.0/echarts.min.js"></script>
    <style>
        .chart-container { min-height: 150px; max-height: 300px; width: 100%; }
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
                <a href="reports.php" class="flex items-center px-4 md:px-6 py-2 md:py-3 text-slate bg-primary/10">
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
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 main-content p-4 md:p-6 lg:p-8">
            <!-- Hamburger Menu for Mobile -->
            <div class="md:hidden flex justify-between items-center mb-4">
                <button id="hamburger-btn" class="text-slate focus:outline-none">
                    <i class="ri-menu-line text-xl md:text-2xl"></i>
                </button>
                <div class="flex items-center space-x-2">
                    <img src="https://public.readdy.ai/ai/img_res/6d13389090137366a30fed5b15c5dfaf.jpg" 
                         class="w-6 h-6 rounded-full object-cover" alt="Profile">
                    <span class="text-slate text-xs"><?php echo htmlspecialchars($username); ?></span>
                </div>
            </div>

            <!-- Header -->
            <header class="flex flex-col md:flex-row justify-between items-center mb-4 md:mb-6 lg:mb-8">
                <div class="text-center md:text-left">
                    <h1 class="text-lg md:text-xl lg:text-2xl font-semibold text-slate">Your Reports, <?php echo htmlspecialchars($username); ?>!</h1>
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

            <!-- Graphs -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6 mb-4 md:mb-6">
                <!-- Activity Graph -->
                <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border border-gray-100">
                    <div class="flex justify-between items-center mb-3 md:mb-4">
                        <h2 class="text-sm md:text-base lg:text-lg font-semibold text-slate">Daily Activity</h2>
                        <div class="flex space-x-1 md:space-x-2">
                            <button class="rounded-button px-2 md:px-3 py-1 text-xs md:text-sm bg-primary text-white">Last 30 Days</button>
                        </div>
                    </div>
                    <div id="activityChart" class="chart-container"></div>
                </div>

                <!-- Mood Graph -->
                <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border border-gray-100">
                    <div class="flex justify-between items-center mb-3 md:mb-4">
                        <h2 class="text-sm md:text-base lg:text-lg font-semibold text-slate">Mood Tracking</h2>
                    </div>
                    <div id="moodChart" class="chart-container"></div>
                </div>
            </div>

            <!-- Nutrition and Placeholders -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 md:gap-6">
                <!-- Nutrition Graph -->
                <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border border-gray-100">
                    <h3 class="text-sm md:text-base lg:text-lg font-semibold text-slate mb-3 md:mb-4">Nutrition (30 Days)</h3>
                    <div id="nutritionChart" class="chart-container"></div>
                </div>

                <!-- Water Placeholder -->
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
                </div>

                <!-- Sleep Placeholder -->
                <div class="bg-white p-4 md:p-6 rounded-lg shadow-sm border border-gray-100">
                    <h3 class="text-sm md:text-base lg:text-lg font-semibold text-slate mb-3 md:mb-4">Sleep Quality</h3>
                    <div class="text-center text-gray-500 mb-4 md:mb-6 text-xs md:text-sm">
                        <p>Sleep tracking not yet implemented.</p>
                        <p>Score: N/A | Duration: N/A</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- ECharts Scripts -->
    <script>
        // Activity Chart (Line)
        const activityChart = echarts.init(document.getElementById('activityChart'));
        activityChart.setOption({
            animation: false,
            tooltip: { trigger: 'axis', backgroundColor: 'rgba(255, 255, 255, 0.9)' },
            xAxis: {
                type: 'category',
                data: <?php echo json_encode($activity_dates); ?>,
                axisLine: { show: false },
                axisTick: { show: false }
            },
            yAxis: {
                type: 'value',
                name: 'Calories (kcal)',
                axisLine: { show: false },
                axisTick: { show: false }
            },
            series: [{
                data: <?php echo json_encode($activity_calories); ?>,
                type: 'line',
                smooth: true,
                symbolSize: 0,
                lineStyle: { color: '#4ABDAC' },
                areaStyle: {
                    color: {
                        type: 'linear',
                        x: 0, y: 0, x2: 0, y2: 1,
                        colorStops: [
                            { offset: 0, color: 'rgba(74, 189, 172, 0.2)' },
                            { offset: 1, color: 'rgba(74, 189, 172, 0)' }
                        ]
                    }
                }
            }]
        });

        // Mood Chart (Line)
        const moodChart = echarts.init(document.getElementById('moodChart'));
        moodChart.setOption({
            animation: false,
            tooltip: { trigger: 'axis', backgroundColor: 'rgba(255, 255, 255, 0.9)' },
            xAxis: {
                type: 'category',
                data: <?php echo json_encode($wellness_dates); ?>,
                axisLine: { show: false },
                axisTick: { show: false }
            },
            yAxis: {
                type: 'value',
                min: 0,
                max: 10,
                name: 'Mood Score',
                axisLine: { show: false },
                axisTick: { show: false }
            },
            series: [{
                data: <?php echo json_encode($wellness_scores); ?>,
                type: 'line',
                smooth: true,
                symbolSize: 0,
                lineStyle: { color: '#A2A8D3' },
                areaStyle: {
                    color: {
                        type: 'linear',
                        x: 0, y: 0, x2: 0, y2: 1,
                        colorStops: [
                            { offset: 0, color: 'rgba(162, 168, 211, 0.2)' },
                            { offset: 1, color: 'rgba(162, 168, 211, 0)' }
                        ]
                    }
                }
            }]
        });

        // Nutrition Chart (Pie)
        const nutritionChart = echarts.init(document.getElementById('nutritionChart'));
        nutritionChart.setOption({
            animation: false,
            tooltip: { trigger: 'item', backgroundColor: 'rgba(255, 255, 255, 0.9)' },
            series: [{
                type: 'pie',
                radius: ['40%', '70%'],
                avoidLabelOverlap: false,
                itemStyle: {
                    borderRadius: 10,
                    borderColor: '#fff',
                    borderWidth: 2
                },
                label: { show: false },
                emphasis: {
                    label: { show: true, fontSize: '12', fontWeight: 'bold' }
                },
                labelLine: { show: false },
                data: [
                    { value: <?php echo $nutrition_values[0]; ?>, name: 'Protein', itemStyle: { color: '#4ABDAC' } },
                    { value: <?php echo $nutrition_values[1]; ?>, name: 'Carbs', itemStyle: { color: '#FCBB6D' } },
                    { value: <?php echo $nutrition_values[2]; ?>, name: 'Fats', itemStyle: { color: '#A2A8D3' } }
                ]
            }]
        });

        // Resize charts on window resize
        window.addEventListener('resize', () => {
            activityChart.resize();
            moodChart.resize();
            nutritionChart.resize();
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