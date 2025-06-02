<?php
session_start();

$token = $_GET['token'] ?? '';
$error = '';
$success = '';

// If form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $resetToken = $_POST['token'] ?? '';
    
    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'Please fill in all fields.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (strlen($newPassword) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } else {
        // Call the reset password API
        $data = [
            'action' => 'reset_password',
            'token' => $resetToken,
            'password' => $newPassword
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents('http://' . $_SERVER['HTTP_HOST'] . '/api/auth.php', false, $context);
        $response = json_decode($result, true);
        
        if ($response && $response['success']) {
            $success = 'Password reset successfully! You can now login with your new password.';
            $token = ''; // Clear token after successful reset
        } else {
            $error = $response['message'] ?? 'Failed to reset password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Thermeleon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'thermeleon': {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .login-bg {
            background: linear-gradient(135deg, #0ea5e9 0%, #0369a1 100%);
        }
    </style>
</head>
<body class="min-h-screen login-bg flex items-center justify-center px-4">
    <div class="max-w-md w-full space-y-8">
        <div class="bg-white rounded-lg shadow-xl p-8">
            <!-- Logo and Title -->
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-thermeleon-500 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 0h12a2 2 0 002-2v-1a2 2 0 00-2-2H6a2 2 0 00-2 2v1a2 2 0 002 2z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19H6.931A1.922 1.922 0 015 17.087V8h12v9.087A1.922 1.922 0 0115.069 19H11z"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Reset Your Password</h2>
                <p class="text-gray-600 mt-2">Enter your new password below</p>
            </div>

            <?php if ($error): ?>
                <div class="mb-4 border-l-4 p-4 bg-red-100 border-red-400 text-red-700">
                    <div class="flex">
                        <div class="ml-3">
                            <p class="text-sm"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-4 border-l-4 p-4 bg-green-100 border-green-400 text-green-700">
                    <div class="flex">
                        <div class="ml-3">
                            <p class="text-sm"><?php echo htmlspecialchars($success); ?></p>
                        </div>
                    </div>
                </div>
                <div class="text-center">
                    <a href="login.php" class="inline-flex items-center px-4 py-2 bg-thermeleon-500 text-white rounded-lg hover:bg-thermeleon-600 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 0v-3a1 1 0 00-1-1h-4"></path>
                        </svg>
                        Go to Login
                    </a>
                </div>
            <?php else: ?>
                <?php if (empty($token)): ?>
                    <div class="text-center">
                        <div class="mb-4 border-l-4 p-4 bg-yellow-100 border-yellow-400 text-yellow-700">
                            <div class="flex">
                                <div class="ml-3">
                                    <p class="text-sm">Invalid or missing reset token. Please request a new password reset.</p>
                                </div>
                            </div>
                        </div>
                        <a href="login.php" class="inline-flex items-center px-4 py-2 bg-thermeleon-500 text-white rounded-lg hover:bg-thermeleon-600 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 0v-3a1 1 0 00-1-1h-4"></path>
                            </svg>
                            Back to Login
                        </a>
                    </div>
                <?php else: ?>
                    <!-- Reset Password Form -->
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                        
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                New Password
                            </label>
                            <div class="relative">
                                <input type="password" id="password" name="password" required minlength="6"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-thermeleon-500 focus:border-thermeleon-500 transition-colors pr-10">
                                <button type="button" onclick="togglePassword('password', 'passwordEyeIcon')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg id="passwordEyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Password must be at least 6 characters long</p>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                                Confirm New Password
                            </label>
                            <div class="relative">
                                <input type="password" id="confirm_password" name="confirm_password" required minlength="6"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-thermeleon-500 focus:border-thermeleon-500 transition-colors pr-10">
                                <button type="button" onclick="togglePassword('confirm_password', 'confirmEyeIcon')" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg id="confirmEyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-thermeleon-500 text-white py-2 px-4 rounded-lg hover:bg-thermeleon-600 transition-colors duration-200 font-medium">
                            Reset Password
                        </button>
                    </form>

                    <div class="mt-6 text-center">
                        <a href="login.php" class="text-sm text-thermeleon-600 hover:text-thermeleon-500">
                            Back to Login
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Footer -->
            <div class="mt-8 text-center text-sm text-gray-500">
                <p>&copy; 2024 Thermeleon. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const eyeIcon = document.getElementById(iconId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21"></path>
                `;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                `;
            }
        }

        // Form validation
        document.querySelector('form')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long!');
                return false;
            }
        });
    </script>
</body>
</html> 