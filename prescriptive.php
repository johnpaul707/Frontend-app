<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
include 'db.php';

// Year filter setup
$yearQuery = "SELECT DISTINCT YEAR(sale_date) AS year FROM sales ORDER BY year DESC";
$yearResult = $conn->query($yearQuery);
$selectedYear = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Get monthly data for the selected year
$monthlyQuery = "SELECT 
                    MONTH(sale_date) AS month_num,
                    DATE_FORMAT(sale_date, '%M') AS month_name,
                    SUM(amount) AS total_sales,
                    COUNT(*) AS transaction_count
                 FROM sales 
                 WHERE YEAR(sale_date) = $selectedYear
                 GROUP BY month_num, month_name
                 ORDER BY month_num";
$monthlyResult = $conn->query($monthlyQuery);
$monthlyData = [];
$monthlyTotals = [];

while ($row = $monthlyResult->fetch_assoc()) {
    $monthlyData[$row['month_num']] = $row;
    $monthlyTotals[$row['month_num']] = $row['total_sales'];
}

// Calculate yearly total
$yearlyTotal = array_sum($monthlyTotals);

// Get best and worst performing months
if (!empty($monthlyData)) {
    $bestMonth = array_reduce($monthlyData, function($a, $b) {
        if ($a === null) return $b;
        return ($a['total_sales'] ?? 0) > ($b['total_sales'] ?? 0) ? $a : $b;
    });
    
    $worstMonth = array_reduce($monthlyData, function($a, $b) {
        if ($a === null) return $b;
        return ($a['total_sales'] ?? 0) < ($b['total_sales'] ?? 0) ? $a : $b;
    });
}

// Get seasonal trends (compare each month to yearly average)
$monthlyAverage = $yearlyTotal / 12;
$seasonalTrends = [];
foreach ($monthlyData as $monthNum => $data) {
    $deviation = ($data['total_sales'] - $monthlyAverage) / $monthlyAverage * 100;
    $seasonalTrends[$monthNum] = [
        'month' => $data['month_name'],
        'deviation' => $deviation,
        'trend' => $deviation > 0 ? 'above' : 'below'
    ];
}

// Get previous year data for comparison
$prevYear = $selectedYear - 1;
$prevYearQuery = "SELECT 
                    MONTH(sale_date) AS month_num,
                    SUM(amount) AS total_sales
                 FROM sales 
                 WHERE YEAR(sale_date) = $prevYear
                 GROUP BY month_num
                 ORDER BY month_num";
$prevYearResult = $conn->query($prevYearQuery);
$prevYearData = [];

while ($row = $prevYearResult->fetch_assoc()) {
    $prevYearData[$row['month_num']] = $row['total_sales'];
}

// Calculate YoY growth by month
$yoyGrowth = [];
foreach ($monthlyData as $monthNum => $data) {
    if (isset($prevYearData[$monthNum])) {
        $growth = ($data['total_sales'] - $prevYearData[$monthNum]) / $prevYearData[$monthNum] * 100;
        $yoyGrowth[$monthNum] = [
            'month' => $data['month_name'],
            'growth' => $growth,
            'trend' => $growth > 0 ? 'up' : 'down'
        ];
    }
}

// Generate recommendations based on the data
$recommendations = [];

// Recommendation based on best/worst months
if (isset($bestMonth) && isset($worstMonth)) {
    $recommendations[] = [
        'icon' => 'star',
        'title' => 'Focus on Peak Months',
        'content' => "Your best performing month was <strong>{$bestMonth['month_name']}</strong> with ₱" . number_format($bestMonth['total_sales'], 2) . " in sales. Consider increasing marketing efforts and inventory during this period.",
        'type' => 'success'
    ];
    
    $recommendations[] = [
        'icon' => 'exclamation-triangle',
        'title' => 'Improve Weak Months',
        'content' => "Your worst performing month was <strong>{$worstMonth['month_name']}</strong> with only ₱" . number_format($worstMonth['total_sales'], 2) . " in sales. Consider promotions or special events to boost sales during this time.",
        'type' => 'warning'
    ];
}

