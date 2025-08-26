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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f7f8fc;
            --bg-accent: #eff3ff;
            --card-bg: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: #e5e7eb;
            --shadow: 0 6px 20px rgba(16, 24, 40, 0.06);
            --primary: #2563eb;
            --approve: #16a34a;
            --reject: #dc2626;
        }

        body {
            margin: 0;
            font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, 'Apple Color Emoji', 'Segoe UI Emoji';
            color: var(--text);
            background: linear-gradient(180deg, var(--bg) 0%, var(--bg-accent) 100%);
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 20px;
            background: var(--card-bg);
            border-bottom: 1px solid var(--border);
            box-shadow: 0 1px 0 rgba(16, 24, 40, 0.04);
        }
        .brand {
            font-weight: 700;
            letter-spacing: 0.2px;
        }
        .spacer { flex: 1; }
        .user { color: var(--muted); }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 14px;
            border: 1px solid transparent;
            transition: background-color 120ms ease, color 120ms ease, border-color 120ms ease, box-shadow 120ms ease;
            cursor: pointer;
        }
        .btn:hover { box-shadow: 0 2px 8px rgba(16, 24, 40, 0.06); }
        .btn-outline {
            color: var(--text);
            background: transparent;
            border-color: var(--border);
        }
        .btn-outline:hover { background: #f1f5f9; }
        .btn-approve { background: var(--approve); color: #fff; }
        .btn-approve:hover { background: #15803d; }
        .btn-reject { background: var(--reject); color: #fff; }
        .btn-reject:hover { background: #b91c1c; }

        .dashboard {
            max-width: 1100px;
            margin: 24px auto;
            padding: 0 16px;
        }

        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .panel {
            background: var(--card-bg);
            border: 1px solid var(--border);
            padding: 20px;
            border-radius: 12px;
            box-shadow: var(--shadow);
        }
        .panel h2 {
            margin: 0 0 12px 0;
            font-size: 18px;
        }
        .panel h3 {
            margin: 12px 0 8px 0;
            font-size: 16px;
            color: var(--muted);
            font-weight: 600;
        }

        .pending-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid var(--border);
        }
        .pending-item:last-child { border-bottom: none; }
        .pending-item .info { display: flex; flex-direction: column; }
        .pending-item .name { font-weight: 600; }
        .pending-item .email { color: var(--muted); font-size: 13px; }
        .actions { display: inline-flex; gap: 8px; }

        .admin-panel {
            display: <?php echo ($user['user_type'] == 'admin') ? 'block' : 'none'; ?>;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0b1220;
                --bg-accent: #0b1220;
                --card-bg: #0e1628;
                --text: #e2e8f0;
                --muted: #94a3b8;
                --border: #1f2a44;
                --shadow: 0 6px 20px rgba(0, 0, 0, 0.35);
            }
            .btn-outline { border-color: #29334d; color: #e2e8f0; }
            .btn-outline:hover { background: #162033; }
        }

        @media (max-width: 640px) {
            .user { display: none; }
        }
    </style>
</head>
<body>
    <header class="topbar">
        <div class="brand">Dashboard</div>
        <div class="spacer"></div>
        <div class="user">Signed in as <?php echo htmlspecialchars($user['username']); ?></div>
        <a class="btn btn-outline" href="logout.php">Logout</a>
    </header>
    <main class="dashboard">
        <section class="analytics-grid">
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
        </section>

        <?php if ($user['user_type'] == 'admin'): ?>
        <section class="panel admin-panel">
            <h2>Admin Panel</h2>
            <h3>Pending Approvals</h3>
            <?php
            $stmt = $pdo->query("SELECT * FROM users WHERE status = 'pending'");
            while ($pending = $stmt->fetch()):
            ?>
                <div class="pending-item">
                    <div class="info">
                        <div class="name"><?php echo htmlspecialchars($pending['username']); ?></div>
                        <div class="email"><?php echo htmlspecialchars($pending['email']); ?></div>
                    </div>
                    <div class="actions">
                        <a class="btn btn-approve" href="approve.php?id=<?php echo $pending['id']; ?>">Approve</a>
                        <a class="btn btn-reject" href="reject.php?id=<?php echo $pending['id']; ?>">Reject</a>
                    </div>
                </div>
            <?php endwhile; ?>
        </section>
        <?php endif; ?>
    </main>
</body>
</html>