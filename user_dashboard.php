<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db.php';

// Year filter setup
$yearQuery = "SELECT DISTINCT YEAR(sale_date) AS year FROM sales ORDER BY year DESC";
$yearResult = $conn->query($yearQuery);
$selectedYear = isset($_GET['year']) ? $_GET['year'] : date('Y');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>👤 User Sales Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #4F46E5;
            --primary-dark: #4338CA;
            --secondary: #EC4899;
            --accent: #10B981;
            --success: #10B981;
            --warning: #F59E0B;
            --danger: #EF4444;
            --dark: #1F2937;
            --darker: #111827;
            --light: #F9FAFB;
            --lighter: #F3F4F6;
            --gray: #6B7280;
            --gray-light: #E5E7EB;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            color: var(--dark);
            line-height: 1.6;
        }
        
        .dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            background: white;
            border-right: 1px solid var(--gray-light);
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--gray-light);
        }
        
        .logo h1 {
            font-size: 1.3rem;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .nav-menu {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .nav-item {
            padding: 12px 15px;
            border-radius: 8px;
            color: var(--gray);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
        }
        
        .nav-item:hover, .nav-item.active {
            background-color: var(--primary);
            color: white;
        }
        
        .nav-item i {
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main-content {
            padding: 30px;
            background-color: #f8fafc;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .page-title h2 {
            font-size: 1.8rem;
            color: var(--darker);
            margin-bottom: 5px;
        }
        
        .page-title p {
            color: var(--gray);
            font-size: 0.9rem;
        }
        
        .user-controls {
            display: flex;
            gap: 15px;
        }
        
        /* Card Styles */
        .card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--gray-light);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--darker);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--gray-light);
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--darker);
            margin: 5px 0;
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.85rem;
        }
        
        /* Chart Containers */
        .chart-container {
            position: relative;
            height: 350px;
            width: 100%;
            margin: 20px 0;
        }
        
        /* Table Styles */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 0.9rem;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid var(--gray-light);
        }
        
        th {
            background-color: var(--primary);
            color: white;
            font-weight: 500;
        }
        
        tr:hover {
            background-color: rgba(79, 70, 229, 0.03);
        }
        
        /* Button Styles */
        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--primary));
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        
        .btn-outline {
            background: white;
            color: var(--primary);
            border: 1px solid var(--primary);
        }
        
        .btn-outline:hover {
            background: var(--lighter);
        }
        
        /* Form Styles */
        select {
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid var(--gray-light);
            font-family: 'Inter', sans-serif;
        }
        
        /* Export Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--gray);
        }
        
        .export-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }
        
        .export-option {
            padding: 15px;
            border: 1px solid var(--gray-light);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .export-option:hover {
            border-color: var(--primary);
            background: rgba(79, 70, 229, 0.05);
        }
        
        .export-option i {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            
            .sidebar {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
        
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .main-content {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <div class="sidebar">
            <div class="logo">
                <i class="fas fa-chart-pie" style="font-size: 1.5rem; color: var(--primary);"></i>
                <h1>SalesDash</h1>
            </div>
            
            <nav class="nav-menu">
                <a href="#" class="nav-item active">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Sales</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-users"></i>
                    <span>Customers</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-box-open"></i>
                    <span>Products</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-file-export"></i>
                    <span>Reports</span>
                </a>
                <a href="#" class="nav-item">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
               <!-- Add this right after your other script includes in the head section -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- Replace your existing logout link with this one -->
<a href="#" class="nav-item" onclick="confirmLogout()">
    <i class="fas fa-sign-out-alt"></i>
    <span>Logout</span>
</a>

<!-- Add this JavaScript function at the bottom of your script section, before the closing body tag -->
<script>
function confirmLogout() {
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
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'logout.php';
        }
    });
}
</script>
</a>
            </nav>
        </div>
        
        <!-- Main Content Area -->
        <div class="main-content">
            <div class="header">
                <div class="page-title">
                    <h2>User Sales Dashboard</h2>
                    <p>Welcome back! Here's your sales overview</p>
                </div>
                
                <div class="user-controls">
                    <!-- Year Filter -->
                    <form method="get">
                        <select name="year" id="year" onchange="this.form.submit()">
                            <?php while($row = $yearResult->fetch_assoc()): ?>
                                <option value="<?= $row['year'] ?>" <?= ($selectedYear == $row['year']) ? 'selected' : '' ?>>
                                    <?= $row['year'] ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </form>
                    
                    <!-- Export Button -->
                    <button class="btn btn-primary" onclick="openExportModal()">
                        <i class="fas fa-file-export"></i> Export Reports
                    </button>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <?php
            $statsQuery = "SELECT 
                            COUNT(*) as total_sales,
                            SUM(amount) as total_amount,
                            AVG(amount) as avg_sale,
                            MAX(amount) as max_sale,
                            MIN(amount) as min_sale
                          FROM sales 
                          WHERE YEAR(sale_date) = $selectedYear";
            $statsResult = $conn->query($statsQuery);
            $stats = $statsResult->fetch_assoc();
            
            // Calculate YoY growth if we have previous year data
            $prevYear = $selectedYear - 1;
            $prevYearQuery = "SELECT SUM(amount) as total_amount FROM sales WHERE YEAR(sale_date) = $prevYear";
            $prevYearResult = $conn->query($prevYearQuery);
            $prevYearStats = $prevYearResult->fetch_assoc();
            $yoyGrowth = 0;
            
            if ($prevYearStats['total_amount'] > 0) {
                $yoyGrowth = (($stats['total_amount'] - $prevYearStats['total_amount']) / $prevYearStats['total_amount']) * 100;
            }
            ?>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Transactions</div>
                    <div class="stat-value"><?= $stats['total_sales'] ?? 0 ?></div>
                    <div style="color: var(--gray); font-size: 0.8rem;">
                        <i class="fas fa-calendar"></i> <?= $selectedYear ?>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Revenue</div>
                    <div class="stat-value">₱<?= number_format($stats['total_amount'] ?? 0, 2) ?></div>
                    <?php if ($yoyGrowth != 0): ?>
                        <div style="font-size: 0.8rem; color: <?= $yoyGrowth > 0 ? 'var(--success)' : 'var(--danger)' ?>;">
                            <i class="fas fa-<?= $yoyGrowth > 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                            <?= number_format(abs($yoyGrowth), 1) ?>% vs last year
                        </div>
                    <?php endif; ?>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Average Sale</div>
                    <div class="stat-value">₱<?= number_format($stats['avg_sale'] ?? 0, 2) ?></div>
                    <div style="color: var(--gray); font-size: 0.8rem;">
                        <i class="fas fa-chart-bar"></i> Per transaction
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Highest Sale</div>
                    <div class="stat-value">₱<?= number_format($stats['max_sale'] ?? 0, 2) ?></div>
                    <div style="color: var(--gray); font-size: 0.8rem;">
                        <i class="fas fa-trophy"></i> Peak performance
                    </div>
                </div>
            </div>
            
            <!-- Main Chart -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-chart-line"></i> Monthly Sales Performance</h3>
                    <div>
                        <button class="btn btn-outline">
                            <i class="fas fa-calendar"></i> <?= $selectedYear ?>
                        </button>
                    </div>
                </div>
                
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
            
            <!-- Recent Sales Table -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-receipt"></i> Recent Sales</h3>
                    <button class="btn btn-outline">
                        <i class="fas fa-eye"></i> View All
                    </button>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recentQuery = "SELECT * FROM sales 
                                        WHERE YEAR(sale_date) = $selectedYear
                                        ORDER BY sale_date DESC 
                                        LIMIT 5";
                        $recentResult = $conn->query($recentQuery);
                        
                        while($row = $recentResult->fetch_assoc()):
                            $status = $row['amount'] > 5000 ? 'High Value' : 'Standard';
                            $statusColor = $row['amount'] > 5000 ? 'var(--success)' : 'var(--gray)';
                        ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($row['sale_date'])) ?></td>
                            <td>₱<?= number_format($row['amount'], 2) ?></td>
                            <td style="color: <?= $statusColor ?>"><?= $status ?></td>
                            <td>
                                <button class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;">
                                    <i class="fas fa-search"></i> Details
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Export Reports Modal -->
    <div class="modal" id="exportModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Export Reports</h3>
                <button class="close-modal" onclick="closeExportModal()">&times;</button>
            </div>
            
            <p>Select the format you want to export:</p>
            
            <div class="export-options">
                <div class="export-option" onclick="exportReport('pdf')">
                    <i class="fas fa-file-pdf"></i>
                    <div>PDF Format</div>
                </div>
                <div class="export-option" onclick="exportReport('excel')">
                    <i class="fas fa-file-excel"></i>
                    <div>Excel Format</div>
                </div>
                <div class="export-option" onclick="exportReport('csv')">
                    <i class="fas fa-file-csv"></i>
                    <div>CSV Format</div>
                </div>
                <div class="export-option" onclick="exportReport('print')">
                    <i class="fas fa-print"></i>
                    <div>Print Report</div>
                </div>
            </div>
            
            <div style="margin-top: 20px;">
                <button class="btn btn-primary" style="width: 100%;" onclick="closeExportModal()">
                    <i class="fas fa-times"></i> Close
                </button>
            </div>
        </div>
    </div>
    
    <script>
        // Monthly Sales Chart
        <?php
        $query = "SELECT 
                    DATE_FORMAT(sale_date, '%Y-%m') AS month, 
                    SUM(amount) AS total
                  FROM sales 
                  WHERE YEAR(sale_date) = $selectedYear
                  GROUP BY month 
                  ORDER BY month";
        $result = $conn->query($query);
        
        $labels = [];
        $data = [];
        
        while ($row = $result->fetch_assoc()) {
            $labels[] = date('M', strtotime($row['month'] . '-01'));
            $data[] = $row['total'];
        }
        ?>
        
        const ctx = document.getElementById('salesChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Sales (₱)',
                    data: <?= json_encode($data) ?>,
                    backgroundColor: '#4F46E5',
                    borderRadius: 6,
                    hoverBackgroundColor: '#4338CA',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.raw.toLocaleString('en-PH');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString('en-PH');
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        // Export Modal Functions
        function openExportModal() {
            document.getElementById('exportModal').style.display = 'flex';
        }
        
        function closeExportModal() {
            document.getElementById('exportModal').style.display = 'none';
        }
        
        function exportReport(format) {
            alert(`Exporting report as ${format.toUpperCase()} for year <?= $selectedYear ?>`);
            // In a real implementation, this would redirect to an export script
            // window.location.href = `export.php?year=<?= $selectedYear ?>&format=${format}`;
            closeExportModal();
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('exportModal');
            if (event.target == modal) {
                closeExportModal();
            }
        }
    </script>
</body>
</html>