<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'johnpaulproject');

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Check if token is valid and not expired
    $stmt = $conn->prepare("SELECT user_id FROM password_resets WHERE token = ? AND expires_at > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows != 1) {
        $error = "Invalid or expired token";
        header("Location: FP.php?error=invalid_token");
        exit();
    }
    
    $reset_data = $result->fetch_assoc();
    $_SESSION['reset_user_id'] = $reset_data['user_id'];
    $_SESSION['reset_token'] = $token;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['reset_user_id'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match";
    } else {
        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $_SESSION['reset_user_id']);
        
        if ($stmt->execute()) {
            // Delete the used token
            $conn->query("DELETE FROM password_resets WHERE token = '".$_SESSION['reset_token']."'");
            
            $_SESSION['password_reset'] = true;
            header("Location: login.php?reset=success");
            exit();
        } else {
            $error = "Error updating password";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - John Paul Project</title>
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

        .reset-container {
            width: 100%;
            max-width: 500px;
            background: #fff;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .reset-header {
            margin-bottom: 30px;
        }

        .reset-header h2 {
            font-size: 2.2rem;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .reset-header p {
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
            text-align: left;
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

        .update-btn {
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

        .update-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(37, 117, 252, 0.6);
        }

        .update-btn:active {
            transform: translateY(0);
        }

        .back-to-login {
            text-align: center;
            margin-top: 25px;
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

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 576px) {
            .reset-container {
                padding: 30px 20px;
            }
            
            .reset-header h2 {
                font-size: 1.8rem;
            }
            
            .app-logo {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <?php if (!isset($_SESSION['reset_user_id'])): ?>
        <div class="reset-container">
            <div class="error-message show">
                <i class="fas fa-exclamation-circle"></i> Invalid or expired password reset link
            </div>
            <div class="back-to-login">
                <a href="FP.php"><i class="fas fa-arrow-left"></i> Request new reset link</a>
            </div>
        </div>
    <?php else: ?>
        <div class="reset-container">
            <div class="reset-header">
                <div class="app-logo">
                    <i class="fas fa-key"></i>
                </div>
                <h2>Reset Your Password</h2>
                <p>Create a new password for your account</p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error-message show">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="new_password" id="new_password" placeholder="Enter new password" required>
                    </div>
                    <div id="password-strength" class="password-strength"></div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required>
                    </div>
                    <div id="password-match" class="password-strength"></div>
                </div>
                
                <button type="submit" class="update-btn">Update Password</button>
                
                <div class="back-to-login">
                    <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <script>
        // Password strength indicator
        const passwordInput = document.getElementById('new_password');
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
        const updateBtn = document.querySelector('.update-btn');
        if (updateBtn) {
            updateBtn.addEventListener('click', function() {
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            });
        }
    </script>
</body>
</html>