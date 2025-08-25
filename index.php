<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db.php';

// Handle new sale form
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_sale'])) {
    $date = $_POST['sale_date'];
    $amount = $_POST['amount'];

    if (!empty($date) && is_numeric($amount)) {
        $stmt = $conn->prepare("INSERT INTO sales (sale_date, amount) VALUES (?, ?)");
        $stmt->bind_param("sd", $date, $amount);
        $stmt->execute();
        $stmt->close();

        header("Location: index.php?year=" . date('Y', strtotime($date)));
        exit;
    } else {
        echo "<script>alert('Invalid data! Please check your inputs.');</script>";
    }
}

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
    <title>📊 Premium Sales Analytics Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-gradient-colors"></script>
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
            background-color: var(--lighter);
            color: var(--dark);
            line-height: 1.6;
            min-height: 100vh;
            padding: 0;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        /* Modern Header */
        .dashboard-header {
            background: white;
            border-radius: 12px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            border: 1px solid var(--gray-light);
        }
        
        h1, h2, h3 {
            color: var(--darker);
            font-weight: 700;
        }
        
        h1 {
            font-size: 2.2rem;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 5px;
            letter-spacing: -0.5px;
        }
        
        .subtitle {
            color: var(--gray);
            font-weight: 400;
            font-size: 0.95rem;
        }
        
        /* Card Design */
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
            padding: 25px;
            margin-bottom: 30px;
            transition: all 0.3s ease;
            border: 1px solid var(--gray-light);
        }
        
        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--gray-light);
        }
        
        .card-title {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--darker);
        }
        
        /* Form Styles */
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--darker);
            font-size: 0.9rem;
        }
        
        input, select, button {
            font-family: 'Inter', sans-serif;
            padding: 12px 16px;
            border: 1px solid var(--gray-light);
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.2s;
            width: 100%;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }
        
        button {
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 500;
            padding: 12px 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
        }
        
        button:hover {
            background: linear-gradient(to right, var(--primary-dark), var(--primary));
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.05);
        }
        
        /* Table Styles */
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 20px 0;
            font-size: 0.9rem;
            border-radius: 8px;
            overflow: hidden;
        }
        
        th, td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid var(--gray-light);
        }
        
        th {
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            font-weight: 500;
            position: sticky;
            top: 0;
        }
        
        tr:nth-child(even) {
            background-color: var(--lighter);
        }
        
        tr:hover {
            background-color: rgba(79, 70, 229, 0.03);
        }
        
        /* Chart Containers */
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
            margin: 25px 0;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            text-align: center;
            transition: all 0.2s;
            border: 1px solid var(--gray-light);
        }
        
        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.08);
        }
        
        .stat-icon {
            font-size: 1.8rem;
            margin-bottom: 12px;
            color: var(--primary);
            opacity: 0.9;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--darker);
            margin: 8px 0;
        }
        
        .stat-label {
            color: var(--gray);
            font-size: 0.85rem;
            font-weight: 500;
        }
        
        /* Navigation Buttons */
        .nav-buttons {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin-top: 30px;
            flex-wrap: wrap;
        }
        
        .nav-btn {
            background: white;
            color: var(--primary-dark);
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            border: 1px solid var(--gray-light);
        }
        
        .nav-btn:hover {
            background: var(--primary-dark);
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        .nav-btn.active {
            background: linear-gradient(to right, var(--primary), var(--primary-dark));
            color: white;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        }
        
        /* Responsive Design */
        @media (max-width: 992px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }
        
        @media (max-width: 768px) {
            .card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .chart-container {
                height: 300px;
            }
        }
        
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .nav-buttons {
                flex-direction: column;
            }
            
            .nav-btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animated {
            animation: fadeIn 0.4s ease-out forwards;
        }
        
        .delay-1 { animation-delay: 0.1s; }
        .delay-2 { animation-delay: 0.2s; }
        .delay-3 { animation-delay: 0.3s; }
        
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(241, 245, 249, 0.5);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--primary-dark);
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-primary {
            background-color: rgba(79, 70, 229, 0.1);
            color: var(--primary);
        }
        
        .badge-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: var(--success);
        }
        
        .badge-warning {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }
        
        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
        }
        
        .tooltip .tooltip-text {
            visibility: hidden;
            width: 200px;
            background-color: var(--darker);
            color: white;
            text-align: center;
            border-radius: 6px;
            padding: 8px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.8rem;
            font-weight: normal;
        }
        
        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }
        
        /* Progress bars */
        .progress-container {
            width: 100%;
            background-color: var(--gray-light);
            border-radius: 8px;
            margin: 8px 0;
        }
        
        .progress-bar {
            height: 8px;
            border-radius: 8px;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            transition: width 0.4s ease;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header animated">
            <div>
                <h1>Sales Analytics Dashboard</h1>
                <p class="subtitle">Comprehensive insights into your sales performance</p>
            </div>
            
            <!-- Year Filter -->
            <form method="get" class="animated delay-1">
                <div class="form-group" style="min-width: 200px;">
                    <label for="year"><i class="fas fa-calendar-alt"></i> Select Year</label>
                    <select name="year" id="year" onchange="this.form.submit()">
                        <?php while($row = $yearResult->fetch_assoc()): ?>
                            <option value="<?= $row['year'] ?>" <?= ($selectedYear == $row['year']) ? 'selected' : '' ?>>
                                <?= $row['year'] ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </form>
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
            <div class="stat-card animated delay-1">
                <div class="stat-icon"><i class="fas fa-receipt"></i></div>
                <div class="stat-value"><?= $stats['total_sales'] ?? 0 ?></div>
                <div class="stat-label">Total Transactions</div>
            </div>
            <div class="stat-card animated delay-1">
                <div class="stat-icon"><i class="fas fa-peso-sign"></i></div>
                <div class="stat-value">₱<?= number_format($stats['total_amount'] ?? 0, 2) ?></div>
                <div class="stat-label">Total Revenue</div>
                <?php if ($yoyGrowth != 0): ?>
                    <div style="margin-top: 8px;">
                        <span class="badge <?= $yoyGrowth > 0 ? 'badge-success' : 'badge-warning' ?>">
                            <i class="fas fa-<?= $yoyGrowth > 0 ? 'arrow-up' : 'arrow-down' ?>"></i>
                            <?= number_format(abs($yoyGrowth), 1) ?>% YoY
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="stat-card animated delay-2">
                <div class="stat-icon"><i class="fas fa-calculator"></i></div>
                <div class="stat-value">₱<?= number_format($stats['avg_sale'] ?? 0, 2) ?></div>
                <div class="stat-label">Average Sale</div>
            </div>
            <div class="stat-card animated delay-2">
                <div class="stat-icon"><i class="fas fa-trophy"></i></div>
                <div class="stat-value">₱<?= number_format($stats['max_sale'] ?? 0, 2) ?></div>
                <div class="stat-label">Highest Sale</div>
            </div>
        </div>
        
        <!-- Add Sale Form -->
        <div class="card animated delay-1">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-plus-circle"></i> Record New Sale</h3>
            </div>
            <form method="post">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                    <div class="form-group">
                        <label for="sale_date"><i class="fas fa-calendar-day"></i> Date</label>
                        <input type="date" name="sale_date" required>
                    </div>
                    <div class="form-group">
                        <label for="amount"><i class="fas fa-peso-sign"></i> Amount (₱)</label>
                        <input type="number" step="0.01" name="amount" required>
                    </div>
                    <div class="form-group" style="align-self: flex-end;">
                        <button type="submit" name="add_sale"><i class="fas fa-save"></i> Add Sale</button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Charts Section -->
        <div class="card animated delay-2">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-line"></i> Sales Performance</h3>
            </div>
            
            <!-- Monthly Sales Chart -->
            <div class="chart-container">
                <canvas id="monthlyChart"></canvas>
            </div>
            
            <!-- Multi-Year Comparison Chart -->
            <div class="chart-container" style="margin-top: 30px;">
                <canvas id="multiYearChart"></canvas>
            </div>
        </div>
        
        <!-- Descriptive Analytics Table -->
        <div class="card animated delay-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-table"></i> Monthly Sales Breakdown (<?= $selectedYear ?>)</h3>
                <form method="post" action="export.php">
                    <input type="hidden" name="year" value="<?= $selectedYear ?>">
                    <button type="submit"><i class="fas fa-file-export"></i> Export Data</button>
                </form>
            </div>
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Total Sales (₱)</th>
                            <th>Transactions</th>
                            <th>Avg. Sale (₱)</th>
                            <th>% of Year</th>
                            <th>Trend</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT 
                                    DATE_FORMAT(sale_date, '%Y-%m') AS month, 
                                    SUM(amount) AS total,
                                    COUNT(*) as count,
                                    AVG(amount) as avg_sale
                                  FROM sales 
                                  WHERE YEAR(sale_date) = $selectedYear
                                  GROUP BY month 
                                  ORDER BY month";
                        $result = $conn->query($query);
                        
                        $labels = [];
                        $data = [];
                        $yearTotal = 0;
                        $monthlyData = [];
                        
                        while ($row = $result->fetch_assoc()) {
                            $monthlyData[$row['month']] = $row;
                            $yearTotal += $row['total'];
                        }
                        
                        // Now display with percentage
                        foreach ($monthlyData as $month => $row) {
                            $percentage = $yearTotal > 0 ? ($row['total'] / $yearTotal) * 100 : 0;
                            $trendIcon = ($percentage > 8) ? '📈' : (($percentage < 5) ? '📉' : '➡️');
                            echo "<tr>
                                    <td>" . date('F Y', strtotime($month . '-01')) . "</td>
                                    <td>₱" . number_format($row['total'], 2) . "</td>
                                    <td>{$row['count']}</td>
                                    <td>₱" . number_format($row['avg_sale'], 2) . "</td>
                                    <td>
                                        <div class='tooltip'>
                                            " . number_format($percentage, 1) . "%
                                            <div class='tooltip-text'>" . number_format($percentage, 2) . "% of annual revenue</div>
                                        </div>
                                        <div class='progress-container'>
                                            <div class='progress-bar' style='width: " . $percentage . "%'></div>
                                        </div>
                                    </td>
                                    <td style='text-align: center; font-size: 1.2em;'>{$trendIcon}</td>
                                </tr>";
                            $labels[] = date('M', strtotime($month . '-01'));
                            $data[] = $row['total'];
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Navigation -->
        <div class="nav-buttons">
            <a href="admin_dashboard.php" class="nav-btn"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
            <a href="index.php" class="nav-btn active"><i class="fas fa-chart-bar"></i> Descriptive Analytics</a>
            <a href="predictive.php" class="nav-btn"><i class="fas fa-chart-line"></i> Predictive Analytics</a>
            <a href="prescriptive.php" class="nav-btn"><i class="fas fa-lightbulb"></i> Prescriptive Analytics</a>
        </div>
    </div>
    
    <script>
        // Register plugins
        Chart.register(ChartDataLabels);
        
        // Gradient creation function
        function createLinearGradient(ctx, colors) {
            const gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, colors[0]);
            gradient.addColorStop(1, colors[1]);
            return gradient;
        }
        
        // Monthly Sales Chart with Gradient
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Monthly Sales (₱)',
                    data: <?= json_encode($data) ?>,
                    backgroundColor: createLinearGradient(monthlyCtx, ['#4F46E5', '#818CF8']),
                    borderColor: '#4F46E5',
                    borderWidth: 1,
                    borderRadius: 6,
                    hoverBackgroundColor: createLinearGradient(monthlyCtx, ['#4338CA', '#6366F1']),
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Monthly Sales for <?= $selectedYear ?>',
                        font: {
                            size: 16,
                            weight: '600'
                        },
                        padding: {
                            bottom: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₱' + context.raw.toLocaleString('en-PH');
                            }
                        }
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        formatter: (value) => '₱' + value.toLocaleString('en-PH', {maximumFractionDigits: 0}),
                        font: {
                            weight: '500',
                            size: 10
                        },
                        color: '#1F2937'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString('en-PH');
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            },
            plugins: [ChartDataLabels]
        });
        
        // Multi-Year Chart
        <?php
        $multiQuery = "SELECT YEAR(sale_date) AS year, MONTH(sale_date) AS month, SUM(amount) AS total
                       FROM sales GROUP BY year, month ORDER BY year, month";
        $multiResult = $conn->query($multiQuery);
        $yearlyData = [];
        
        while ($row = $multiResult->fetch_assoc()) {
            $year = $row['year'];
            $month = str_pad($row['month'], 2, '0', STR_PAD_LEFT);
            $yearlyData[$year]['labels'][] = $month;
            $yearlyData[$year]['data'][] = $row['total'];
        }
        ?>
        
        const multiYearCtx = document.getElementById('multiYearChart').getContext('2d');
        const chartData = {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [
                <?php
                $colorPairs = [
                    ['#4F46E5', '#818CF8'],
                    ['#EC4899', '#F472B6'],
                    ['#10B981', '#34D399'],
                    ['#F59E0B', '#FBBF24'],
                    ['#8B5CF6', '#A78BFA']
                ];
                $i = 0;
                foreach ($yearlyData as $year => $data) {
                    $filledData = array_fill(0, 12, null);
                    foreach ($data['labels'] as $index => $month) {
                        $filledData[intval($month) - 1] = $data['data'][$index];
                    }
                    echo "{
                        label: '$year',
                        data: " . json_encode($filledData) . ",
                        borderColor: '{$colorPairs[$i % count($colorPairs)][0]}',
                        backgroundColor: 'rgba(79, 70, 229, 0.05)',
                        tension: 0.3,
                        fill: true,
                        borderWidth: 2,
                        pointBackgroundColor: 'white',
                        pointBorderColor: '{$colorPairs[$i % count($colorPairs)][0]}',
                        pointRadius: 4,
                        pointHoverRadius: 6
                    },";
                    $i++;
                }
                ?>
            ]
        };
        
        new Chart(multiYearCtx, {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Year-over-Year Sales Comparison',
                        font: {
                            size: 16,
                            weight: '600'
                        },
                        padding: {
                            bottom: 15
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ₱' + context.raw.toLocaleString('en-PH');
                            }
                        }
                    },
                    datalabels: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString('en-PH');
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart'
                }
            },
            plugins: [ChartDataLabels]
        });
    </script>
</body>
</html>