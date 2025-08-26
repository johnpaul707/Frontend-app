<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/db.php';

// Sanitized year selection
$selectedYear = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

// Year filter setup (distinct years with data)
$yearQuery = "SELECT DISTINCT YEAR(sale_date) AS year FROM sales ORDER BY year DESC";
$yearResult = $conn->query($yearQuery);
$availableYears = [];
if ($yearResult) {
    while ($row = $yearResult->fetch_assoc()) {
        $availableYears[] = (int)$row['year'];
    }
}
if (!in_array($selectedYear, $availableYears, true) && !empty($availableYears)) {
    $selectedYear = $availableYears[0];
}

// Stats for selected year using prepared statements
$stats = [
    'total_sales' => 0,
    'total_amount' => 0.0,
    'avg_sale' => 0.0,
    'max_sale' => 0.0,
    'min_sale' => 0.0,
];
if ($stmt = $conn->prepare("SELECT COUNT(*) as total_sales, COALESCE(SUM(amount),0) as total_amount, COALESCE(AVG(amount),0) as avg_sale, COALESCE(MAX(amount),0) as max_sale, COALESCE(MIN(amount),0) as min_sale FROM sales WHERE YEAR(sale_date) = ?")) {
    $stmt->bind_param('i', $selectedYear);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $stats = $row;
    }
    $stmt->close();
}

// Previous year YoY calculation
$prevYear = $selectedYear - 1;
$prevTotal = 0.0;
if ($stmt = $conn->prepare("SELECT COALESCE(SUM(amount),0) as total_amount FROM sales WHERE YEAR(sale_date) = ?")) {
    $stmt->bind_param('i', $prevYear);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $row = $result->fetch_assoc()) {
        $prevTotal = (float)$row['total_amount'];
    }
    $stmt->close();
}
$yoyGrowth = 0.0;
if ($prevTotal > 0) {
    $yoyGrowth = (($stats['total_amount'] - $prevTotal) / $prevTotal) * 100.0;
}

