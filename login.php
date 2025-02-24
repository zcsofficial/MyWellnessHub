<?php
// Start session
session_start();

// Include database configuration
require_once 'config.php';

// Initialize variables for form data and errors
$login_input = $password = '';
$errors = [];

// Prevent logged-in users from accessing login page
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php"); // Redirect to dashboard if already logged in
    exit();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize inputs
    $login_input = mysqli_real_escape_string($conn, trim($_POST['login_input']));
    $password = mysqli_real_escape_string($conn, trim($_POST['password']));

    // Validation
    if (empty($login_input)) {
        $errors[] = "Username or email is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    // If no validation errors, check credentials
    if (empty($errors)) {
        // Query to check if username or email exists
        $query = "SELECT user_id, username, email, password FROM users 
                  WHERE username = '$login_input' OR email = '$login_input'";
        $result = mysqli_query($conn, $query);

        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Login successful, set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                
                // Redirect to dashboard (or index if no dashboard yet)
                header("Location: index.php"); // Change to dashboard.php when created
                exit();
            } else {
                $errors[] = "Incorrect password.";
            }
        } else {
            $errors[] = "Username or email not found.";
        }
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
    <title>Login - MyWellness Hub</title>
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
        <h2 class="text-3xl font-bold text-slate text-center mb-6">Sign In to MyWellness Hub</h2>
        
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

        <!-- Login form -->
        <form method="POST" action="login.php" class="space-y-6">
            <div>
                <label for="login_input" class="block text-slate font-medium">Username or Email</label>
                <input type="text" name="login_input" id="login_input" value="<?php echo htmlspecialchars($login_input); ?>" 
                       class="w-full mt-1 p-3 border border-lavender/20 rounded focus:outline-none focus:ring-2 focus:ring-primary" 
                       required>
            </div>
            <div>
                <label for="password" class="block text-slate font-medium">Password</label>
                <input type="password" name="password" id="password" 
                       class="w-full mt-1 p-3 border border-lavender/20 rounded focus:outline-none focus:ring-2 focus:ring-primary" 
                       required>
            </div>
            <button type="submit" class="w-full py-3 bg-primary text-white rounded hover:bg-primary/90 transition-colors">
                Sign In
            </button>
        </form>

        <p class="mt-4 text-center text-slate">
            Don’t have an account? <a href="register.php" class="text-primary hover:underline">Register</a>
        </p>
    </div>
</body>
</html>