<?php
session_start();
include('config.php');

try {
    $connection = new mysqli($hostname, $username, $password, $database);

    if ($connection->connect_error) {
        throw new Exception("Error: " . $connection->connect_error);
    }
} catch (Exception $e) {
    exit($e->getMessage());
}

// Disable safe update mode (for this operation)
$disableSafeUpdateQuery = "SET SQL_SAFE_UPDATES = 0;";
$connection->query($disableSafeUpdateQuery);

$Username = $_SESSION['Username'];

// Use the sales_pilot database
$useDatabaseQuery = "USE sales_pilot";
$connection->query($useDatabaseQuery);

// Get the current date
$currentDate = date('Y-m-d');
// Calculate week start date as last Sunday
$weekStartDate = date('Y-m-d', strtotime('last Sunday', strtotime($currentDate)));

$sql = "CREATE TEMPORARY TABLE temp_weekly_sales AS
        SELECT Product, SUM(DailySalesQty) AS WeeklySalesQty
        FROM sales_records
        WHERE Date >= '$weekStartDate' AND Date <= '$currentDate'
        GROUP BY Product";

$connection->query($sql);

$sql = "UPDATE sales_records AS sr
        JOIN temp_weekly_sales AS temp
        ON sr.Product = temp.Product
        SET sr.WeeklySalesQty = temp.WeeklySalesQty";

$connection->query($sql);

$sql = "DROP TEMPORARY TABLE IF EXISTS temp_weekly_sales";

$connection->query($sql);

// Get the current date
$currentDate = date('Y-m-d');
// Calculate week start date as last Sunday
$weekStartDate = date('Y-m-d', strtotime('last Sunday', strtotime($currentDate)));

// Function to fetch and calculate totals
function fetchAndCalculateTotals($connection, $Username) {
    $stmt = $connection->prepare('SELECT COUNT(*) AS TotalProduct, SUM(TotalRevenue) AS TotalRevenue, SUM(InventoryValue) AS TotalInventoryValue FROM sales_records WHERE Username = ?');
    $stmt->bind_param('s', $Username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return [
            'TotalProduct' => $row['TotalProduct'],
            'TotalRevenue' => $row['TotalRevenue'],
            'TotalInventoryValue' => $row['TotalInventoryValue'],
        ];
    } else {
        return [
            'TotalProduct' => 0,
            'TotalRevenue' => 0,
            'TotalInventoryValue' => 0,
        ];
    }
}

// Fetch and calculate totals
$totalData = fetchAndCalculateTotals($connection, $Username);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    // Handle the form submission for updating inventory
    $salesrecordIds = $_POST['id_salesrecords'];
    $products = $_POST['Product'];
    $costPrices = $_POST['CostPrice'];
    $inventoryValues = $_POST['InventoryValue'];
    $salesPrices = $_POST['SalesPrice'];
    $totalRevenues = $_POST['TotalRevenue'];

    for ($i = 0; $i < count($salesrecordIds); $i++) {
        $stmt = $connection->prepare("UPDATE sales_records SET Product = ?, CostPrice = ?, InventoryValue = ?, SalesPrice = ?, TotalRevenue = ? WHERE id_salesrecords = ?");
        $stmt->bind_param('ssdddi', $products[$i], $costPrices[$i], $inventoryValues[$i], $salesPrices[$i], $totalRevenues[$i], $salesrecordIds[$i]);
        $stmt->execute();
    }

    // Redirect to the same page to avoid form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch user records based on their Username
$stmt = $connection->prepare('SELECT * FROM sales_records WHERE Username = ?');
$stmt->bind_param('s', $Username);
$stmt->execute();
$result = $stmt->get_result();

// Function to fetch products with highest values
function fetchProductWithHighestValue($connection, $Username, $valueType) {
    $valueColumn = ($valueType === 'sales') ? 'TotalRevenue' : 'InventoryValue';
    $stmt = $connection->prepare("SELECT Product FROM sales_records WHERE Username = ? ORDER BY $valueColumn DESC LIMIT 1");
    $stmt->bind_param('s', $Username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['Product'];
    } else {
        return 'No products found';
    }
}

// Fetch products with highest values
$HighestSalesValue = fetchProductWithHighestValue($connection, $Username, 'sales');
$HighestInventoryValue = fetchProductWithLowestValue($connection, $Username, 'inventory');

// Function to fetch products with lowest values
function fetchProductWithLowestValue($connection, $Username, $valueType) {
    $valueColumn = ($valueType === 'sales') ? 'TotalRevenue' : 'InventoryValue';
    $stmt = $connection->prepare("SELECT Product FROM sales_records WHERE Username = ? ORDER BY $valueColumn ASC LIMIT 1");
    $stmt->bind_param('s', $Username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['Product'];
    } else {
        return 'No products found';
    }
}

// Fetch products with lowest sales values
$LowestSalesValue = fetchProductWithLowestValue($connection, $Username, 'sales');
$LowestInventoryValue = fetchProductWithLowestValue($connection, $Username, 'inventory');