// Monthly totals for chart
$labels = [];
$data = [];
if ($stmt = $conn->prepare("SELECT DATE_FORMAT(sale_date, '%Y-%m') AS ym, SUM(amount) AS total FROM sales WHERE YEAR(sale_date) = ? GROUP BY ym ORDER BY ym")) {
    $stmt->bind_param('i', $selectedYear);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $labels[] = date('M', strtotime($row['ym'] . '-01'));
        $data[] = (float)$row['total'];
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --primary: #4F46E5;
            --primary-600: #4F46E5;
            --primary-700: #4338CA;
            --secondary: #EC4899;
            --accent: #10B981;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --bg: #F8FAFC;
            --surface: #FFFFFF;
            --surface-2: #F3F4F6;
            --text: #111827;
            --muted: #6B7280;
            --border: #E5E7EB;
            --shadow: 0 1px 3px rgba(0, 0, 0, 0.06), 0 1px 2px rgba(0, 0, 0, 0.04);
        }
        
        [data-theme="dark"] {
            --primary: #8B5CF6;
            --primary-600: #8B5CF6;
            --primary-700: #7C3AED;
            --secondary: #F472B6;
            --accent: #34D399;
            --success: #34D399;
            --warning: #F59E0B;
            --danger: #F87171;
            --bg: #0F172A;
            --surface: #111827;
            --surface-2: #1F2937;
            --text: #E5E7EB;
            --muted: #9CA3AF;
            --border: #374151;
            --shadow: 0 2px 10px rgba(0,0,0,0.35);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; }
        body {
            font-family: 'Inter', system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, 'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .dashboard-container {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            background: var(--surface);
            border-right: 1px solid var(--border);
            padding: 22px 18px;
            box-shadow: var(--shadow);
            position: sticky;
            top: 0;
            height: 100vh;
        }
        .logo { display: flex; align-items: center; gap: 10px; margin-bottom: 26px; padding-bottom: 18px; border-bottom: 1px solid var(--border); }
        .logo h1 { font-size: 1.2rem; background: linear-gradient(90deg, var(--primary), var(--secondary)); -webkit-background-clip: text; background-clip: text; color: transparent; letter-spacing: 0.3px; }
        .nav-menu { display: flex; flex-direction: column; gap: 6px; }
        .nav-item { padding: 11px 12px; border-radius: 10px; color: var(--muted); text-decoration: none; display: flex; align-items: center; gap: 10px; transition: background-color .2s, color .2s, transform .05s; user-select: none; }
        .nav-item i { width: 20px; text-align: center; }
        .nav-item:hover { background-color: rgba(79,70,229,0.08); color: var(--text); }
        .nav-item.active { background: linear-gradient(135deg, var(--primary-700), var(--primary-600)); color: #fff; box-shadow: 0 6px 12px rgba(79,70,229,0.25); }

        /* Main */
        .main-content { padding: 28px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 14px; }
        .page-title h2 { font-size: 1.6rem; font-weight: 700; margin-bottom: 4px; }
        .page-title p { color: var(--muted); font-size: 0.95rem; }
        .user-controls { display: flex; gap: 10px; align-items: center; }

        /* Buttons */
        .btn { padding: 10px 14px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: transform .05s ease, background .2s ease, box-shadow .2s ease; border: 1px solid transparent; display: inline-flex; align-items: center; gap: 8px; }
        .btn:active { transform: translateY(1px); }
        .btn-primary { background: linear-gradient(135deg, var(--primary-700), var(--primary-600)); color: #fff; box-shadow: 0 8px 16px rgba(79,70,229,0.25); }
        .btn-primary:hover { filter: brightness(1.05); }
        .btn-outline { background: transparent; color: var(--primary); border-color: var(--primary); }
        .btn-outline:hover { background: rgba(79,70,229,0.07); }
        .btn-icon { width: 40px; height: 40px; border-radius: 10px; display: inline-grid; place-items: center; }

        /* Select */
        select { padding: 10px 12px; border-radius: 10px; border: 1px solid var(--border); background: var(--surface); color: var(--text); font-family: inherit; }

        /* Cards */
        .card { background: var(--surface); border-radius: 14px; padding: 22px; margin-bottom: 22px; box-shadow: var(--shadow); border: 1px solid var(--border); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; gap: 10px; }
        .card-title { font-size: 1.05rem; font-weight: 700; display: flex; align-items: center; gap: 8px; }

        /* Grid */
        .stats-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: var(--surface); border-radius: 12px; padding: 18px; border: 1px solid var(--border); box-shadow: var(--shadow); }
        .stat-value { font-size: 1.6rem; font-weight: 800; margin: 6px 0; letter-spacing: 0.3px; }
        .stat-label { color: var(--muted); font-size: 0.85rem; }
        .delta { font-size: 0.8rem; font-weight: 600; }
        .delta.up { color: var(--success); }
        .delta.down { color: var(--danger); }

        /* Analytics buttons */
        .analytics-buttons { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; margin-bottom: 24px; }
        .analytics-btn { padding: 18px; border-radius: 12px; background: var(--surface); border: 1px solid var(--border); text-align: center; text-decoration: none; color: inherit; transition: transform .15s ease, box-shadow .2s ease, border-color .2s ease; box-shadow: var(--shadow); }
        .analytics-btn:hover { transform: translateY(-2px); border-color: var(--primary); box-shadow: 0 12px 24px rgba(79,70,229,0.18); }
        .analytics-btn i { font-size: 1.7rem; margin-bottom: 8px; background: linear-gradient(135deg, var(--primary), var(--secondary)); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .analytics-btn h3 { margin-bottom: 4px; font-size: 1.05rem; }
        .analytics-btn p { color: var(--muted); font-size: 0.9rem; }

        /* Chart */
        .chart-container { position: relative; height: 360px; width: 100%; margin: 10px 0; }

        /* Tabs */
        .admin-tabs { display: flex; border-bottom: 1px solid var(--border); margin-bottom: 16px; gap: 6px; }
        .admin-tab { padding: 10px 14px; cursor: pointer; border-bottom: 2px solid transparent; border-radius: 8px 8px 0 0; color: var(--muted); font-weight: 600; }
        .admin-tab.active { border-bottom-color: var(--primary); color: var(--primary); background: rgba(79,70,229,0.06); }
        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* Table */
        table { width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 0.92rem; }
        th, td { padding: 12px 14px; text-align: left; border-bottom: 1px solid var(--border); }
        th { background: var(--primary); color: #fff; font-weight: 600; }
        tr:hover td { background-color: rgba(79,70,229,0.05); }
        .user-actions { display: flex; gap: 6px; }
        .action-btn { padding: 6px 10px; border-radius: 8px; font-size: 0.82rem; border: none; cursor: pointer; }
        .edit-btn { background-color: var(--warning); color: #fff; }
        .delete-btn { background-color: var(--danger); color: #fff; }

        /* Config */
        .config-item { margin-bottom: 14px; padding-bottom: 14px; border-bottom: 1px solid var(--border); }
        .config-item:last-child { border-bottom: none; }
        .config-label { font-weight: 600; margin-bottom: 6px; display: block; }
        .config-input, .config-select { width: 100%; padding: 10px 12px; border: 1px solid var(--border); background: var(--surface); color: var(--text); border-radius: 10px; }
        .checkboxes { display: flex; flex-wrap: wrap; gap: 14px; }

        /* Modal */
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.45); z-index: 50; justify-content: center; align-items: center; padding: 16px; }
        .modal.open { display: flex; }
        .modal-content { background: var(--surface); border-radius: 14px; padding: 22px; width: 100%; max-width: 520px; box-shadow: var(--shadow); border: 1px solid var(--border); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
        .close-modal { background: transparent; border: none; font-size: 1.4rem; cursor: pointer; color: var(--muted); }
        .export-options { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; margin-top: 10px; }
        .export-option { padding: 16px; border: 1px solid var(--border); border-radius: 12px; text-align: center; cursor: pointer; transition: border-color .2s, background .2s; }
        .export-option:hover { border-color: var(--primary); background: rgba(79,70,229,0.06); }
        .export-option i { font-size: 1.8rem; color: var(--primary); margin-bottom: 8px; }

        /* Responsive */
        @media (max-width: 1100px) {
            .dashboard-container { grid-template-columns: 1fr; }
            .sidebar { position: static; height: auto; }
        }
        @media (max-width: 900px) {
            .analytics-buttons { grid-template-columns: 1fr; }
            .stats-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (max-width: 640px) {
            .main-content { padding: 18px; }
            .stats-grid { grid-template-columns: 1fr; }
            .header { flex-direction: column; align-items: stretch; }
            .user-controls { justify-content: space-between; }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar" aria-label="Sidebar Navigation">
            <div class="logo">
                <i class="fas fa-crown" style="font-size: 1.35rem; color: var(--primary);"></i>
                <h1>AdminDash</h1>
            </div>
            <nav class="nav-menu">
                <a href="#" class="nav-item active" data-nav="dashboard"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
                <a href="#" class="nav-item" data-nav="users"><i class="fas fa-users-cog"></i><span>User Management</span></a>
                <a href="#" class="nav-item" data-nav="config"><i class="fas fa-cogs"></i><span>System Config</span></a>
                <a href="#" class="nav-item"><i class="fas fa-chart-bar"></i><span>Analytics</span></a>
                <a href="#" class="nav-item"><i class="fas fa-file-export"></i><span>Reports</span></a>
                <a href="#" class="nav-item"><i class="fas fa-shield-alt"></i><span>Security</span></a>
                <a href="#" class="nav-item" id="logout-link"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="page-title">
                    <h2>Admin Dashboard</h2>
                    <p>Administrator control panel</p>
                </div>
                <div class="user-controls">
                    <button class="btn btn-outline btn-icon" id="theme-toggle" aria-label="Toggle Theme"><i class="fas fa-moon"></i></button>
                    <form method="get" aria-label="Year Filter">
                        <select name="year" id="year">
                            <?php foreach ($availableYears as $y): ?>
                                <option value="<?= (int)$y ?>" <?= ($selectedYear === (int)$y) ? 'selected' : '' ?>><?= (int)$y ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                    <button class="btn btn-primary" id="export-open"><i class="fas fa-file-export"></i> Export Reports</button>
                </div>
            </header>

            <section class="analytics-buttons" aria-label="Analytics Shortcuts">
                <a href="index.php" class="analytics-btn">
                    <i class="fas fa-chart-bar"></i>
                    <h3>Descriptive Analytics</h3>
                    <p>View historical data and trends</p>
                </a>
                <a href="predictive.php" class="analytics-btn">
                    <i class="fas fa-robot"></i>
                    <h3>Predictive Analytics</h3>
                    <p>Forecast future performance</p>
                </a>
                <a href="prescriptive.php" class="analytics-btn">
                    <i class="fas fa-lightbulb"></i>
                    <h3>Prescriptive Analytics</h3>
                    <p>Get actionable recommendations</p>
                </a>
            </section>

            <div class="admin-tabs" role="tablist">
                <div class="admin-tab active" role="tab" aria-selected="true" data-tab="dashboard">Dashboard</div>
                <div class="admin-tab" role="tab" aria-selected="false" data-tab="users">User Management</div>
                <div class="admin-tab" role="tab" aria-selected="false" data-tab="config">System Configurations</div>
            </div>

            <section id="dashboard" class="tab-content active" role="tabpanel" aria-label="Dashboard">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Total Transactions</div>
                        <div class="stat-value"><?= (int)($stats['total_sales'] ?? 0) ?></div>
                        <div class="stat-label"><i class="fas fa-calendar"></i> <?= (int)$selectedYear ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Total Revenue</div>
                        <div class="stat-value">₱<?= number_format((float)($stats['total_amount'] ?? 0), 2) ?></div>
                        <?php if (abs($yoyGrowth) > 0.0001): ?>
                            <div class="delta <?= $yoyGrowth > 0 ? 'up' : 'down' ?>">
                                <i class="fas fa-<?= $yoyGrowth > 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                                <?= number_format(abs($yoyGrowth), 1) ?>% vs last year
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Average Sale</div>
                        <div class="stat-value">₱<?= number_format((float)($stats['avg_sale'] ?? 0), 2) ?></div>
                        <div class="stat-label"><i class="fas fa-chart-bar"></i> Per transaction</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">System Users</div>
                        <div class="stat-value">42</div>
                        <div class="stat-label"><i class="fas fa-users"></i> Active accounts</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-chart-line"></i> Monthly Sales Performance</h3>
                        <button class="btn btn-outline"><i class="fas fa-calendar"></i> <?= (int)$selectedYear ?></button>
                    </div>
                    <div class="chart-container"><canvas id="salesChart"></canvas></div>
                </div>
            </section>

            <section id="users" class="tab-content" role="tabpanel" aria-label="User Management">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-users-cog"></i> User Management</h3>
                        <button class="btn btn-primary"><i class="fas fa-plus"></i> Add User</button>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Last Login</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>Admin User</td>
                                <td>admin@example.com</td>
                                <td><span class="badge" style="background-color: var(--primary); color: #fff; padding: 3px 8px; border-radius: 10px; font-size: 0.8rem;">Administrator</span></td>
                                <td>Today, 09:45</td>
                                <td>
                                    <div class="user-actions">
                                        <button class="action-btn edit-btn"><i class="fas fa-edit"></i> Edit</button>
                                        <button class="action-btn delete-btn"><i class="fas fa-trash"></i> Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>2</td>
                                <td>Manager User</td>
                                <td>manager@example.com</td>
                                <td><span class="badge" style="background-color: var(--success); color: #fff; padding: 3px 8px; border-radius: 10px; font-size: 0.8rem;">Manager</span></td>
                                <td>Yesterday, 14:30</td>
                                <td>
                                    <div class="user-actions">
                                        <button class="action-btn edit-btn"><i class="fas fa-edit"></i> Edit</button>
                                        <button class="action-btn delete-btn"><i class="fas fa-trash"></i> Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td>3</td>
                                <td>Staff User</td>
                                <td>staff@example.com</td>
                                <td><span class="badge" style="background-color: var(--warning); color: #fff; padding: 3px 8px; border-radius: 10px; font-size: 0.8rem;">Staff</span></td>
                                <td>2 days ago</td>
                                <td>
                                    <div class="user-actions">
                                        <button class="action-btn edit-btn"><i class="fas fa-edit"></i> Edit</button>
                                        <button class="action-btn delete-btn"><i class="fas fa-trash"></i> Delete</button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="config" class="tab-content" role="tabpanel" aria-label="System Configurations">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-cogs"></i> System Configurations</h3>
                        <button class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
                    </div>
                    <div class="config-item">
                        <label class="config-label" for="system-name">System Name</label>
                        <input id="system-name" class="config-input" type="text" value="SalesDash Admin" />
                    </div>
                    <div class="config-item">
                        <label class="config-label" for="maintenance">Maintenance Mode</label>
                        <select id="maintenance" class="config-select">
                            <option>Disabled</option>
                            <option>Enabled</option>
                        </select>
                    </div>
                    <div class="config-item">
                        <label class="config-label" for="retention">Data Retention Policy (days)</label>
                        <input id="retention" class="config-input" type="number" value="365" />
                    </div>
                    <div class="config-item">
                        <label class="config-label" for="default-role">Default User Role</label>
                        <select id="default-role" class="config-select">
                            <option>Staff</option>
                            <option>Manager</option>
                            <option>Administrator</option>
                        </select>
                    </div>
                    <div class="config-item">
                        <label class="config-label">Email Notifications</label>
                        <div class="checkboxes">
                            <label><input type="checkbox" checked /> System Alerts</label>
                            <label><input type="checkbox" checked /> User Activities</label>
                            <label><input type="checkbox" /> Marketing</label>
                        </div>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <div class="modal" id="exportModal" role="dialog" aria-modal="true" aria-labelledby="export-title">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="export-title">Export Reports</h3>
                <button class="close-modal" id="export-close" aria-label="Close">&times;</button>
            </div>
            <p>Select the format you want to export:</p>
            <div class="export-options">
                <div class="export-option" data-export="pdf"><i class="fas fa-file-pdf"></i><div>PDF Format</div></div>
                <div class="export-option" data-export="excel"><i class="fas fa-file-excel"></i><div>Excel Format</div></div>
                <div class="export-option" data-export="csv"><i class="fas fa-file-csv"></i><div>CSV Format</div></div>
                <div class="export-option" data-export="print"><i class="fas fa-print"></i><div>Print Report</div></div>
            </div>
            <div style="margin-top: 16px;">
                <button class="btn btn-primary" id="export-dismiss" style="width: 100%;"><i class="fas fa-times"></i> Close</button>
            </div>
        </div>
    </div>

    <script>
        // Theme toggle with localStorage
        (function() {
            const root = document.documentElement;
            const toggle = document.getElementById('theme-toggle');
            const stored = localStorage.getItem('theme');
            if (stored === 'dark') { root.setAttribute('data-theme', 'dark'); toggle.innerHTML = '<i class="fas fa-sun"></i>'; }
            toggle.addEventListener('click', () => {
                const current = root.getAttribute('data-theme') || 'light';
                const next = current === 'light' ? 'dark' : 'light';
                root.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
                toggle.innerHTML = next === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
                // Repaint chart to adjust grid/label colors
                if (window.__salesChart) { window.__salesChart.update(); }
            });
        })();

        // Year auto-submit
        document.getElementById('year').addEventListener('change', function() { this.form.submit(); });

        // Tabs
        document.querySelectorAll('.admin-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const name = tab.getAttribute('data-tab');
                document.querySelectorAll('.admin-tab').forEach(t => { t.classList.remove('active'); t.setAttribute('aria-selected', 'false'); });
                tab.classList.add('active');
                tab.setAttribute('aria-selected', 'true');
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                const panel = document.getElementById(name);
                if (panel) panel.classList.add('active');
            });
        });

        // Export modal
        const exportModal = document.getElementById('exportModal');
        const openExport = document.getElementById('export-open');
        const closeExport = document.getElementById('export-close');
        const dismissExport = document.getElementById('export-dismiss');
        const exportOptions = document.querySelectorAll('.export-option');
        function openExportModal() { exportModal.classList.add('open'); }
        function closeExportModal() { exportModal.classList.remove('open'); }
        openExport.addEventListener('click', openExportModal);
        closeExport.addEventListener('click', closeExportModal);
        dismissExport.addEventListener('click', closeExportModal);
        exportModal.addEventListener('click', (e) => { if (e.target === exportModal) closeExportModal(); });
        window.addEventListener('keydown', (e) => { if (e.key === 'Escape' && exportModal.classList.contains('open')) closeExportModal(); });
        exportOptions.forEach(opt => {
            opt.addEventListener('click', () => {
                const format = opt.getAttribute('data-export');
                const year = <?= json_encode((int)$selectedYear) ?>;
                if (window.Swal) {
                    Swal.fire({
                        title: 'Export',
                        text: `Exporting ${format.toUpperCase()} for year ${year}`,
                        icon: 'info',
                        confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--primary').trim()
                    });
                } else {
                    alert(`Exporting ${format.toUpperCase()} for year ${year}`);
                }
                // Implement real export here, e.g. window.location.href = `export.php?year=${year}&format=${format}`;
                closeExportModal();
            });
        });

        // Logout confirm
        document.getElementById('logout-link').addEventListener('click', (e) => {
            e.preventDefault();
            const proceed = () => { window.location.href = 'logout.php'; };
            if (window.Swal) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "You're about to log out of the system.",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#4F46E5',
                    cancelButtonColor: '#6B7280',
                    confirmButtonText: 'Yes, log me out',
                    cancelButtonText: 'Cancel',
                    reverseButtons: true
                }).then((result) => { if (result.isConfirmed) proceed(); });
            } else if (confirm('Log out now?')) {
                proceed();
            }
        });

        // Chart.js setup
        (function() {
            const ctx = document.getElementById('salesChart').getContext('2d');
            const styles = getComputedStyle(document.documentElement);
            const primary = styles.getPropertyValue('--primary').trim();
            const text = styles.getPropertyValue('--text').trim();
            const border = styles.getPropertyValue('--border').trim();

            const gradient = ctx.createLinearGradient(0, 0, 0, 360);
            gradient.addColorStop(0, primary + 'CC');
            gradient.addColorStop(1, primary + '22');

            const labels = <?= json_encode($labels) ?>;
            const data = <?= json_encode($data) ?>;

            if (window.Chart && window.ChartDataLabels) {
                Chart.register(ChartDataLabels);
            }

            const currency = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });

            window.__salesChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Sales',
                        data,
                        backgroundColor: gradient,
                        borderColor: primary,
                        borderWidth: 1,
                        borderRadius: 8,
                        hoverBackgroundColor: primary,
                        maxBarThickness: 42
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: styles.getPropertyValue('--surface').trim(),
                            titleColor: text,
                            bodyColor: text,
                            borderColor: border,
                            borderWidth: 1,
                            callbacks: {
                                label: (ctx) => currency.format(ctx.raw || 0)
                            }
                        },
                        datalabels: {
                            anchor: 'end',
                            align: 'end',
                            color: text,
                            formatter: (v) => v ? currency.format(v).replace('PHP', '₱') : '',
                            clamp: true,
                            clip: true,
                            offset: 4,
                            font: { weight: '600', size: 10 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: styles.getPropertyValue('--border').trim() },
                            ticks: {
                                color: text,
                                callback: (val) => currency.format(val).replace('PHP', '₱')
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: text }
                        }
                    }
                }
            });
        })();
    </script>
</body>
</html>