<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['year'])) {
    $year = $_POST['year'];

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="sales_' . $year . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Month', 'Total Sales']);

    $query = "SELECT DATE_FORMAT(sale_date, '%Y-%m') AS month, SUM(amount) AS total
              FROM sales WHERE YEAR(sale_date) = $year
              GROUP BY month ORDER BY month";
    $result = $conn->query($query);

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [$row['month'], $row['total']]);
    }

    fclose($output);
    exit;
}
?>