// Initialize start and end date variables
$startDate = isset($_GET['startDate']) ? $_GET['startDate'] : '';
$endDate = isset($_GET['endDate']) ? $_GET['endDate'] : '';

// Define SQL date range condition
$dateCondition = '';
if (!empty($startDate) && !empty($endDate)) {
    $dateCondition = "AND Date BETWEEN '$startDate' AND '$endDate'";
}

// Fetch user records based on their Username
$stmt2 = $connection->prepare('SELECT * FROM sales_records WHERE Username = ?');
$stmt2->bind_param('s', $Username);
$stmt2->execute();
$result2 = $stmt2->get_result();

// Fetch data for the chart (e.g., products and net profit)
$data = array();
while ($row = $result2->fetch_assoc()) {
    $data['Products'][] = $row['Product'];
    $data['GrossProfitMargin'][] = $row['GrossProfitMargin'];
    $data['NetProfitMargin'][] = $row['NetProfitMargin'];
    $data['InventoryToSalesRatio'][] = $row['InventoryToSalesRatio'];
    $data['ReturnOnInvestment'][] = $row['ReturnOnInvestment'];
}

// Re-enable safe update mode (recommended for security)
$enableSafeUpdateQuery = "SET SQL_SAFE_UPDATES = 1;";
$connection->query($enableSafeUpdateQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard</title>
</head>
<style>
    /* styles.css */

    body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
    }

    main tr th {
        color: #456fec;
    }

    main .dashboard-container .dashboard-card {
        position: relative;
        top: 35px;
        left: -16px;
        width:406px !important;
        background-image:linear-gradient(to right, #4ca1af 0%, #c4e0e5 100%);
        transform:translatex(382px) translatey(-75px) !important;
    }

    header {
        background-image: linear-gradient(to right, #108dc7 0%, #ef8e38 100%);
        color: #fff;
        padding: 20px;
        text-align: center;
    }

    header h1 {
        margin: 0;
        transform:translatex(554px) translatey(-43px);
        top:-28px;
        width:18% !important;
        color:#724502;
        font-size: 24px;
    }

    .date-range {
        margin-top: 10px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    table th, table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }

    table th {
        background-color: #f2f2f2;
    }

    tfoot td {
        font-weight: bold;
    }

    main {
        padding: 20px;
        background-image:linear-gradient(to right, #e0eafc 0%, #cfdef3 100%);
    }

    footer {
        background-color: #333;
        color: #fff;
        text-align: center;
        padding: 10px;
    }

    footer p {
        margin: 5px;
    }

    body {
        font-family: Arial, sans-serif;
        background-color: #f0f0f0;
        margin: 0;
        padding: 0;
    }

    header {
        color: #fff;
        text-align: center;
        padding: 20px;
        height: 70px;
        min-height:78px;
        background-image:none;
        background-color:#f1eaab;
    }

    header img {
        max-width: 150px;
        display: inline-block;
        min-height: 98px;
        width: 150px;
        transform:translatex(-180px) translatey(-21px);
        min-height:113px;
        width:150px;
    }
    
    #myChart{
        position:relative;
        top:-4px;
        transform:translatex(-9px) translatey(-76px);
    }
    header h1 {
        font-size: 28px;
        margin-top: 10px;
        color: #456fec;
        position: relative;
        top: -10px;
        transform:translatex(560px) translatey(-55px);
        font-family:'Courier New',Courier,'Lucida Sans Typewriter','Lucida Typewriter',monospace;
        color:#3b3b4d;
        padding-left:55px;
    }

    .dashboard-header {
        background-color: #007bff;
        color: #fff;
        text-align: center;
        padding: 20px;
    }

    .dashboard-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        margin: 20px;
    }

    .dashboard-card {
        flex-basis: calc(33.33% - 20px);
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
    }

    .dashboard-card h2 {
        margin-top: 0;
        color: #007bff;
    }

    main .dashboard-container .dashboard-card h2{
        position:relative;  
        font-family:Palatino,'Palatino Linotype','Palatino LT STD','Book Antiqua',Georgia,serif;
        font-size:25px;
        line-height:0px;
        text-align:left;
        color:#3b3b4d;
        transform:translatex(0px) translatey(0px) !important;
        width:474px !important;
        top:13px;
      left:26px !important;
    }

    .dashboard-card p {
        margin: 0;
    }

    .button-container {
        margin-top: 20px;
        text-align: center;
    }

    .button {
        background-color: #007bff;
        color: #fff;
        border: none;
        padding: 10px 20px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 16px;
        margin: 4px 2px;
        cursor: pointer;
        border-radius: 5px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    th, td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    th {
        background-color: #007bff;
        color: #fff;
    }

    tr:nth-child(even) {
        background-color: #f2f2f2;
    }

    header .date-range label {
        color: #456fec;
    }

    header .date-range label {
        position: relative;
        top: -12px;
    }

    /* Start date */
    #startDate {
        position: relative;
        top: -12px;
        left: -2px;
    }

    /* End date */
    #endDate {
        position: relative;
        top: -11px;
        left: -2px;
    }

    /* Filter button */
    #filterButton {
        position: relative;
        top: -11px;
    }
    