// Recommendation based on seasonal trends
foreach ($seasonalTrends as $monthNum => $trend) {
    if ($trend['deviation'] > 20) {
        $recommendations[] = [
            'icon' => 'chart-line',
            'title' => "Capitalize on {$trend['month']} Demand",
            'content' => "Sales in {$trend['month']} were " . number_format($trend['deviation'], 1) . "% above average. This indicates strong seasonal demand that you can further capitalize on.",
            'type' => 'info'
        ];
    } elseif ($trend['deviation'] < -20) {
        $recommendations[] = [
            'icon' => 'lightbulb',
            'title' => "Address {$trend['month']} Slump",
            'content' => "Sales in {$trend['month']} were " . number_format(abs($trend['deviation']), 1) . "% below average. Consider analyzing customer behavior during this period to identify improvement opportunities.",
            'type' => 'warning'
        ];
    }
}

// Recommendation based on YoY growth
foreach ($yoyGrowth as $monthNum => $growth) {
    if ($growth['growth'] > 15) {
        $recommendations[] = [
            'icon' => 'thumbs-up',
            'title' => "Continue {$growth['month']} Strategies",
            'content' => "Year-over-year growth in {$growth['month']} was " . number_format($growth['growth'], 1) . "%. Whatever you're doing is working - consider applying similar strategies to other months.",
            'type' => 'success'
        ];
    } elseif ($growth['growth'] < -10) {
        $recommendations[] = [
            'icon' => 'user-shield',
            'title' => "Review {$growth['month']} Performance",
            'content' => "Year-over-year decline in {$growth['month']} was " . number_format(abs($growth['growth']), 1) . "%. Investigate potential causes and consider corrective actions.",
            'type' => 'danger'
        ];
    }
}

