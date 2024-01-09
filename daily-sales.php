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


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    // Handle the form submission for updating inventory
    $salesrecordIds = $_POST['id_sales'];
    $products = $_POST['Product'];
    $dailySalesQtys = $_POST['DailySalesQty'];
    $costPrices = $_POST['CostPrice']; // Corrected variable name
    $salesPrices = $_POST['SalesPrice'];
    $dailyExpenses = $_POST['DailyExpenses'];

    for ($i = 0; $i < count($salesrecordIds); $i++) {
        $stmt = $connection->prepare("UPDATE sales SET Product = ?, DailySalesQty = ?, CostPrice = ?, SalesPrice = ?, DailyExpenses = ? WHERE id_sales = ?");
        $stmt->bind_param('sdddsi', $products[$i], $dailySalesQtys[$i], $costPrices[$i], $salesPrices[$i], $dailyExpenses[$i], $salesrecordIds[$i]);
        $stmt->execute();
    }

    // Redirect to the same page to avoid form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Fetch user records based on their Username
$stmt = $connection->prepare('SELECT * FROM sales WHERE Username = ?');
$stmt->bind_param('s', $Username);
$stmt->execute();
$result = $stmt->get_result();


// Function to fetch products with highest and lowest DailyRevenue and DailyProfit
function fetchProductsByProfit($connection, $Username, $orderBy) {
    $stmt = $connection->prepare("SELECT Product FROM sales WHERE Username = ? ORDER BY $orderBy LIMIT 1");
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

// Fetch products with highest DailyRevenue
$HighestDailyRevenueProduct = fetchProductsByProfit($connection, $Username, 'DailyRevenue DESC');

// Fetch products with lowest DailyRevenue
$LowestDailyRevenueProduct = fetchProductsByProfit($connection, $Username, 'DailyRevenue ASC');

// Fetch products with highest DailyProfit
$HighestDailyProfitProduct = fetchProductsByProfit($connection, $Username, 'DailyProfit DESC');

// Fetch products with lowest DailyProfit
$LowestDailyProfitProduct = fetchProductsByProfit($connection, $Username, 'DailyProfit ASC');

$stmt_metrics = $connection->prepare('SELECT category.categoryname AS categoryname, 
                                    GROUP_CONCAT(sales.Product) AS Product, 
                                    SUM(sales.DailyRevenue) AS DailyRevenue,
                                    AVG(SalesPrice) AS AverageSalesPrice, 
                                    SUM(sales.CostPrice) AS CostPrice, 
                                    SUM(sales.SalesPrice) AS SalesPrice, 
                                    SUM(sales.DailyExpenses) AS DailyExpenses, 
                                    SUM(sales.DailySalesQty) AS DailySalesQty,
                                    SUM(sales.NetProfit) AS NetProfit, 
                                    SUM(sales.GrossProfit) AS GrossProfit,
                                    SUM(sales.DailyProfit) AS DailyProfit
                                FROM sales
                                INNER JOIN category ON sales.sales_categoryid = category.id_category
                                WHERE sales.Username = ?
                                GROUP BY categoryname'); // Use categoryname instead of category.categoryname
$stmt_metrics->bind_param('s', $Username);
$stmt_metrics->execute();
$result_metrics = $stmt_metrics->get_result(); // Corrected variable name

// Fetch data for the chart (e.g., products and net profit)
$data = array(
    'categoryname' => array(),
    'Product' => array(),  
    'AverageSalesPrice' => array(),   
    'DailyRevenue' => array(),
    'CostPrice' => array(),
    'SalesPrice' => array(),
    'DailyExpenses' => array(),
    'DailyProfit' => array(),
    'NetProfit' => array(),
    'GrossProfit' => array(),
);

while ($row = $result_metrics->fetch_assoc()) {
    $data['categoryname'][] = $row['categoryname'];
    $data['Product'][] = $row['Product'];
    $data['DailyRevenue'][] = $row['DailyRevenue'];
    $data['AverageSalesPrice'][] = $row['AverageSalesPrice'];
    $data['CostPrice'][] = $row['CostPrice'];
    $data['SalesPrice'][] = $row['SalesPrice'];
    $data['DailyExpenses'][] = $row['DailyExpenses'];
    $data['DailyProfit'][] = $row['DailyProfit'];
    $data['NetProfit'][] = $row['NetProfit'];
    $data['GrossProfit'][] = $row['GrossProfit'];
}



function fetchAndCalculateDailys($connection, $Username, $selectedDate) {
    $dateCondition = !empty($selectedDate) ? "AND Date = '$selectedDate'" : "";

    $stmt = $connection->prepare('SELECT COUNT(*) AS DailyProduct, SUM(DailyRevenue) AS DailyRevenue, SUM(CostPrice) AS DailyExpenses, SUM(DailySalesQty) AS DailySalesQty, AVG(SalesPrice) AS AverageSalesPrice, SUM(NetProfit) AS NetProfit, SUM(GrossProfit) AS GrossProfit FROM sales WHERE Username = ? ' . $dateCondition);
    $stmt->bind_param('s', $Username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return [
            'DailyProduct' => $row['DailyProduct'],
            'DailyRevenue' => $row['DailyRevenue'],
            'DailyExpenses' => $row['DailyExpenses'],
            'DailySalesQty' => $row['DailySalesQty'],
            'AverageSalesPrice' => $row['AverageSalesPrice'],
            'NetProfit' => $row['NetProfit'],
            'GrossProfit' => $row['GrossProfit'],
        ];
    } else {
        return [
            'DailyProduct' => 0,
            'DailyRevenue' => 0,
            'DailyExpenses' => 0,
            'DailySalesQty' => 0,
            'AverageSalesPrice' => 0,
            'NetProfit' => 0,
            'GrossProfit' => 0,
        ];
    }
}

$selectedDate = isset($_GET['selectedDate']) ? $_GET['selectedDate'] : '';
$totalData = fetchAndCalculateDailys($connection, $Username, $selectedDate);


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <link href="http://localhost/WEB/newlogo.png" rel="icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Sales</title>
</head>
<style>
   /* Reset styles */
body, main, table {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
}

/* Header styles */
header {
    background-image: linear-gradient(to right, #108dc7 0%, #ef8e38 100%);
    color: #fff;
    text-align: center;
    padding: 20px;
    height: 70px;
}

header img {
    max-width: 150px;
}

header h1 {
    font-size: 28px;
    margin-top: 10px;
    color: #456fec;
    width: 44% !important;
    transform: translateX(754px) translateY(-60px);
}

/* Button */
.dashboard-container a .button {
    transform: translateX(876px) translateY(-538px);
}

/* Dashboard header */
.dashboard-header {
    background-color: #007bff;
    color: #fff;
    text-align: center;
    padding: 20px;
}

/* Dashboard container */
.dashboard-container {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    margin: 20px;
    width:1322px;
}

/* Dashboard card */
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

.dashboard-card p {
    margin: 0;
}

/* Button styles */
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

/* Table styles */
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

/* Date range label in header */
header .date-range label {
    color: #456fec;
}

/* Positioning adjustments */
main .dashboard-container .dashboard-card {
    position: relative;
    top: 37px;
    transform: translateX(45px) translateY(-50px);
}

#myChart {
    position: relative;
    top: -8px;
    width:91% !important;
    transform:translatex(45px) translatey(-11px);
}


/* Scrollable sales metrics table */
.sales-metrics table {
    display: block;
    max-height: 300px; /* Adjust the max height as needed */
    overflow-y: auto;
}

/* CSS for the Dropdown */
.dropdown {
    display: inline-block;
    transform:translatex(943px) translatey(-453px);
    position:relative;
    top:-991px;
    left:-66px;
}

.dropbtn {
    background-color: #007bff;
    color: white;
    padding: 10px 20px;
    font-size: 16px;
    border: none;
    cursor: pointer;
}

.dropdown-content {
    display: none;
    position: absolute;
    background-color: #f9f9f9;
    box-shadow: 0px 8px 16px 0px rgba(0,0,0,0.2);
    z-index: 1;
}

.dropdown-content a {
    color: black;
    padding: 12px 16px;
    text-decoration: none;
    display: block;
}

.dropdown-content a:hover {
    background-color: #ddd;
}

.dropdown:hover .dropdown-content {
    display: block;
}

.dropdown:hover .dropbtn {
    background-color: #0056b3;
}


/* Footer */
footer{
 font-size:21px;
 text-align:center;
 font-style:italic;
 background-color:#afada6;
 line-height:18.8px;
  min-height:73px;
}

footer p{
 position:relative;
 top:11px;
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
        <h2>Daily Sales Overview</h2>
        <p>Daily Product: <?php echo $totalData['DailyProduct']; ?></p>  
        <p>Highest Daily Revenue: <?php echo $HighestDailyRevenueProduct; ?></p>
        <p>Lowest Daily Revenue: <?php echo $LowestDailyRevenueProduct; ?></p>
        <p>Highest Daily Profit: <?php echo $HighestDailyProfitProduct; ?></p>
        <p>Lowest Daily Profit: <?php echo $LowestDailyProfitProduct; ?></p>
    </div>
    
    <canvas id="myChart" width="400" height="100"></canvas>
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
                    label: 'Daily Profit ($)',
                    data: chartData.DailyProfit, // Updated to use DailyProfit
                    backgroundColor: 'rgba(0, 0, 255, 0.2)',
                    borderColor: 'rgba(0, 0, 255, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Daily Expenses ($)',
                    data: chartData.DailyExpenses,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    fill: false
                },
                {
                    label: 'Daily Revenue ($)',
                    data: chartData.DailyRevenue, // Updated to use DailyRevenue
                    borderColor: 'rgba(0, 99, 132, 1)',
                    borderWidth: 3,
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
   
</div>
    <div class="dashboard-card sales-metrics">
    <h2>Daily Sales Metrics</h2>
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
    <table>
            <tr>
                <th>Product</th>
                <th>Daily Sales Quantity</th>
                <th>Cost Price </th> 
                <th>Sales Price </th>
                <th>Daily Expenses</th>
            </tr>
            <?php
            // Reset the result set pointer
            $result->data_seek(0);
            while ($row = $result->fetch_assoc()) :
            ?>
                <tr>
                    <input type="hidden" name="id_sales[]" value="<?php echo $row['id_sales']; ?>">
                    <td><input type="text" name="Product[]" value="<?php echo $row['Product']; ?>"></td>   
                    <td><input type="text" name="DailySalesQty[]" value="<?php echo $row['DailySalesQty']; ?>"></td>
                    <td>$<input type="text" name="CostPrice[]" value="<?php echo $row['CostPrice']; ?>"></td>
                    <td>$<input type="text" name="SalesPrice[]" value="<?php echo $row['SalesPrice']; ?>"></td>
                    <td>$<input type="text" name="DailyExpenses[]" value="<?php echo $row['DailyExpenses']; ?>"></td>
                </tr>
            <?php endwhile; ?>
        </table>
        <button class="button" type="submit" name="update">Update</button>
    </form> 
</div>
<div class="dashboard-card sales-metrics">
    <h2>Daily Sales Expenditure</h2>
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <table  id="inventoryTable">
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Daily Sales Qty</th>
                <th>Daily Revenue</th>
                <th>Daily Profit</th>
                <th>Gross Profit</th>
                <th>Average Daily Sales</th>
            </tr>
            <?php
            $result_metrics->data_seek(0);
            while ($row = $result_metrics->fetch_assoc()) :
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['Product']); ?></td>
                    <td><?php echo fetchCategoryName($connection, $row['categoryname']); ?></td>
                    <td><?php echo number_format($row['DailySalesQty'],); ?></td>
                    <td>$<?php echo number_format($row['DailyRevenue'], 2); ?></td>
                    <td>$<?php echo number_format($row['DailyProfit'], 2); ?></td>
                    <td>$<?php echo number_format($row['GrossProfit'], 2); ?></td>
                    <td>$<?php echo number_format($row['AverageSalesPrice'], 2); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>   
    </div>
    <div class="dropdown">
    <button class="dropbtn">Select View</button>
    <div class="dropdown-content">
        <a href="product.php">Products</a>
        <a href="inventory.php">Inventory</a>
        <a href="sales-dashboard.php">Dashboard</a>
        <a href="weekly-sales.php">Weekly</a>
        <a href="monthly-sales.php">Monthly</a>
        <a href="yearly-sales.php">Annual</a>
    </div>
</div>
    </main>
    <footer>
        <p>Last Data Update: <span id="currentDateTime"></span></p>
        <p>Contact Us: olphemie@sales-pilot.com</p>
    </footer>
    <script>
  // JavaScript code to display current date and time
  const currentDate = new Date();
  const currentDateTimeElement = document.getElementById("currentDateTime");
  
  const options = { 
    year: 'numeric', 
    month: 'long', 
    day: 'numeric', 
    hour: '2-digit', 
    minute: '2-digit' 
  };
  
  currentDateTimeElement.textContent = currentDate.toLocaleDateString(undefined, options);
</script>
<script>
        // JavaScript code to handle date range filtering
        const filterButton = document.getElementById("filterButton");
        filterButton.addEventListener("click", function() {
            const startDate = document.getElementById("startDate").value;
            const endDate = document.getElementById("endDate").value;
            
            // Redirect to the same page with date range parameters
            window.location.href = `${window.location.pathname}?startDate=${startDate}&endDate=${endDate}`;
        });
    </script>

</body>
</html>
