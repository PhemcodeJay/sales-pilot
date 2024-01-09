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

$Username = $_SESSION['Username'];

function fetchInventoryDataByMonth($connection, $Username, $month)
{
    $stmt = $connection->prepare('
        SELECT inventory.Product, category.categoryname, inventory.InventoryQty, inventory.StockQty, inventory.SupplyQty, inventory.Date
        FROM inventory
        JOIN category ON inventory.category_id = category.id_category
        WHERE inventory.Username = ? AND MONTH(inventory.Date) = ?
    ');

    $stmt->bind_param('ss', $Username, $month);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}



// Function to fetch data from inventory table based on date
function fetchInventoryDataByDate($connection, $Username, $date)
{
    $stmt = $connection->prepare('
        SELECT inventory.Product, category.categoryname, inventory.InventoryQty, inventory.StockQty, inventory.SupplyQty, inventory.Date
        FROM inventory
        JOIN category ON inventory.category_id = category.id_category
        WHERE inventory.Username = ? AND inventory.Date = ?
    ');

    $stmt->bind_param('ss', $Username, $date);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

// Define the fetchCategoryName function
function fetchCategoryName($connection, $categoryName) {
    $stmt = $connection->prepare("SELECT categoryname FROM category WHERE id_category = ?");
    $stmt->bind_param('s', $categoryName);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row['categoryname'];
    } else {
        return 'Category not found';
    }
}
// Function to fetch and calculate totals
function fetchAndCalculateTotals($connection, $Username) {
    $stmt = $connection->prepare('SELECT COUNT(*) AS TotalProduct, SUM(InventoryQty) AS TotalInventoryQty FROM inventory WHERE Username = ?');
    $stmt->bind_param('s', $Username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return [
            'TotalProduct' => $row['TotalProduct'],
            'TotalInventoryQty' => $row['TotalInventoryQty']
        ];
    } else {
        return [
            'TotalProduct' => 0,
            'TotalInventoryQty' => 0
        ];
    }
}

// Fetch and calculate totals
$totalData = fetchAndCalculateTotals($connection, $Username);


// Fetch category names and IDs
$categoryNames = array();

$stmt = $connection->prepare('SELECT id_category, categoryname FROM category');
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $categoryNames[$row['id_category']] = $row['categoryname'];
}


// Fetch user records based on their Username and group by category
$stmt = $connection->prepare('SELECT category.categoryname AS categoryname, GROUP_CONCAT(inventory.Product) AS ProductNames, SUM(inventory.WeeklyStockQty) AS WeeklyStockQty, SUM(inventory.InventoryQty) AS InventoryQty, SUM(inventory.StockQty) AS StockQty, SUM(inventory.SupplyQty) AS SupplyQty
FROM inventory
INNER JOIN category ON inventory.category_id = category.id_category
WHERE inventory.Username = ?
GROUP BY categoryname'); // Use categoryname instead of category.categoryname
$stmt->bind_param('s', $Username);
$stmt->execute();
$result = $stmt->get_result();

// Fetch data for the chart (e.g., products and net profit)
$data = array(
    'categoryname' => array(),
    'ProductNames' => array(),
    'WeeklyStockQty' => array(),
    'InventoryQty' => array(),
    'StockQty' => array(),
    'SupplyQty' => array(),
);

while ($row = $result->fetch_assoc()) {
    $data['categoryname'][] = $row['categoryname'];
    $data['ProductNames'][] = $row['ProductNames'];
    $data['WeeklyStockQty'][] = $row['WeeklyStockQty'];
    $data['InventoryQty'][] = $row['InventoryQty'];
    $data['StockQty'][] = $row['StockQty'];
    $data['SupplyQty'][] = $row['SupplyQty'];
}

// Initialize an array to store aggregated data
$aggregatedData = [];

// Fetch and aggregate data for each month and week
for ($month = 1; $month <= 12; $month++) {
    // Fetch data for the month
    $monthlyData = fetchInventoryDataByMonth($connection, $Username, $month);

    // Key to identify the month
    $key = $month;

    // If the key doesn't exist, initialize the aggregated data
    if (!isset($aggregatedData[$key])) {
        $aggregatedData[$key] = [
            'Month' => date('F', mktime(0, 0, 0, $month, 1)),
            'Weeks' => [], // Initialize the 'Weeks' array
            'Categories' => [],
            'Products' => [],
            'StockQty' => 0,
            'SupplyQty' => 0,
            'InventoryQty' => 0,
        ];
    }

    if (!empty($monthlyData)) {
        foreach ($monthlyData as $rowMonth) {
            // Fetch category and product names
            $categoryName = fetchCategoryName($connection, $rowMonth['categoryname']);
            $productName = $rowMonth['Product'];

            // Check if the category and product combination already exists
            $keyCombination = $categoryName . '|' . $productName;
            if (!in_array($keyCombination, $aggregatedData[$key]['Categories'])) {
                $aggregatedData[$key]['Categories'][] = $keyCombination;
                $aggregatedData[$key]['Products'][] = $productName;

                // Update the aggregated data
                $aggregatedData[$key]['StockQty'] += $rowMonth['StockQty'];
                $aggregatedData[$key]['SupplyQty'] += $rowMonth['SupplyQty'];
                $aggregatedData[$key]['InventoryQty'] += $rowMonth['InventoryQty'];
            }

            // Store the week number if not already present
            $weekNumber = date('W', strtotime($rowMonth['Date']));
            if (!in_array($weekNumber, $aggregatedData[$key]['Weeks'])) {
                $aggregatedData[$key]['Weeks'][] = $weekNumber;
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <link rel="icon" type="image/png" href="http://localhost/WEB/newlogo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - Weekly</title>
    <style>
        body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color:#5f6e76;
        min-height:909px;
    }

    main tr th {
        color: #456fec;
    }

    #myChart{
 position:relative;
 background-color:#f3e7bd;
 top:-76px;
 height:430px !important;
 width:1305px !important;
 left:3px;
 transform:translatex(17px) translatey(27px);
}

/* Dashboard container */
.dashboard-container{
 min-height:74px;
 height:74px;
}


/* Heading */
.dashboard-header h1{
 color:#65a4dc;
 position:relative;
 left:-23px;
}
    main .dashboard-container .dashboard-card {
        position: relative;
        top: 35px;
        left: -16px;
        width:419px !important;
        text-align:right;
       transform:translatex(353px) translatey(-142px);
        background-color:#f3e7bd;
       height:63px;
    }
    .dashboard-container .dashboard-card p{
 position:relative;
 top:-33px;
 left:3px;
 font-weight:600;
 background-color:#f3e7bd;
 text-align:center;
}


/* Dashboard card */
.dashboard-container .dashboard-card{
 height:76px;
 min-height:76px;
 position:relative;
 left:-26px;
  background-color:#f3e7bd;
}



/* Dashboard container */
.dashboard-container{
 transform:translatex(0px) translatey(0px);
 
}


    header {
        background-color:#5f6e76;
        padding: 20px;
        text-align: center;
    }

    header h1 {
        margin: 0;
        font-size: 24px;
    }

/* Dashboard header */
.dashboard-header{
 transform:translatex(0px) translatey(0px);
 background-color:#f3e7bd;
 background-color:transparent;
}



/* Heading */
.dashboard-header h1{
 color:#89c7fa;
 transform:translatex(482px) translatey(-81px);
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
        background-color: #007bff;
    }

    tfoot td {
        font-weight: bold;
    }

    main {
        padding: 20px;
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
        background-color:#5f6e76;
        margin: 0;
        padding: 0;
    }

    header img {
        max-width: 150px;
        display: inline-block;
        transform:translatex(-231px) translatey(-15px);
        min-height: 98px;
        width: 150px;
    }

    header h1 {
        font-size: 28px;
        margin-top: 10px;
        color: #456fec;
        position: relative;
        top: -10px;
    }

    .dashboard-header h1{
     font-family:'Courier New',Courier,'Lucida Sans Typewriter','Lucida Typewriter',monospace;
     color:#f9daa3;
     width:31% !important;
     transform:translatex(506px) translatey(-84px);
    }

    /* Dashboard card */
.dashboard-container .dashboard-card{
 transform:translatex(332px) translatey(-109px);
 height:86px;
 position:relative;
 background-color:#f3e7bd;
 top:-5px;
 left:20px;
}

/* Heading */
.dashboard-header h1{
 left:-18px;
 transform:translatex(548px) translatey(-118px);
}


/* Button */
.button-container a .button{
 background-color:#5f6e76;
}

/* Button */
.button{
 background-color:#2f3436 !important;
}



    .dashboard-container {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        margin: 20px;
    }

    .dashboard-card {
        flex-basis: calc(33.33% - 20px);
        background-color: #f3e7bd;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
        transform:translatex(357px) translatey(-142px);
    }

    .dashboard-card h2 {
        margin-top: 0;
        color: #007bff;
    }

    .dashboard-card p {
        margin: 0;
    }

    .button-container {
        margin-top: 20px;
        text-align: center;
        
    }


    /* Link */
.button-container .button a{
 color:#f6f6f8;
 text-decoration:none;
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
        transform:translatex(7px) translatey(4px);
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

    .dashboard-container .dashboard-card{
 background-color:#f3e7bd;
 min-height:76px;
}

/* Heading */
.dashboard-container .dashboard-card h2{
 width:59% !important;
 text-align:right;
 transform:translatex(176px) translatey(-17px);
 color:#6e7777;
 text-align:center;
 position:relative;
 left:-86px;
 top:-3px;
}


table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 15px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
<header>
        <img class="app-logo" src="http://localhost/WEB/salespilot.png" alt="Sales Pilot Logo">
    </header>

    <div class="dashboard-header">
        <h1><?php echo htmlspecialchars($_SESSION['Username']); ?></h1>
    </div>

    <div class="dashboard-container">
        <div class="dashboard-card">
            <h2>Weekly Overview</h2>
            <p>Category: 10</p>
            <p>Total Products: <?php echo number_format($totalData['TotalProduct']); ?></p>
            <p>Total Inventory Qty: <?php echo number_format($totalData['TotalInventoryQty']); ?></p>
            <p>Date: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>

    <canvas id="myChart" width="500" height="150"></canvas>
  <script>

    var ctx = document.getElementById('myChart').getContext('2d');
    var chartData = <?php echo json_encode($data); ?>;

    var myChart = new Chart(ctx, {
        type: 'line', 
        data: {
            labels: [
          "Tech Devices",
          "Office Supplies",
          "Clothing Items",
          "Food Itms",
          "Home Goods",
          "Wellness Products",
          "Automotives",
          "Sport & Games",
          "Other",
          "Kids"
        ],
            datasets: [
                
                
                {
    label: 'Weekly Stock',
    data: chartData.WeeklyStockQty,
    backgroundColor: 'rgba(0, 0, 255, 0.2)',
    borderColor: 'rgba(0, 0, 255, 1)',
    borderWidth: 1
},
{
    label: 'Inventory Qty',
    data: chartData.InventoryQty,
    backgroundColor: 'rgba(255, 0, 0, 0.2)',
    borderColor: 'rgba(255, 0, 0, 1)',
    borderWidth: 2,
    fill: false
},
{
    label: 'Stock Qty',
    data: chartData.StockQty,
    backgroundColor: 'rgba(0, 255, 0, 0.2)',
    borderColor: 'rgba(0, 255, 0, 1)',
    borderWidth: 2,
    fill: false
},
{
    label: 'Supply Qty',
    data: chartData.SupplyQty,
    backgroundColor: 'rgba(128, 0, 128, 0.2)',
    borderColor: 'rgba(128, 0, 128, 1)',

    borderWidth: 2,
    fill: false
}

            ]
        },
        options: {
            scales: {
                x: {
                    stacked: true,
                    title: {
                        display: true,
                        text: 'Category'
                    }
                },
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Amount ($)'
                    }
                }
            }
        }
    });
</script>

<div id="htmlReportTable"></div>

    <button class="button" onclick="generateHTMLReport()">View Report</button>
    <button class="button" onclick="generatePDFReport()">Export as PDF</button>

    <script>
    function generateHTMLReport() {
    var chartData = <?php echo json_encode($data); ?>;
    var categoryNames = <?php echo json_encode($categoryNames); ?>;
    var reportContent = '<h2>Weekly Report</h2>';
    reportContent += '<table>';
    reportContent += '<tr><th>Category</th><th>Product</th><th>Available Weekly Stock</th><th>Inventory Qty</th><th>Stock Qty</th><th>Supply Qty</th></tr>';

    for (var i = 0; i < chartData.categoryname.length; i++) {
        var categoryId = chartData.categoryname[i];
        var categoryName = categoryNames[categoryId] || 'Unknown Category';
        reportContent += '<tr>';
        reportContent += '<td>' + categoryName + '</td>';
        reportContent += '<td>' + chartData.ProductNames[i] + '</td>';
        reportContent += '<td>' + numberWithCommas(chartData.WeeklyStockQty[i]) + '</td>';
        reportContent += '<td>' +  numberWithCommas(chartData.InventoryQty[i]) + '</td>';
        reportContent += '<td>' +  numberWithCommas(chartData.StockQty[i]) + '</td>';
        reportContent += '<td>' +  numberWithCommas(chartData.SupplyQty[i]) + '</td>';
        reportContent += '</tr>';
    }

    // Rest of the function...

    function numberWithCommas(x) {
        return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }



    reportContent += '</table>';

    // Display the HTML report in the "htmlReport" div
    document.getElementById('htmlReportTable').innerHTML = reportContent;
}

        function generatePDFReport() {
            var chartData = <?php echo json_encode($data); ?>;
            var reportContent = "Sales Report\n\n";
            reportContent += "Category Name\tProduct Names\tTotal Revenue\tInventory Value\tStock Value\tSupply Value\n";

            for (var i = 0; i < chartData.categoryname.length; i++) {
                reportContent += chartData.categoryname[i] + "\t" + chartData.ProductNames[i] + "\t" + chartData.WeeklySalesQty[i] + "\t" + chartData.InventoryQty[i] + "\t" + chartData.StockQty[i] + "\t" + chartData.SupplyQty[i] + "\n";
            }

            var doc = new jsPDF();
            doc.text(reportContent, 10, 10);

            // Trigger PDF download
            doc.save("sales_report.pdf");
        }
    </script>
 <h1>Weekly Inventory</h1>
<table>
    <thead>
        <tr>
            <th>Month</th>
            <th>Week Number</th>
            <th>Product</th>
            <th>Stock Qty</th>
            <th>Supply Qty</th>
            <th>Inventory Qty</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Fetch and aggregate data for each month and week
        for ($month = 1; $month <= 12; $month++) {
            // Fetch data for the month
            $monthlyData = fetchInventoryDataByMonth($connection, $Username, $month);

            // Key to identify the month
            $key = $month;

            // If the key doesn't exist, initialize the aggregated data
                if (!isset($aggregatedData[$key])) {
                    $aggregatedData[$key] = [
                        'Month' => date('F', mktime(0, 0, 0, $month, 1)),
                        'Weeks' => [], // Initialize the 'Weeks' array
                        'Categories' => [],
                        'Products' => [],
                        'StockQty' => 0,
                        'SupplyQty' => 0,
                        'InventoryQty' => 0,
                    ];
                }


            if (!empty($monthlyData)) {
                foreach ($monthlyData as $rowMonth) {
                    // Fetch category and product names
                    $categoryName = fetchCategoryName($connection, $rowMonth['categoryname']);
                    $productName = $rowMonth['Product'];

                    // Check if the category and product combination already exists
                    $keyCombination = $categoryName . '|' . $productName;
                    if (!in_array($keyCombination, $aggregatedData[$key]['Categories'])) {
                        $aggregatedData[$key]['Categories'][] = $keyCombination;
                        $aggregatedData[$key]['Products'][] = $productName;

                        // Update the aggregated data
                        $aggregatedData[$key]['StockQty'] += $rowMonth['StockQty'];
                        $aggregatedData[$key]['SupplyQty'] += $rowMonth['SupplyQty'];
                        $aggregatedData[$key]['InventoryQty'] += $rowMonth['InventoryQty'];
                    }
                }
            }
        }

        // Display the aggregated data in the table
        foreach ($aggregatedData as $row) {
            echo "<tr>";
            echo "<td>{$row['Month']}</td>";
            echo "<td>" . implode(', ', $row['Weeks']) . "</td>"; // Display actual week numbers
            echo "<td>" . implode(', ', $row['Products']) . "</td>";
            echo "<td>{$row['StockQty']}</td>";
            echo "<td>{$row['SupplyQty']}</td>";
            echo "<td>{$row['InventoryQty']}</td>";
            echo "</tr>";
        }
        ?>
    </tbody>
</table>

    <div class="button-container">
        <a href="product.php"><button class="button">Products</button></a>
        <a href="inventory-dashboard.php"><button class="button">Dashboard</button></a>
        <a href="sales-dashboard.php"><button class="button">Sales</button></a>
        <a href="monthlyreport.php"><button class="button">Monthly</a>
        <a href="yearlyreport.php"><button class="button">Yearly</a>
    </div>
</body>
</html>