// General recommendations based on overall performance
if ($yearlyTotal > 0) {
    // Calculate standard deviation to measure sales volatility
    $variance = 0.0;
    foreach ($monthlyTotals as $sales) {
        $variance += pow($sales - $monthlyAverage, 2);
    }
    $stdDev = sqrt($variance / count($monthlyTotals));
    $cv = ($stdDev / $monthlyAverage) * 100; // Coefficient of variation
    
    if ($cv > 50) {
        $recommendations[] = [
            'icon' => 'random',
            'title' => 'High Sales Volatility',
            'content' => "Your sales show high month-to-month variation (CV = " . number_format($cv, 1) . "%). Consider strategies to stabilize revenue streams throughout the year.",
            'type' => 'warning'
        ];
    }
    
    // Check if growth is slowing
    if (count($yoyGrowth) >= 3) {
        $recentGrowth = array_slice($yoyGrowth, -3);
        $growthTrend = 0;
        foreach ($recentGrowth as $growth) {
            $growthTrend += $growth['growth'];
        }
        $growthTrend /= 3;
        
        if ($growthTrend < 5 && $growthTrend > -5) {
            $recommendations[] = [
                'icon' => 'tachometer-alt',
                'title' => 'Growth Stabilizing',
                'content' => "Recent growth rates indicate your sales may be stabilizing. Consider exploring new markets or product lines to reignite growth.",
                'type' => 'info'
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 Premium Sales Prescriptive Analytics</title>
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
        
        /* Recommendation Cards */
        .recommendation-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .recommendation-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            transition: all 0.2s;
            border-left: 4px solid var(--primary);
            position: relative;
            overflow: hidden;
        }
        
        .recommendation-card.success {
            border-left-color: var(--success);
        }
        
        .recommendation-card.warning {
            border-left-color: var(--warning);
        }
        
        .recommendation-card.danger {
            border-left-color: var(--danger);
        }
        
        .recommendation-card.info {
            border-left-color: var(--primary);
        }
        
        .recommendation-card .icon {
            font-size: 1.5rem;
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .recommendation-card.success .icon {
            color: var(--success);
        }
        
        .recommendation-card.warning .icon {
            color: var(--warning);
        }
        
        .recommendation-card.danger .icon {
            color: var(--danger);
        }
        
        .recommendation-card.info .icon {
            color: var(--primary);
        }
        
        .recommendation-card h4 {
            margin-bottom: 10px;
            color: var(--darker);
            font-size: 1.1rem;
        }
        
        .recommendation-card p {
            color: var(--gray);
            font-size: 0.9rem;
            line-height: 1.5;
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
        
        .badge-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }
        
        /* Priority Matrix */
        .priority-matrix {
            display: grid;
            grid-template-columns: 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            gap: 15px;
            height: 400px;
            margin: 20px 0;
        }
        
        .quadrant {
            border-radius: 8px;
            padding: 15px;
            display: flex;
            flex-direction: column;
        }
        
        .quadrant-header {
            font-weight: 600;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .quadrant-content {
            flex: 1;
            overflow-y: auto;
            padding-right: 5px;
        }
        
        .quadrant-item {
            background: rgba(255, 255, 255, 0.7);
            padding: 8px 10px;
            margin-bottom: 8px;
            border-radius: 6px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .quadrant-1 {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .quadrant-1 .quadrant-header {
            color: var(--success);
        }
        
        .quadrant-2 {
            background: rgba(79, 70, 229, 0.1);
            border: 1px solid rgba(79, 70, 229, 0.3);
        }
        
        .quadrant-2 .quadrant-header {
            color: var(--primary);
        }
        
        .quadrant-3 {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .quadrant-3 .quadrant-header {
            color: var(--warning);
        }
        
        .quadrant-4 {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .quadrant-4 .quadrant-header {
            color: var(--danger);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header animated">
            <div>
                <h1>Prescriptive Sales Analytics</h1>
                <p class="subtitle">Actionable recommendations to optimize your sales performance</p>
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
        
        <!-- Priority Matrix -->
        <div class="card animated delay-1">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-clipboard-list"></i> Action Priority Matrix</h3>
            </div>
            <div class="priority-matrix">
                <div class="quadrant quadrant-1">
                    <div class="quadrant-header">
                        <i class="fas fa-check-circle"></i> High Impact, Easy to Implement
                    </div>
                    <div class="quadrant-content">
                        <div class="quadrant-item">
                            <i class="fas fa-bullhorn"></i> Increase marketing during peak months
                        </div>
                        <div class="quadrant-item">
                            <i class="fas fa-calendar-plus"></i> Schedule promotions before slow periods
                        </div>
                        <div class="quadrant-item">
                            <i class="fas fa-chart-line"></i> Focus on best-performing products
                        </div>
                    </div>
                </div>
                <div class="quadrant quadrant-2">
                    <div class="quadrant-header">
                        <i class="fas fa-star"></i> High Impact, Requires Effort
                    </div>
                    <div class="quadrant-content">
                        <div class="quadrant-item">
                            <i class="fas fa-users"></i> Customer retention programs
                        </div>
                        <div class="quadrant-item">
                            <i class="fas fa-box-open"></i> Product line expansion
                        </div>
                        <div class="quadrant-item">
                            <i class="fas fa-store"></i> New sales channels
                        </div>
                    </div>
                </div>
                <div class="quadrant quadrant-3">
                    <div class="quadrant-header">
                        <i class="fas fa-clock"></i> Low Impact, Easy to Implement
                    </div>
                    <div class="quadrant-content">
                        <div class="quadrant-item">
                            <i class="fas fa-envelope"></i> Email campaign optimization
                        </div>
                        <div class="quadrant-item">
                            <i class="fas fa-tags"></i> Small discount promotions
                        </div>
                        <div class="quadrant-item">
                            <i class="fas fa-bell"></i> Customer reminder system
                        </div>
                    </div>
                </div>
                <div class="quadrant quadrant-4">
                    <div class="quadrant-header">
                        <i class="fas fa-times-circle"></i> Low Impact, Hard to Implement
                    </div>
                    <div class="quadrant-content">
                        <div class="quadrant-item">
                            <i class="fas fa-globe"></i> International expansion
                        </div>
                        <div class="quadrant-item">
                            <i class="fas fa-robot"></i> Full automation systems
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Seasonal Trends Chart -->
        <div class="card animated delay-2">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-area"></i> Seasonal Trends Analysis</h3>
            </div>
            <div class="chart-container">
                <canvas id="seasonalChart"></canvas>
            </div>
        </div>
        
        <!-- Recommendations Section -->
        <div class="card animated delay-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-lightbulb"></i> Smart Recommendations for <?= $selectedYear ?></h3>
            </div>
            
            <?php if (!empty($recommendations)): ?>
                <div class="recommendation-grid">
                    <?php foreach ($recommendations as $rec): ?>
                        <div class="recommendation-card <?= $rec['type'] ?>">
                            <div class="icon">
                                <i class="fas fa-<?= $rec['icon'] ?>"></i>
                            </div>
                            <h4><?= $rec['title'] ?></h4>
                            <p><?= $rec['content'] ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; padding: 20px; color: var(--gray);">Not enough data to generate recommendations. Please add more sales data.</p>
            <?php endif; ?>
        </div>
        
        <!-- Navigation -->
        <div class="nav-buttons">
            <a href="admin_dashboard.php" class="nav-btn"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
            <a href="index.php" class="nav-btn"><i class="fas fa-chart-bar"></i> Descriptive Analytics</a>
            <a href="predictive.php" class="nav-btn"><i class="fas fa-chart-line"></i> Predictive Analytics</a>
            <a href="prescriptive.php" class="nav-btn active"><i class="fas fa-lightbulb"></i> Prescriptive Analytics</a>
        </div>
    </div>
    
    <script>
        // Register plugins
        Chart.register(ChartDataLabels);
        
        // Seasonal Trends Chart
        const seasonalCtx = document.getElementById('seasonalChart').getContext('2d');
        
        <?php
        $seasonalLabels = [];
        $seasonalData = [];
        $seasonalColors = [];
        
        if (!empty($monthlyData)) {
            foreach ($monthlyData as $monthNum => $data) {
                $seasonalLabels[] = $data['month_name'];
                $seasonalData[] = $data['total_sales'];
                
                // Determine color based on performance vs average
                if ($data['total_sales'] > $monthlyAverage) {
                    $seasonalColors[] = '#10B981'; // Green for above average
                } else {
                    $seasonalColors[] = '#EF4444'; // Red for below average
                }
            }
        }
        ?>
        
        new Chart(seasonalCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($seasonalLabels) ?>,
                datasets: [
                    {
                        label: 'Monthly Sales',
                        data: <?= json_encode($seasonalData) ?>,
                        backgroundColor: <?= json_encode($seasonalColors) ?>,
                        borderColor: '#1F2937',
                        borderWidth: 1,
                        borderRadius: 4
                    },
                    {
                        label: 'Yearly Average',
                        data: Array(<?= count($seasonalLabels) ?>).fill(<?= $monthlyAverage ?? 0 ?>),
                        borderColor: '#4F46E5',
                        borderWidth: 2,
                        borderDash: [5, 5],
                        pointRadius: 0,
                        type: 'line',
                        fill: false
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'Monthly Sales vs Yearly Average (₱<?= number_format($monthlyAverage ?? 0, 2) ?>)',
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
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += '₱' + context.parsed.y.toLocaleString('en-PH');
                                    
                                    // Add percentage difference for monthly sales
                                    if (context.datasetIndex === 0) {
                                        const diff = ((context.parsed.y - <?= $monthlyAverage ?>) / <?= $monthlyAverage ?>) * 100;
                                        label += ' (' + (diff > 0 ? '+' : '') + diff.toFixed(1) + '%)';
                                    }
                                }
                                return label;
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
        
        // Add interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Add click event to recommendation cards
            const recCards = document.querySelectorAll('.recommendation-card');
            recCards.forEach(card => {
                card.addEventListener('click', function() {
                    this.style.transform = 'scale(1.02)';
                    setTimeout(() => {
                        this.style.transform = 'scale(1)';
                    }, 200);
                });
            });
            
            // Add hover effect to priority matrix items
            const quadrantItems = document.querySelectorAll('.quadrant-item');
            quadrantItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateX(5px)';
                    this.style.boxShadow = '0 2px 5px rgba(0,0,0,0.1)';
                });
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateX(0)';
                    this.style.boxShadow = 'none';
                });
            });
        });
    </script>
</body>
</html>