</style>
<body>
    <header>
        <img class="app-logo" src="http://localhost/WEB/salespilot.png" alt="Sales Pilot Logo">
        <h1><?php echo $_SESSION['Username']; ?></h1>
    </header>
    <main>
        <div class="dashboard-container">
            <div class="dashboard-card">
                <h2>Inventory & Sales Analytics</h2>
            </div>
        </div>

        <canvas id="myChart" width="400" height="100"></canvas>
        <script>
            var ctx = document.getElementById('myChart').getContext('2d');
            var chartData = <?php echo json_encode($data); ?>;

            var myChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.Products,
                    datasets: [
                        {
                            label: 'Gross Profit Margin (%)',
                            data: chartData.GrossProfitMargin,
                            backgroundColor: 'rgba(0, 0, 255, 0.2)',
                            borderColor: 'rgba(0, 0, 255, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'Net Profit Margin (%)',
                            data: chartData.NetProfitMargin,
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 2,
                            fill: false
                        },
                        {
                            label: 'Inventory To Sales Ratio (%)',
                            data: chartData.InventoryToSalesRatio,
                            backgroundColor: 'rgba(55, 0, 0, 0.2)',
                            borderColor: 'rgba(55, 0, 0, 1)',
                            borderWidth: 2,
                            fill: false
                        },
                        {
                            label: 'Return On Investment (%)',
                            data: chartData.ReturnOnInvestment,
                            backgroundColor: 'rgba(0, 99, 132, 0.2)',
                            borderColor: 'rgba(0, 99, 132, 1)',
                            borderWidth: 2,
                            fill: false
                        }
                        
                    ]
                },
                options: {
                    scales: {
                        xAxes: [
                            {
                                stacked: true,
                                scaleLabel: {
                                    display: true,
                                    labelString: 'Products'
                                }
                            }
                        ],
                        yAxes: [
                            {
                                ticks: {
                                    beginAtZero: true
                                },
                                scaleLabel: {
                                    display: true,
                                    labelString: 'Percentage (%)'
                                }
                            }
                        ]
                    }
                }
            });
        </script>
        <div id="htmlReport"></div>

        <button class="button" onclick="generateHTMLReport()">View Report</button>
        <button class="button" onclick="generatePDFReport()">Export as PDF</button>

        <script>
            function generateHTMLReport() {
                var chartData = <?php echo json_encode($data); ?>;
                var reportContent = '<h2>Analytics Report</h2>';
                reportContent += '<table>';
                reportContent += '<tr><th>Product</th><th>Gross Profit Margin (%)</th><th>Net Profit Margin (%)</th><th>Inventory To Sales Ratio (%)</th><th>Return On Investment (%)</th></tr>';

                for (var i = 0; i < chartData.Products.length; i++) {
                    reportContent += '<tr>';
                    reportContent += '<td>' + chartData.Products[i] + '</td>';
                    reportContent += '<td>' + chartData.GrossProfitMargin[i] + '</td>';
                    reportContent += '<td>' + chartData.NetProfitMargin[i] + '</td>';
                    reportContent += '<td>' + chartData.InventoryToSalesRatio[i] + '</td>';
                    reportContent += '<td>' + chartData.ReturnOnInvestment[i] + '</td>';
                    reportContent += '</tr>';
                }

                reportContent += '</table>';

                // Display the HTML report in the "htmlReport" div
                document.getElementById('htmlReport').innerHTML = reportContent;
            }

            function generatePDFReport() {
                var chartData = <?php echo json_encode($data); ?>;
                var reportContent = "Sales Report\n\n";
                reportContent += "Product\tGross Profit Margin (%)\tNet Profit Margin (%)\tInventory To Sales Ratio (%)\tReturn On Investment (%)\n";

                for (var i = 0; i < chartData.Products.length; i++) {
                    reportContent += chartData.Products[i] + "\t" + chartData.GrossProfitMargin[i] + "\t" + chartData.NetProfitMargin[i] + "\t" + chartData.InventoryToSalesRatio[i] + "\t" + chartData.ReturnOnInvestment[i] +"\n";
                }

                var doc = new jsPDF();
                doc.text(reportContent, 10, 10);

                // Trigger PDF download
                doc.save("sales_report.pdf");
            }
        </script>
        <div class="button-container">
        <a href="product.php"><button class="button">Products</button></a>
        <a href="inventory.php"><button class="button">Inventory</button></a>
        <a href="dashboard.php"><button class="button">Dashboard</button></a>
    </div>
    </main>
    <footer>
        <p>&copy; 2023 Sales Pilot</p>
    </footer>
</body>
</html>