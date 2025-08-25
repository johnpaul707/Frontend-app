<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <style>
        .dashboard {
            display: grid;
            grid-template-columns: 1fr;
            gap: 20px;
            max-width: 1000px;
            margin: 0 auto;
        }
        .panel {
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
        }
        .admin-panel {
            display: <?php echo ($user['user_type'] == 'admin') ? 'block' : 'none'; ?>;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <h1>Welcome, <?php echo htmlspecialchars($user['username']); ?></h1>
        
        <div class="panel">
            <h2>Descriptive Analytics</h2>
            <!-- Your descriptive analytics content here -->
        </div>
        
        <div class="panel">
            <h2>Predictive Analytics</h2>
            <!-- Your predictive analytics content here -->
        </div>
        
        <div class="panel">
            <h2>Prescriptive Analytics</h2>
            <!-- Your prescriptive analytics content here -->
        </div>
        
        <?php if ($user['user_type'] == 'admin'): ?>
        <div class="panel admin-panel">
            <h2>Admin Panel</h2>
            <h3>Pending Approvals</h3>
            <?php
            $stmt = $pdo->query("SELECT * FROM users WHERE status = 'pending'");
            while ($pending = $stmt->fetch()):
            ?>
                <div>
                    <?php echo htmlspecialchars($pending['username']); ?> - 
                    <?php echo htmlspecialchars($pending['email']); ?>
                    <a href="approve.php?id=<?php echo $pending['id']; ?>">Approve</a>
                    <a href="reject.php?id=<?php echo $pending['id']; ?>">Reject</a>
                </div>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
        
        <a href="logout.php">Logout</a>
    </div>
</body>
</html>