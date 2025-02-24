<?php
// Start session (optional, if you want to redirect after signup)
session_start();

// Include database configuration
require_once 'config.php';

// Initialize variables for form data and errors
$username = $email = $password = $age = $weight = '';
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and validate inputs
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = mysqli_real_escape_string($conn, trim($_POST['password']));
    $age = !empty($_POST['age']) ? (int)$_POST['age'] : NULL;
    $weight = !empty($_POST['weight']) ? (float)$_POST['weight'] : NULL;

    // Validation
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters.";
    }

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }

    if ($age !== NULL && ($age < 1 || $age > 150)) {
        $errors[] = "Age must be between 1 and 150.";
    }

    if ($weight !== NULL && ($weight < 1 || $weight > 500)) {
        $errors[] = "Weight must be between 1 and 500 kg.";
    }

    // Check if username or email already exists
    $check_query = "SELECT user_id FROM users WHERE username = '$username' OR email = '$email'";
    $check_result = mysqli_query($conn, $check_query);
    if (mysqli_num_rows($check_result) > 0) {
        $errors[] = "Username or email already taken.";
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert user into database
        $query = "INSERT INTO users (username, email, password, age, weight) 
                  VALUES ('$username', '$email', '$hashed_password', " . 
                  ($age !== NULL ? $age : 'NULL') . ", " . 
                  ($weight !== NULL ? $weight : 'NULL') . ")";
        
        if (mysqli_query($conn, $query)) {
            // Registration successful, redirect to login or dashboard
            header("Location: login.php"); // Adjust to your login page
            exit();
        } else {
            $errors[] = "Registration failed: " . mysqli_error($conn);
        }
    }
}

// Close connection (optional, PHP closes it automatically at script end)
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - MyWellness Hub</title>
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
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white p-8 rounded-lg shadow-lg">
        <h2 class="text-3xl font-bold text-slate text-center mb-6">Join MyWellness Hub</h2>
        
        <!-- Display errors if any -->
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 text-red-700 p-4 rounded mb-6">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Registration form -->
        <form method="POST" action="register.php" class="space-y-6">
            <div>
                <label for="username" class="block text-slate font-medium">Username</label>
                <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username); ?>" 
                       class="w-full mt-1 p-3 border border-lavender/20 rounded focus:outline-none focus:ring-2 focus:ring-primary" 
                       required>
            </div>
            <div>
                <label for="email" class="block text-slate font-medium">Email</label>
                <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($email); ?>" 
                       class="w-full mt-1 p-3 border border-lavender/20 rounded focus:outline-none focus:ring-2 focus:ring-primary" 
                       required>
            </div>
            <div>
                <label for="password" class="block text-slate font-medium">Password</label>
                <input type="password" name="password" id="password" 
                       class="w-full mt-1 p-3 border border-lavender/20 rounded focus:outline-none focus:ring-2 focus:ring-primary" 
                       required>
            </div>
            <div>
                <label for="age" class="block text-slate font-medium">Age (optional)</label>
                <input type="number" name="age" id="age" value="<?php echo $age !== NULL ? $age : ''; ?>" 
                       class="w-full mt-1 p-3 border border-lavender/20 rounded focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label for="weight" class="block text-slate font-medium">Weight in kg (optional)</label>
                <input type="number" step="0.1" name="weight" id="weight" value="<?php echo $weight !== NULL ? $weight : ''; ?>" 
                       class="w-full mt-1 p-3 border border-lavender/20 rounded focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <button type="submit" class="w-full py-3 bg-primary text-white rounded hover:bg-primary/90 transition-colors">
                Register
            </button>
        </form>

        <p class="mt-4 text-center text-slate">
            Already have an account? <a href="login.php" class="text-primary hover:underline">Sign In</a>
        </p>
    </div>
</body>
</html>