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
$recommendations = [];
$analysis = '';

// Fetch user data
$user_query = "SELECT username, weight, age FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($user_result);
$username = $user['username'];
$weight = $user['weight'] ?? 70; // Default to 70 kg if not set
$age = $user['age'] ?? 30; // Default to 30 years if not set
mysqli_stmt_close($stmt);

// Fetch recent logs (last 7 days for simplicity)
$last_week = date('Y-m-d', strtotime('-7 days'));
$activity_logs = mysqli_query($conn, "SELECT * FROM activity_logs WHERE user_id = $user_id AND date >= '$last_week' ORDER BY date DESC");
$nutrition_logs = mysqli_query($conn, "SELECT * FROM nutrition_logs WHERE user_id = $user_id AND date >= '$last_week' ORDER BY date DESC");
$wellness_logs = mysqli_query($conn, "SELECT * FROM wellness_logs WHERE user_id = $user_id AND date >= '$last_week' ORDER BY date DESC");

// Simple analysis
$total_calories_burned = 0;
$total_duration = 0;
$total_calories_consumed = 0;
$mood_counts = ['Happy' => 0, 'Stressed' => 0, 'Calm' => 0, 'Tired' => 0];

while ($activity = mysqli_fetch_assoc($activity_logs)) {
    $total_calories_burned += $activity['calories_burned'];
    $total_duration += $activity['duration'];
}

while ($nutrition = mysqli_fetch_assoc($nutrition_logs)) {
    $total_calories_consumed += $nutrition['calories'];
}

while ($wellness = mysqli_fetch_assoc($wellness_logs)) {
    $mood_counts[$wellness['mood']]++;
}

// Hardcoded 20 Health Recommendations
$all_recommendations = [
    "Increase water intake to at least 2 liters daily for better hydration.",
    "Aim for 150 minutes of moderate exercise weekly to meet health guidelines.",
    "Incorporate more protein-rich foods (e.g., eggs, beans) to support muscle health.",
    "Reduce sugar intake to improve energy levels and overall wellness.",
    "Practice 10 minutes of mindfulness daily to reduce stress.",
    "Ensure 7-8 hours of sleep nightly for optimal recovery.",
    "Add more leafy greens to your diet for essential vitamins and minerals.",
    "Try a 20-minute brisk walk daily to boost cardiovascular health.",
    "Limit processed foods to maintain a healthy weight.",
    "Stretch for 5-10 minutes daily to improve flexibility.",
    "Consider a morning routine to enhance daily productivity.",
    "Eat smaller, more frequent meals to stabilize blood sugar levels.",
    "Incorporate strength training twice a week to build muscle.",
    "Take short breaks every hour if you sit for long periods.",
    "Boost fiber intake with whole grains to improve digestion.",
    "Try deep breathing exercises to manage stress effectively.",
    "Cut back on caffeine if you feel anxious or restless.",
    "Schedule regular health check-ups to monitor your progress.",
    "Add a variety of fruits to your diet for antioxidants.",
    "Set a consistent sleep schedule to regulate your body clock."
];

// Select recommendations based on analysis
if ($total_calories_burned < 500) {
    $recommendations[] = $all_recommendations[1]; // Exercise more
    $recommendations[] = $all_recommendations[7]; // Brisk walk
}
if ($total_calories_consumed > 2000) {
    $recommendations[] = $all_recommendations[9]; // Limit processed foods
    $recommendations[] = $all_recommendations[11]; // Smaller meals
}
if ($mood_counts['Stressed'] > 2 || $mood_counts['Tired'] > 2) {
    $recommendations[] = $all_recommendations[4]; // Mindfulness
    $recommendations[] = $all_recommendations[15]; // Deep breathing
}
if ($total_duration < 60) {
    $recommendations[] = $all_recommendations[12]; // Strength training
    $recommendations[] = $all_recommendations[13]; // Short breaks
}

// Add generic recommendations to ensure at least 5
$remaining = 5 - count($recommendations);
for ($i = 0; $i < $remaining && $i < count($all_recommendations); $i++) {
    if (!in_array($all_recommendations[$i], $recommendations)) {
        $recommendations[] = $all_recommendations[$i];
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
    <title>AI Analysis - MyWellness Hub</title>
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
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css" rel="stylesheet">
    <style>
        .scrollbar-thin::-webkit-scrollbar { width: 6px; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background-color: #A2A8D3; border-radius: 4px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: #f1f1f1; }
    </style>
</head>
<body class="bg-white min-h-screen">
    <div class="flex">
        <!-- Sidebar (simplified for brevity, same as dashboard.php) -->
        <aside class="w-64 h-screen bg-white border-r border-gray-200 fixed">
            <div class="p-6">
                <a href="index.php" class="font-['Pacifico'] text-2xl text-primary">MyWellness Hub</a>
            </div>
            <nav class="mt-6">
                <a href="dashboard.php" class="flex items-center px-6 py-3 text-slate hover:bg-gray-50">
                    <i class="ri-dashboard-line mr-3"></i> Dashboard
                </a>
                <a href="ai.php" class="flex items-center px-6 py-3 text-slate bg-primary/10">
                    <i class="ri-robot-line mr-3"></i> AI Analysis
                </a>
                <a href="reports.php" class="flex items-center px-6 py-3 text-slate hover:bg-gray-50">
                    <i class="ri-bar-chart-line mr-3"></i> Reports
                </a>
                <a href="settings.php" class="flex items-center px-6 py-3 text-slate hover:bg-gray-50">
                    <i class="ri-settings-3-line mr-3"></i> Settings
                </a>
                <a href="logout.php" class="flex items-center px-6 py-3 text-slate hover:bg-gray-50">
                    <i class="ri-logout-box-line mr-3"></i> Logout
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 ml-64 p-6">
            <h1 class="text-2xl font-semibold text-slate mb-6">AI Health Analysis</h1>
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
                <h2 class="text-lg font-semibold text-slate mb-4">Your Health Summary (Last 7 Days)</h2>
                <p class="text-sm text-gray-600">Calories Burned: <?php echo round($total_calories_burned, 1); ?> kcal</p>
                <p class="text-sm text-gray-600">Total Activity Duration: <?php echo $total_duration; ?> minutes</p>
                <p class="text-sm text-gray-600">Calories Consumed: <?php echo round($total_calories_consumed, 1); ?> kcal</p>
                <p class="text-sm text-gray-600">Mood Summary: 
                    Happy: <?php echo $mood_counts['Happy']; ?>, 
                    Stressed: <?php echo $mood_counts['Stressed']; ?>, 
                    Calm: <?php echo $mood_counts['Calm']; ?>, 
                    Tired: <?php echo $mood_counts['Tired']; ?>
                </p>

                <h2 class="text-lg font-semibold text-slate mt-6 mb-4">Health Recommendations</h2>
                <ul class="list-disc list-inside text-sm text-gray-700 space-y-2 max-h-96 overflow-y-auto scrollbar-thin">
                    <?php foreach ($recommendations as $rec): ?>
                        <li><?php echo htmlspecialchars($rec); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </main>
    </div>
</body>
</html>