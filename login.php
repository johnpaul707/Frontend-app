<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'johnpaulproject');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];
    
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ? AND user_type = ?");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("ss", $username, $user_type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        // First try password_verify, then try plain text comparison
        if (password_verify($password, $user['password']) || $password == $user['password']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_type'] = $user_type;
            $_SESSION['username'] = $username;
            
            if ($user_type == 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: user_dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid password";
        }
    } else {
        $error = "Username not found or incorrect user type";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - John Paul Project</title>
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

        .container {
            display: flex;
            max-width: 1000px;
            width: 100%;
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .login-left {
            flex: 1;
            background: linear-gradient(135deg, rgba(106, 17, 203, 0.9) 0%, rgba(37, 117, 252, 0.9) 100%);
            color: white;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .login-left h1 {
            font-size: 2.8rem;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .login-left p {
            font-size: 1.1rem;
            margin-bottom: 30px;
            opacity: 0.9;
            max-width: 400px;
        }

        .app-logo {
            font-size: 5rem;
            margin-bottom: 30px;
            color: rgba(255, 255, 255, 0.9);
        }

        .login-right {
            flex: 1;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h2 {
            font-size: 2.2rem;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .login-header p {
            color: #7f8c8d;
            font-size: 1rem;
        }

        .login-form {
            width: 100%;
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

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 14px 20px 14px 45px;
            border: 2px solid #e1e5eb;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        }

        .form-group input:focus + i {
            color: #3498db;
        }

        .user-type-select {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }

        .user-type-select label {
            flex: 1;
            text-align: center;
            padding: 12px 10px;
            background: #f8f9fa;
            border: 2px solid #e1e5eb;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            color: #7f8c8d;
        }

        .user-type-select input[type="radio"] {
            display: none;
        }

        .user-type-select input[type="radio"]:checked + label {
            background: #3498db;
            color: white;
            border-color: #3498db;
        }

        .login-btn {
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

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(37, 117, 252, 0.6);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .additional-links {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .forgot-password {
            color: #3498db;
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }

        .forgot-password:hover {
            color: #2575fc;
            text-decoration: underline;
        }

        .register-link {
            color: #7f8c8d;
            font-size: 0.95rem;
        }

        .register-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
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

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                max-width: 500px;
            }
            
            .login-left {
                padding: 30px 20px;
            }
            
            .login-left h1 {
                font-size: 2.2rem;
            }
            
            .app-logo {
                font-size: 4rem;
                margin-bottom: 20px;
            }
        }

        @media (max-width: 480px) {
            .login-left {
                display: none;
            }
            
            .login-right {
                padding: 40px 25px;
            }
            
            .user-type-select {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-left">
            <div class="app-logo">
                <i class="fas fa-lock"></i>
            </div>
            <h1>John Paul Project</h1>
            <p>Secure access to your personalized dashboard. Manage your account and settings with our easy-to-use interface.</p>
            <div class="features">
                <div class="feature-item">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Bank-level Security</h3>
                </div>
                <div class="feature-item">
                    <i class="fas fa-sync-alt"></i>
                    <h3>Real-time Updates</h3>
                </div>
            </div>
        </div>
        
        <div class="login-right">
            <div class="login-header">
                <h2>Welcome Back!</h2>
                <p>Sign in to continue to your account</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error-message show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form class="login-form" method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" name="username" id="username" placeholder="Enter your username" required
                               value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" placeholder="Enter your password" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Login As</label>
                    <div class="user-type-select">
                        <input type="radio" name="user_type" id="admin" value="admin" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] == 'admin') ? 'checked' : ''; ?>>
                        <label for="admin">Administrator</label>
                        
                        <input type="radio" name="user_type" id="user" value="user" <?php echo (!isset($_POST['user_type']) || (isset($_POST['user_type']) && $_POST['user_type'] == 'user')) ? 'checked' : ''; ?>>
                        <label for="user">Standard User</label>
                    </div>
                </div>
                
                <button type="submit" class="login-btn">Sign In</button>
                
                <div class="additional-links">
                    <a href="FP.php" class="forgot-password">Forgot Password?</a>
                    <div class="register-link">
                        Don't have an account? <a href="register.php">Sign Up</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add animation to input fields when focused
        document.querySelectorAll('input, select').forEach(element => {
            element.addEventListener('focus', function() {
                this.parentElement.parentElement.classList.add('focused');
            });
            
            element.addEventListener('blur', function() {
                this.parentElement.parentElement.classList.remove('focused');
            });
        });

        // Simulate a loading effect on button click
        const loginBtn = document.querySelector('.login-btn');
        const loginForm = document.querySelector('.login-form');
        
        if (loginForm) {
            loginForm.addEventListener('submit', function() {
                if (loginBtn) {
                    loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';
                    loginBtn.disabled = true;
                }
            });
        }
    </script>
</body>
</html>