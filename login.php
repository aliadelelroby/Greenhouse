<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thermeleon - Login</title>
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
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 21l4-4 4 4"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900">Welcome to Thermeleon</h2>
                <p class="text-gray-600 mt-2">Sign in to your account</p>
            </div>

            <!-- Error/Success Messages -->
            <div id="messageContainer" class="hidden mb-4"></div>

            <!-- Login Form -->
            <form id="loginForm" method="POST" action="api/auth.php" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">
                        Username or Email
                    </label>
                    <input type="text" id="username" name="username" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-thermeleon-500 focus:border-thermeleon-500 transition-colors">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        Password
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-thermeleon-500 focus:border-thermeleon-500 transition-colors pr-10">
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-thermeleon-600 focus:ring-thermeleon-500 border-gray-300 rounded">
                        <label for="remember" class="ml-2 block text-sm text-gray-700">
                            Remember me
                        </label>
                    </div>
                    <a href="#" onclick="showForgotPassword()" class="text-sm text-thermeleon-600 hover:text-thermeleon-500">
                        Forgot password?
                    </a>
                </div>

                <button type="submit" class="w-full bg-thermeleon-500 text-white py-2 px-4 rounded-lg hover:bg-thermeleon-600 transition-colors duration-200 font-medium">
                    <span id="loginButtonText">Sign In</span>
                    <svg id="loginSpinner" class="hidden animate-spin ml-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </form>

            <!-- Forgot Password Modal -->
            <div id="forgotPasswordModal" class="hidden">
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Reset Password</h3>
                    <form id="forgotPasswordForm" class="space-y-4">
                        <div>
                            <label for="resetEmail" class="block text-sm font-medium text-gray-700 mb-2">
                                Enter your email address
                            </label>
                            <input type="email" id="resetEmail" name="resetEmail" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-thermeleon-500 focus:border-thermeleon-500 transition-colors">
                        </div>
                        <div class="flex space-x-3">
                            <button type="submit" class="flex-1 bg-thermeleon-500 text-white py-2 px-4 rounded-lg hover:bg-thermeleon-600 transition-colors duration-200">
                                Send Reset Link
                            </button>
                            <button type="button" onclick="hideForgotPassword()" class="flex-1 bg-gray-300 text-gray-700 py-2 px-4 rounded-lg hover:bg-gray-400 transition-colors duration-200">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center text-sm text-gray-500">
                <p>&copy; 2024 Thermeleon. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        class LoginManager {
            constructor() {
                this.setupEventListeners();
            }

            setupEventListeners() {
                const loginForm = document.getElementById('loginForm');
                const forgotPasswordForm = document.getElementById('forgotPasswordForm');

                if (loginForm) {
                    loginForm.addEventListener('submit', (e) => this.handleLogin(e));
                }

                if (forgotPasswordForm) {
                    forgotPasswordForm.addEventListener('submit', (e) => this.handleForgotPassword(e));
                }
            }

            async handleLogin(e) {
                e.preventDefault();
                
                const submitButton = e.target.querySelector('button[type="submit"]');
                const buttonText = document.getElementById('loginButtonText');
                const spinner = document.getElementById('loginSpinner');
                
                try {
                    // Show loading state
                    submitButton.disabled = true;
                    buttonText.textContent = 'Signing In...';
                    spinner.classList.remove('hidden');
                    
                    const formData = new FormData(e.target);
                    
                    const response = await fetch('api/auth.php', {
                        method: 'POST',
                        body: formData
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showMessage('Login successful! Redirecting...', 'success');
                        setTimeout(() => {
                            window.location.href = result.redirect || 'index.php';
                        }, 1000);
                    } else {
                        this.showMessage(result.message || 'Login failed. Please check your credentials.', 'error');
                    }
                    
                } catch (error) {
                    console.error('Login error:', error);
                    this.showMessage('Network error. Please try again.', 'error');
                } finally {
                    // Reset button state
                    submitButton.disabled = false;
                    buttonText.textContent = 'Sign In';
                    spinner.classList.add('hidden');
                }
            }

            async handleForgotPassword(e) {
                e.preventDefault();
                
                const email = document.getElementById('resetEmail').value;
                
                try {
                    const response = await fetch('api/auth.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'forgot_password',
                            email: email
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showMessage('Password reset instructions sent to your email.', 'success');
                        hideForgotPassword();
                    } else {
                        this.showMessage(result.message || 'Failed to send reset email.', 'error');
                    }
                    
                } catch (error) {
                    console.error('Forgot password error:', error);
                    this.showMessage('Network error. Please try again.', 'error');
                }
            }

            showMessage(message, type) {
                const container = document.getElementById('messageContainer');
                const bgColor = type === 'error' ? 'bg-red-100 border-red-400 text-red-700' : 'bg-green-100 border-green-400 text-green-700';
                
                container.innerHTML = `
                    <div class="border-l-4 p-4 ${bgColor}">
                        <div class="flex">
                            <div class="ml-3">
                                <p class="text-sm">${message}</p>
                            </div>
                        </div>
                    </div>
                `;
                container.classList.remove('hidden');
                
                // Auto-hide success messages
                if (type === 'success') {
                    setTimeout(() => {
                        container.classList.add('hidden');
                    }, 3000);
                }
            }
        }

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
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

        function showForgotPassword() {
            document.getElementById('forgotPasswordModal').classList.remove('hidden');
        }

        function hideForgotPassword() {
            document.getElementById('forgotPasswordModal').classList.add('hidden');
            document.getElementById('resetEmail').value = '';
        }

        // Initialize login manager
        document.addEventListener('DOMContentLoaded', function() {
            new LoginManager();
        });
    </script>
</body>
</html> 