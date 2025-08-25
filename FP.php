<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'johnpaulproject');

// Function to generate random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $token = generateToken();
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));
        
        // Store token in database
        $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user['id'], $token, $expires);
        
        if ($stmt->execute()) {
            // Send email with reset link (in a real application)
            $reset_link = "http://".$_SERVER['HTTP_HOST']."/reset_password.php?token=".$token;
            
            // For demo purposes, we'll just show the link
            $_SESSION['reset_link'] = $reset_link;
            $_SESSION['email_sent'] = $email;
            header("Location: FP.php?sent=1");
            exit();
        } else {
            $error = "Error generating reset token";
        }
    } else {
        $error = "Email not found in our system";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - John Paul Project</title>
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

        .forgot-container {
            width: 100%;
            max-width: 500px;
            background: #fff;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .forgot-header {
            margin-bottom: 30px;
        }

        .forgot-header h2 {
            font-size: 2.2rem;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .forgot-header p {
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

        .reset-btn {
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

        .reset-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(37, 117, 252, 0.6);
        }

        .reset-btn:active {
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

        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.95rem;
            display: none;
        }

        .success-message.show {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        .demo-reset-link {
            margin-top: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 8px;
            word-break: break-all;
        }

        .demo-reset-link a {
            color: #3498db;
            text-decoration: none;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 576px) {
            .forgot-container {
                padding: 30px 20px;
            }
            
            .forgot-header h2 {
                font-size: 1.8rem;
            }
            
            .app-logo {
                font-size: 3rem;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <div class="app-logo">
                <i class="fas fa-key"></i>
            </div>
            <h2>Forgot Password?</h2>
            <p>Enter your email to reset your password</p>
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error-message show">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['sent']) && isset($_SESSION['email_sent'])): ?>
            <div class="success-message show">
                <i class="fas fa-check-circle"></i> Password reset link sent to <?php echo $_SESSION['email_sent']; ?>
            </div>
            
            <!-- For demo purposes, show the reset link -->
            <div class="demo-reset-link">
                <p>Demo reset link (in real app this would be emailed):</p>
                <a href="<?php echo $_SESSION['reset_link']; ?>"><?php echo $_SESSION['reset_link']; ?></a>
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" name="email" id="email" placeholder="Enter your email" required>
                </div>
            </div>
            
            <button type="submit" class="reset-btn">Send Reset Link</button>
            
            <div class="back-to-login">
                <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
            </div>
        </form>
    </div>

    <script>
        // Form submission animation
        const resetBtn = document.querySelector('.reset-btn');
        if (resetBtn) {
            resetBtn.addEventListener('click', function() {
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
                setTimeout(() => {
                    this.innerHTML = 'Send Reset Link';
                }, 2000);
            });
        }
    </script>
</body>
</html>