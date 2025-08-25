<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'johnpaulproject');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $email = $_POST['email'];
    
    // Force user_type to be 'user' (admin registration not allowed)
    $user_type = 'user';
    
    // Validate password match
    if ($password !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Check password strength (minimum 8 characters)
        if (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long!";
        } else {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Username or email already taken!";
            } else {
                // Hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert new user (always as 'user' type)
                $stmt = $conn->prepare("INSERT INTO users (username, password, user_type, email) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $hashed_password, $user_type, $email);
                
                if ($stmt->execute()) {
                    $_SESSION['user_id'] = $stmt->insert_id;
                    $_SESSION['user_type'] = $user_type;
                    header("Location: user_dashboard.php");
                    exit();
                } else {
                    $error = "Registration failed! Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - John Paul Project</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .register-container {
            width: 100%;
            max-width: 500px;
            background: #fff;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-header h2 {
            font-size: 2.2rem;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .register-header p {
            color: #7f8c8d;
            font-size: 1rem;
        }

        .app-logo {
            font-size: 3.5rem;
            color: #6a11cb;
            margin-bottom: 15px;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .input-with-icon {
            position: relative;
        }

        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
            font-size: 1.1rem;
        }

        .form-group input {
            width: 100%;
            padding: 14px 20px 14px 45px;
            border: 2px solid #e1e5eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-group input:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .form-group input:focus + i {
            color: #3498db;
        }

        .password-strength {
            margin-top: 5px;
            font-size: 0.85rem;
            color: #7f8c8d;
        }

        .password-strength.weak {
            color: #e74c3c;
        }

        .password-strength.medium {
            color: #f39c12;
        }

        .password-strength.strong {
            color: #2ecc71;
        }

        .register-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(37, 117, 252, 0.4);
        }

        .register-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(37, 117, 252, 0.6);
        }

        .register-btn:active {
            transform: translateY(0);
        }

        .additional-links {
            text-align: center;
            margin-top: 25px;
        }

        .back-to-login {
            color: #7f8c8d;
            font-size: 0.95rem;
        }

        .back-to-login a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }

        .back-to-login a:hover {
            text-decoration: underline;
        }

        .error-message {
            background: #ffebee;
            color: #e53935;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.95rem;
            display: none;
        }

        .error-message.show {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        .terms-checkbox {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .terms-checkbox input {
            margin-right: 10px;
        }

        .terms-checkbox label {
            font-size: 0.9rem;
            color: #7f8c8d;
        }

        .terms-checkbox a {
            color: #3498db;
            text-decoration: none;
        }

        .terms-checkbox a:hover {
            text-decoration: underline;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 576px) {
            .register-container {
                padding: 30px 20px;
            }
            
            .register-header h2 {
                font-size: 1.8rem;
            }
            
            .app-logo {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <div class="app-logo">
                <i class="fas fa-user-plus"></i>
            </div>
            <h2>Create Account</h2>
            <p>Join John Paul Project today</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message show">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" id="username" placeholder="Enter your username" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" id="email" placeholder="Enter your email" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" id="password" placeholder="Create a password" required>
                </div>
                <div id="password-strength" class="password-strength"></div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your password" required>
                </div>
                <div id="password-match" class="password-strength"></div>
            </div>
            
            <div class="terms-checkbox">
                <input type="checkbox" id="terms" name="terms" required>
                <label for="terms">I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></label>
            </div>
            
            <input type="hidden" name="user_type" value="user">
            
            <button type="submit" class="register-btn">Create Account</button>
            
            <div class="additional-links">
                <div class="back-to-login">
                    Already have an account? <a href="login.php">Sign In</a>
                </div>
            </div>
        </form>
    </div>

    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const passwordStrength = document.getElementById('password-strength');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordMatch = document.getElementById('password-match');

        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = '';
            let strengthClass = '';
            
            if (password.length === 0) {
                strength = '';
            } else if (password.length < 8) {
                strength = 'Weak (minimum 8 characters)';
                strengthClass = 'weak';
            } else if (password.length < 12) {
                strength = 'Medium';
                strengthClass = 'medium';
            } else {
                strength = 'Strong';
                strengthClass = 'strong';
            }
            
            passwordStrength.textContent = strength;
            passwordStrength.className = 'password-strength ' + strengthClass;
        });

        // Password match checker
        confirmPasswordInput.addEventListener('input', function() {
            if (passwordInput.value !== this.value) {
                passwordMatch.textContent = 'Passwords do not match';
                passwordMatch.className = 'password-strength weak';
            } else {
                passwordMatch.textContent = 'Passwords match';
                passwordMatch.className = 'password-strength strong';
            }
            
            if (this.value.length === 0) {
                passwordMatch.textContent = '';
            }
        });

        // Form submission animation
        const registerBtn = document.querySelector('.register-btn');
        if (registerBtn) {
            registerBtn.addEventListener('click', function() {
                if (document.getElementById('terms').checked) {
                    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
                }
            });
        }
    </script>
</body>
</html>