<?php
session_start();
include 'db.php';

// Handle year selection
$yearQuery = "SELECT DISTINCT YEAR(sale_date) AS year FROM sales ORDER BY year DESC";
$yearResult = $conn->query($yearQuery);
$selectedYear = isset($_GET['year']) ? $_GET['year'] : date("Y");

$monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$salesByMonth = array_fill(0, 12, 0);

// Fetch sales data
$result = $conn->query("SELECT MONTH(sale_date) AS month, SUM(amount) AS total FROM sales WHERE YEAR(sale_date) = $selectedYear GROUP BY MONTH(sale_date)");
while ($row = $result->fetch_assoc()) {
    $index = (int)$row['month'] - 1;
    $salesByMonth[$index] = (float)$row['total'];
}

// Linear regression prediction
$months = [];
$sales = [];
for ($i = 0; $i < 12; $i++) {
    if ($salesByMonth[$i] > 0) {
        $months[] = $i + 1;
        $sales[] = $salesByMonth[$i];
    }
}

if (count($months) >= 2) {
    $n = count($months);
    $sumX = array_sum($months);
    $sumY = array_sum($sales);
    $sumXY = 0;
    $sumX2 = 0;

    for ($i = 0; $i < $n; $i++) {
        $sumXY += $months[$i] * $sales[$i];
        $sumX2 += $months[$i] * $months[$i];
    }

    $b = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    $a = ($sumY - $b * $sumX) / $n;

    $nextMonth = $n + 1;
    $predictedSales = $a + $b * $nextMonth;
    $_SESSION['predicted_sales'] = $predictedSales;
    
    // Generate prediction line data
    $predictionLine = [];
    for ($i = 1; $i <= 13; $i++) {
        $predictionLine[] = $a + $b * $i;
    }
} else {
    $predictedSales = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📈 Predictive Analytics Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-annotation@1.0.2"></script>
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
        
        /* Prediction Card */
        .prediction-card {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            margin: 25px 0;
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.15);
        }
        
        .prediction-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 10px 0;
        }
        
        /* Chart Containers */
        .chart-container {
            position: relative;
            height: 400px;
            width: 100%;
            margin: 25px 0;
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
        
        .badge-warning {
            background-color: rgba(245, 158, 11, 0.1);
            color: var(--warning);
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
            .chart-container {
                height: 300px;
            }
        }
        
        @media (max-width: 576px) {
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
    </style>
</head>
<body>
    <div class="container">
        <!-- Dashboard Header -->
        <div class="dashboard-header animated">
            <div>
                <h1>📈 Predictive Analytics</h1>
                <p class="subtitle">Forecast future sales based on historical trends</p>
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
        
        <?php if ($predictedSales !== null): ?>
            <div class="prediction-card animated delay-1">
                <h3><i class="fas fa-chart-line"></i> Next Month Forecast</h3>
                <p>Based on sales trend from <?= $monthLabels[$months[0]-1] ?> to <?= $monthLabels[$months[count($months)-1]-1] ?></p>
                <div class="prediction-value">₱<?= number_format($predictedSales, 2) ?></div>
                <p>Predicted sales for <?= $nextMonth <= 12 ? $monthLabels[$nextMonth-1] : 'Next Year' ?></p>
            </div>
        <?php else: ?>
            <div class="card animated delay-1" style="text-align: center; background-color: rgba(239, 68, 68, 0.05); border-color: rgba(239, 68, 68, 0.2);">
                <h3><i class="fas fa-exclamation-triangle"></i> Insufficient Data</h3>
                <p>We need at least 2 months of data to generate predictions</p>
            </div>
        <?php endif; ?>
        
        <!-- Sales Prediction Chart -->
        <div class="card animated delay-2">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-chart-bar"></i> Sales Forecast</h3>
            </div>
            <div class="chart-container">
                <canvas id="predictiveChart"></canvas>
            </div>
        </div>
        
        <?php if ($predictedSales !== null): ?>
        <!-- Trend Analysis Chart -->
        <div class="card animated delay-3">
            <div class="card-header">
                <h3 class="card-title"><i class="fas fa-project-diagram"></i> Trend Analysis</h3>
            </div>
            <div class="chart-container">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Navigation -->
        <div class="nav-buttons">
            <a href="admin_dashboard.php" class="nav-btn"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
            <a href="index.php" class="nav-btn"><i class="fas fa-chart-bar"></i> Descriptive</a>
            <a href="predictive.php" class="nav-btn active"><i class="fas fa-chart-line"></i> Predictive</a>
            <a href="prescriptive.php" class="nav-btn"><i class="fas fa-lightbulb"></i> Prescriptive</a>
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
        
        // Main Predictive Chart (Bar + Prediction)
        const ctx = document.getElementById('predictiveChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($monthLabels) ?>,
                datasets: [
                    {
                        label: 'Actual Sales',
                        data: <?= json_encode($salesByMonth) ?>,
                        backgroundColor: createLinearGradient(ctx, ['#4F46E5', '#818CF8']),
                        borderColor: '#4F46E5',
                        borderWidth: 1,
                        borderRadius: 6
                    },
                    <?php if ($predictedSales !== null): ?>
                    {
                        label: 'Predicted',
                        data: [...Array(11).fill(null), <?= $predictedSales ?>],
                        backgroundColor: createLinearGradient(ctx, ['#10B981', '#34D399']),
                        borderColor: '#10B981',
                        borderWidth: 1,
                        borderRadius: 6
                    }
                    <?php endif; ?>
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
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.dataset.label + ': ₱' + context.raw.toLocaleString('en-PH');
                            }
                        }
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        formatter: (value) => value ? '₱' + value.toLocaleString('en-PH', {maximumFractionDigits: 0}) : '',
                        font: {
                            weight: '500',
                            size: 10
                        },
                        color: '#1F2937'
                    },
                    annotation: {
                        annotations: {
                            line1: {
                                type: 'line',
                                yMin: <?= $predictedSales ?? 0 ?>,
                                yMax: <?= $predictedSales ?? 0 ?>,
                                borderColor: 'rgba(239, 68, 68, 0.7)',
                                borderWidth: 2,
                                borderDash: [6, 6],
                                label: {
                                    content: 'Prediction Threshold',
                                    enabled: true,
                                    position: 'right',
                                    backgroundColor: 'rgba(239, 68, 68, 0.7)',
                                    color: 'white',
                                    font: {
                                        weight: 'bold'
                                    }
                                }
                            }
                        }
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
        
        <?php if ($predictedSales !== null): ?>
        // Trend Line Chart
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: [...<?= json_encode(array_slice($monthLabels, 0, count($months))) ?>, 'Prediction'],
                datasets: [
                    {
                        label: 'Actual Sales',
                        data: <?= json_encode($sales) ?>,
                        borderColor: '#4F46E5',
                        backgroundColor: 'rgba(79, 70, 229, 0.05)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.3,
                        pointBackgroundColor: 'white',
                        pointBorderColor: '#4F46E5',
                        pointRadius: 5,
                        pointHoverRadius: 7
                    },
                    {
                        label: 'Trend Line',
                        data: <?= json_encode($predictionLine) ?>,
                        borderColor: '#EC4899',
                        backgroundColor: 'rgba(236, 72, 153, 0.05)',
                        borderWidth: 3,
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0,
                        pointBackgroundColor: 'white',
                        pointBorderColor: '#EC4899',
                        pointRadius: 5,
                        pointHoverRadius: 7
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
                        display: false
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
                        beginAtZero: false,
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
        <?php endif; ?>
    </script>
</body>
</html>