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

if (isset($_POST['update'])) {
    $products = $_POST['Product'];
    $monthlySalesQty = $_POST['MonthlySalesQty'];
    $weeklySalesQty = $_POST['WeeklySalesQty'];
    $dailySalesQty = $_POST['DailySalesQty'];
    $yearlySalesQty = $_POST['YearlySalesQty'];
    $salesPrice = $_POST['SalesPrice'];

    // Prepare and bind parameters
    $stmt = $connection->prepare('UPDATE sales SET MonthlySalesQty = ?, WeeklySalesQty = ?, DailySalesQty = ?, SalesPrice = ?, YearlySalesQty = ? WHERE Product = ?');
    $stmt->bind_param('dddsss', $monthlySalesQtyValue, $weeklySalesQtyValue, $dailySalesQtyValue, $salesPriceValue, $yearlySalesQtyValue, $product);

    // Loop through the form data and update the database
    foreach ($products as $key => $product) {
        $monthlySalesQtyValue = isset($monthlySalesQty[$key]) ? $monthlySalesQty[$key] : null;
        $weeklySalesQtyValue = isset($weeklySalesQty[$key]) ? $weeklySalesQty[$key] : null;
        $dailySalesQtyValue = isset($dailySalesQty[$key]) ? $dailySalesQty[$key] : null;
        $yearlySalesQtyValue = isset($yearlySalesQty[$key]) ? $yearlySalesQty[$key] : null;
        $salesPriceValue = isset($salesPrice[$key]) ? $salesPrice[$key] : null;

        // Execute the statement only if values are not null
        if ($monthlySalesQtyValue !== null && $weeklySalesQtyValue !== null && $dailySalesQtyValue !== null && $yearlySalesQtyValue !== null && $salesPriceValue !== null && $product !== null) {
            // Update the order of parameters in the bind_param method to match the order of placeholders in your SQL query
            $stmt->bind_param('dddsss', $monthlySalesQtyValue, $weeklySalesQtyValue, $dailySalesQtyValue, $salesPriceValue, $yearlySalesQtyValue, $product);

            $stmt->execute();
        }
    }


// Redirect to the current page to refresh the data
header('Location: ' . $_SERVER['PHP_SELF']); {
    exit();
}

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

// Create temporary table for weekly sales with daily quantities
$weeklySalesTable = "
    CREATE TEMPORARY TABLE temp_weekly_sales AS
    SELECT 
        Product, 
        WEEK(Date) AS SalesWeek,
        MONTH(Date) AS SalesMonth, 
        YEAR(Date) AS SalesYear,
        DAYOFWEEK(Date) AS DayOfWeek,
        SUM(DailySalesQty) AS DailySalesQty
    FROM 
        sales
    WHERE 
        Date >= '$weekStartDate' AND Date <= '$currentDate'
    GROUP BY 
        Product, SalesWeek, SalesMonth, SalesYear, DayOfWeek, Date
";

$connection->query($weeklySalesTable);

// Update sales table with weekly and daily sales data
$updateWeeklySales = "
    UPDATE 
        sales AS sr
    JOIN (
        SELECT 
            Product, 
            SalesWeek,
            SalesYear,
            DayOfWeek,
            SUM(DailySalesQty) AS DailySalesQty
        FROM 
            temp_weekly_sales
        GROUP BY 
            Product, SalesWeek, SalesYear, DayOfWeek
    ) AS temp
    ON 
        sr.Product = temp.Product 
        AND WEEK(sr.Date) = temp.SalesWeek 
        AND YEAR(sr.Date) = temp.SalesYear
        AND DAYOFWEEK(sr.Date) = temp.DayOfWeek
    SET 
        sr.DailySalesQty = temp.DailySalesQty
";
$connection->query($updateWeeklySales);

// Drop temporary table for weekly sales
$dropTempTable = "DROP TEMPORARY TABLE IF EXISTS temp_weekly_sales";
$connection->query($dropTempTable);


// Create temporary table for monthly sales with daily quantities
$monthlySalesTable = "
    CREATE TEMPORARY TABLE temp_monthly_sales AS
    SELECT 
        Product, 
        MONTH(Date) AS SalesMonth, 
        YEAR(Date) AS SalesYear,
        DAY(Date) AS DayOfMonth,
        SUM(DailySalesQty) AS DailySalesQty
    FROM 
        sales
    WHERE 
        Date >= DATE_FORMAT(NOW(), '%Y-%m-01') AND Date <= '$currentDate'
    GROUP BY 
        Product, SalesMonth, SalesYear, DayOfMonth
";
$connection->query($monthlySalesTable);

// Update sales table with monthly and daily sales data
$updateMonthlySales = "
    UPDATE 
        sales AS sr
    JOIN (
        SELECT 
            Product, 
            SalesMonth,
            SalesYear,
            DayOfMonth,
            SUM(DailySalesQty) AS DailySalesQty
        FROM 
            temp_monthly_sales
        GROUP BY 
            Product, SalesMonth, SalesYear, DayOfMonth
    ) AS temp
    ON 
        sr.Product = temp.Product 
        AND MONTH(sr.Date) = temp.SalesMonth 
        AND YEAR(sr.Date) = temp.SalesYear
        AND DAY(sr.Date) = temp.DayOfMonth
    SET 
        sr.DailySalesQty = temp.DailySalesQty
";
$connection->query($updateMonthlySales);

// Drop temporary table for monthly sales
$dropTempMonthlyTable = "DROP TEMPORARY TABLE IF EXISTS temp_monthly_sales";
$connection->query($dropTempMonthlyTable);

// Create temporary table for yearly sales with daily quantities
$yearlySalesTable = "
    CREATE TEMPORARY TABLE temp_yearly_sales AS
    SELECT 
        Product, 
        YEAR(Date) AS SalesYear,
        DAY(Date) AS DayOfYear,
        SUM(DailySalesQty) AS DailySalesQty
    FROM 
        sales
    WHERE 
        YEAR(Date) = YEAR(NOW())
    GROUP BY 
        Product, SalesYear, DayOfYear
";
$connection->query($yearlySalesTable);

// Update sales table with yearly and daily sales data
$updateYearlySales = "
    UPDATE 
        sales AS sr
    JOIN (
        SELECT 
            Product, 
            SalesYear,
            DayOfYear,
            SUM(DailySalesQty) AS DailySalesQty
        FROM 
            temp_yearly_sales
        GROUP BY 
            Product, SalesYear, DayOfYear
    ) AS temp
    ON 
        sr.Product = temp.Product 
        AND YEAR(sr.Date) = temp.SalesYear
        AND DAYOFYEAR(sr.Date) = temp.DayOfYear
    SET 
        sr.DailySalesQty = temp.DailySalesQty
";
$connection->query($updateYearlySales);

// Drop temporary table for yearly sales
$dropTempYearlyTable = "DROP TEMPORARY TABLE IF EXISTS temp_yearly_sales";
$connection->query($dropTempYearlyTable);


// Create temporary table for weekly Expenses with daily quantities
$weeklyExpensesTable = "
    CREATE TEMPORARY TABLE temp_weekly_Expenses AS
    SELECT 
        Product, 
        WEEK(Date) AS ExpensesWeek,
        MONTH(Date) AS ExpensesMonth, 
        YEAR(Date) AS ExpensesYear,
        DAYOFWEEK(Date) AS DayOfWeek,
        SUM(DailyExpenses) AS DailyExpenses
    FROM 
        sales
    WHERE 
        Date >= '$weekStartDate' AND Date <= '$currentDate'
    GROUP BY 
        Product, ExpensesWeek, ExpensesMonth, ExpensesYear, DayOfWeek, Date
";

$connection->query($weeklyExpensesTable);

// Update Expenses table with weekly and daily Expenses data
$updateWeeklyExpenses = "
    UPDATE 
        sales AS sr
    JOIN (
        SELECT 
            Product, 
            ExpensesWeek,
            ExpensesYear,
            DayOfWeek,
            SUM(DailyExpenses) AS DailyExpenses
        FROM 
            temp_weekly_Expenses
        GROUP BY 
            Product, ExpensesWeek, ExpensesYear, DayOfWeek
    ) AS temp
    ON 
        sr.Product = temp.Product 
        AND WEEK(sr.Date) = temp.ExpensesWeek 
        AND YEAR(sr.Date) = temp.ExpensesYear
        AND DAYOFWEEK(sr.Date) = temp.DayOfWeek
    SET 
        sr.DailyExpenses = temp.DailyExpenses
";
$connection->query($updateWeeklyExpenses);

// Drop temporary table for weekly Expenses
$dropTempTable = "DROP TEMPORARY TABLE IF EXISTS temp_weekly_Expenses";
$connection->query($dropTempTable);


// Create temporary table for monthly Expenses with daily quantities
$monthlyExpensesTable = "
    CREATE TEMPORARY TABLE temp_monthly_Expenses AS
    SELECT 
        Product, 
        MONTH(Date) AS ExpensesMonth, 
        YEAR(Date) AS ExpensesYear,
        DAY(Date) AS DayOfMonth,
        SUM(DailyExpenses) AS DailyExpenses
    FROM 
        sales
    WHERE 
        Date >= DATE_FORMAT(NOW(), '%Y-%m-01') AND Date <= '$currentDate'
    GROUP BY 
        Product, ExpensesMonth, ExpensesYear, DayOfMonth
";
$connection->query($monthlyExpensesTable);

// Update Expenses table with monthly and daily Expenses data
$updateMonthlyExpenses = "
    UPDATE 
        sales AS sr
    JOIN (
        SELECT 
            Product, 
            ExpensesMonth,
            ExpensesYear,
            DayOfMonth,
            SUM(DailyExpenses) AS DailyExpenses
        FROM 
            temp_monthly_Expenses
        GROUP BY 
            Product, ExpensesMonth, ExpensesYear, DayOfMonth
    ) AS temp
    ON 
        sr.Product = temp.Product 
        AND MONTH(sr.Date) = temp.ExpensesMonth 
        AND YEAR(sr.Date) = temp.ExpensesYear
        AND DAY(sr.Date) = temp.DayOfMonth
    SET 
        sr.DailyExpenses = temp.DailyExpenses
";
$connection->query($updateMonthlyExpenses);

// Drop temporary table for monthly Expenses
$dropTempMonthlyTable = "DROP TEMPORARY TABLE IF EXISTS temp_monthly_Expenses";
$connection->query($dropTempMonthlyTable);

// Create temporary table for yearly Expenses with daily quantities
$yearlyExpensesTable = "
    CREATE TEMPORARY TABLE temp_yearly_Expenses AS
    SELECT 
        Product, 
        YEAR(Date) AS ExpensesYear,
        DAY(Date) AS DayOfYear,
        SUM(DailyExpenses) AS DailyExpenses
    FROM 
        sales
    WHERE 
        YEAR(Date) = YEAR(NOW())
    GROUP BY 
        Product, ExpensesYear, DayOfYear
";
$connection->query($yearlyExpensesTable);

// Update Expenses table with yearly and daily Expenses data
$updateYearlyExpenses = "
    UPDATE 
        sales AS sr
    JOIN (
        SELECT 
            Product, 
            ExpensesYear,
            DayOfYear,
            SUM(DailyExpenses) AS DailyExpenses
        FROM 
            temp_yearly_Expenses
        GROUP BY 
            Product, ExpensesYear, DayOfYear
    ) AS temp
    ON 
        sr.Product = temp.Product 
        AND YEAR(sr.Date) = temp.ExpensesYear
        AND DAYOFYEAR(sr.Date) = temp.DayOfYear
    SET 
        sr.DailyExpenses = temp.DailyExpenses
";
$connection->query($updateYearlyExpenses);

// Drop temporary table for yearly Expenses
$dropTempYearlyTable = "DROP TEMPORARY TABLE IF EXISTS temp_yearly_Expenses";
$connection->query($dropTempYearlyTable);

// Fetch user records based on their Username
$stmt = $connection->prepare('SELECT * FROM sales WHERE Username = ?');
$stmt->bind_param('s', $Username);
$stmt->execute();
$result = $stmt->get_result();

// Function to fetch products with highest values
function fetchProductWithHighestValue($connection, $Username, $valueType) {
    $valueColumn = ($valueType === 'sales') ? 'TotalRevenue' : 'InventoryValue';
    $stmt = $connection->prepare("SELECT Product FROM sales WHERE Username = ? ORDER BY $valueColumn DESC LIMIT 1");
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
$HighestRevenue = fetchProductWithHighestValue($connection, $Username, 'sales');
$HighestInventoryValue = fetchProductWithLowestValue($connection, $Username, 'inventory');

// Function to fetch products with lowest values
function fetchProductWithLowestValue($connection, $Username, $valueType) {
    $valueColumn = ($valueType === 'sales') ? 'TotalRevenue' : 'InventoryValue';
    $stmt = $connection->prepare("SELECT Product FROM sales WHERE Username = ? ORDER BY $valueColumn ASC LIMIT 1");
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
$LowestRevenue = fetchProductWithLowestValue($connection, $Username, 'sales');
$LowestInventoryValue = fetchProductWithLowestValue($connection, $Username, 'inventory');


$stmt2 = $connection->prepare('SELECT category.categoryname AS categoryname, 
                                    GROUP_CONCAT(sales.Product) AS ProductNames, 
                                    SUM(sales.InventoryToSalesRatio) AS InventoryToSalesRatio, 
                                    SUM(sales.ReturnOnInvestment) AS ReturnOnInvestment, 
                                    SUM(sales.NetProfitMargin) AS NetProfitMargin, 
                                    SUM(sales.GrossProfitMargin) AS GrossProfitMargin
                                FROM sales
                                INNER JOIN category ON sales.sales_categoryid = category.id_category
                                WHERE sales.Username = ?
                                GROUP BY categoryname'); // Use categoryname instead of category.categoryname
$stmt2->bind_param('s', $Username);
$stmt2->execute();
$result2 = $stmt2->get_result(); // Corrected variable name

// Fetch data for the chart (e.g., products and net profit)
$data = array(
    'Category' => array(),
    'Products' => array(),
    'InventoryToSalesRatio' => array(),
    'ReturnOnInvestment' => array(),
    'NetProfitMargin' => array(),
    'GrossProfitMargin' => array(),
);

while ($row = $result2->fetch_assoc()) {
    $data['Category'][] = $row['categoryname'];
    $data['Products'][] = $row['ProductNames']; // Updated to use ProductNames
    $data['InventoryToSalesRatio'][] = $row['InventoryToSalesRatio'];
    $data['ReturnOnInvestment'][] = $row['ReturnOnInvestment'];
    $data['NetProfitMargin'][] = $row['NetProfitMargin'];
    $data['GrossProfitMargin'][] = $row['GrossProfitMargin'];
}


// Function to fetch products with highest and lowest Net profit
function fetchProductsByNetProfit($connection, $Username, $order) {
    $stmt = $connection->prepare("SELECT Product, NetProfit FROM sales WHERE Username = ? ORDER BY NetProfit $order LIMIT 1");
    $stmt->bind_param('s', $Username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return [
            'Product' => $row['Product'],
            'NetProfit' => $row['NetProfit'],
        ];
    } else {
        return [
            'Product' => 'No products found',
            'GrossProfit' => 0,
        ];
    }
}

// Fetch product with highest net profit
$highestNetProfitProduct = fetchProductsByNetProfit($connection, $Username, 'DESC');

// Fetch product with lowest net profit
$lowestNetProfitProduct = fetchProductsByNetProfit($connection, $Username, 'ASC');

// Re-enable safe update mode (recommended for security)
$enableSafeUpdateQuery = "SET SQL_SAFE_UPDATES = 1;";
$connection->query($enableSafeUpdateQuery);


// Fetch data for sales metrics with category information
$stmt_metrics = $connection->prepare('
    SELECT sales.*, category.categoryname
    FROM sales
    JOIN category ON sales.sales_categoryid = category.id_category
    WHERE sales.Username = ?
');
$stmt_metrics->bind_param('s', $Username);
$stmt_metrics->execute();
$result_metrics = $stmt_metrics->get_result();


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
    $stmt = $connection->prepare('SELECT COUNT(*) AS TotalProduct, SUM(InventoryValue) AS TotalInventoryValue FROM sales WHERE Username = ?');
    $stmt->bind_param('s', $Username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return [
            'TotalProduct' => $row['TotalProduct'],
            'TotalInventoryValue' => $row['TotalInventoryValue']
        ];
    } else {
        return [
            'TotalProduct' => 0,
            'TotalInventoryValue' => 0
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


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <link rel="icon" type="image/png" href="http://localhost/WEB/newlogo.png">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Dashboard</title>
</head>
<style>
  
  body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 0;
        background-color: #5f6e76;
    }


    main tr th {
        color: #456fec;
    }

    main .dashboard-container .dashboard-card {
        position: relative;
        left: -16px;
        height:105px;
        transform:translatex(411px) translatey(-72px);
        background-color:#f3e7bd;
        top:56px;
    }

  
     main .dashboard-container .dashboard-card h2{
     transform:translatex(221px) translatey(-16px);
     padding-top:1px;
     width:269px !important;
     overflow-y: auto;  /* Add this line to enable vertical scrolling */
     max-height: 300px;  /* Set the maximum height as per your design */
     color:#f6f7f8 !important;
    }

/* Chart container */
#chart-container{
 transform:translatex(3px) translatey(-5px);
 background-color:#dde0e3;
}

    #myChart{
     position:relative;
     top:-22px;
     transform:translatex(3px) translatey(25px);
    }


    header {
        color: #fff;
        text-align: center;
        padding: 20px;
        height:100px;
        min-height:1px;
        padding-bottom:60px;
        padding-top:30px;
        background-color:#3c3a33;
        transform:translatex(0px) translatey(0px);
    }

    /* Html report */
#htmlReport{
 position:relative;
 top:-18px;
}


/* Sales metrics */
main .sales-metrics{
 position:relative;
 top:17px;
}

/* Product metrics */
main .product-metrics{
 position:relative;
 top:16px;
}

/* Button */
main .button{
 position:relative;
 width:150px;
 height:40px;
 top:-37px;
 background-color:#132029;
}

    header h1 {
        margin: 0;
        font-size: 24px;
        background-color:rgba(0,0,0,0);
        font-family:'Courier New',Courier,'Lucida Sans Typewriter','Lucida Typewriter',monospace;
        font-size: 28px;
        margin-top: 10px;
        position: relative;
        top: -10px;
        left:-9px;
        transform:translatex(578px) translatey(-54px);
        width:292px !important;
        text-align:left;
        padding-top:1px;
        color:#f6f5f3;
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
        background-color:#f6f3f3;
        transform:translatex(-9px) translatey(-64px);
    }

    .dashboard-container .dashboard-card p{
      font-weight:550;
      padding-bottom:1px;
      position:relative;
      left:-10px;
      top:-42px;
      height:18px;
      text-align:right;
      top:-38px;
      color:#c6ccd1;
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

   

    header img {
        max-width: 150px;
        display: inline-block;
        width: 150px;
        transform:translatex(-169px) translatey(-19px);
        min-height:107px;
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

    .dashboard-container .dashboard-card h2{
     transform:translatex(69px) translatey(-24px) !important;
     text-align:left;
     font-weight:500;
     font-size:20px;
     position:relative;
     top:2px;
     left:43px;
    }

    main .dashboard-container{
    height:141px;
    background-color:#f6f3f3;
    transform:translatex(-18px) translatey(13px);
    position:relative;
    top:4px;
    }

/* Dashboard card */
main .dashboard-container .dashboard-card{
 transform:translatex(218px) translatey(-43px);
 height:87px;
 width:392px !important;
 top:47px;
 left:157px;
 background-color:#3c3a33;
}

/* Paragraph */
main .dashboard-container .dashboard-card p{
 width:77% !important;
}

main > div{
 text-align:center;
 font-style:italic;
 transform:translatex(0px) translatey(0px) !important;
 font-weight:600;
}

/* Division */
main div:nth-child(7){
 background-color:#c9cd7e;
}

/* Button */
.product-metrics a .button{
 top:7px;
}

/* Button */
.product-metrics form .button{
 left:2px;
 top:10px;
}

/* Button */
.button-container a .button{
 top:11px;
}

/* Heading */
main .product-metrics h2{
 color:#132029;
}

/* Th */
.product-metrics tr th{
 color:#132029;
}

/* Heading */
main .sales-metrics h2{
 color:#132029;
}

/* Th */
#inventoryTable tr th{
 color:#132029;
}

/* Scrollable sales metrics table */
.sales-metrics table {
    display: block;
    max-height: 300px; /* Adjust the max height as needed */
    overflow-y: auto;
}

/* Scrollable sales metrics table */
.product-metrics table {
    display: block;
    max-height: 300px; /* Adjust the max height as needed */
    overflow-y: auto;
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
                <h2>Product Performance</h2>
                <p>Total Product: <?php echo $totalData['TotalProduct']; ?></p>
                <p>Most Sold: <?php echo $HighestRevenue; ?></p>
                <p>Least Sold: <?php echo $LowestRevenue; ?></p>
                <p>Most Profitable: <?php echo $highestNetProfitProduct['Product']; ?></p>
                <p>Least Profitable: <?php echo $lowestNetProfitProduct['Product']; ?></p>
            </div>
        </div>
        <div  class="dashboard-card" id="chart-container">
        <canvas id="myChart" width="500" height="150"></canvas>
  <script>

     // Function to fetch labels from HTML report
     function fetchLabelsFromHTMLReport() {
    // Replace this code with your logic to fetch labels from the HTML report
    // For example, if the labels are stored in a div element with id "labels":
    var labelsDiv = document.getElementById('labels');
    var labels = labelsDiv.textContent.split(',');
    return labels;
} 

    var ctx = document.getElementById('myChart').getContext('2d');
    var chartData = <?php echo json_encode($data); ?>;

    var myChart = new Chart(ctx, {
        type: 'line', 
        data: {
            labels: [
                    "Tech Devices",
                    "Clothing Items",
                    "Food Items",
                    "Home Goods",
                    "Wellness Product",
                    "Automotives",
                    "Sports & Games",
                    "Other",
                    "Kids",
                    "Office Supplies"
        ],
            datasets: [
                
                
                {
    label: 'Inventory to Sales Ratio',
    data: chartData.InventoryToSalesRatio,
    backgroundColor: 'rgba(0, 0, 255, 0.2)',
    borderColor: 'rgba(0, 0, 255, 1)',
    borderWidth: 1
},
{
    label: 'Return On Investment',
    data: chartData.ReturnOnInvestment,
    backgroundColor: 'rgba(255, 0, 0, 0.2)',
    borderColor: 'rgba(255, 0, 0, 1)',
    borderWidth: 2,
    fill: false
},
{
    label: 'Net Profit Margin',
    data: chartData.NetProfitMargin,
    backgroundColor: 'rgba(0, 255, 0, 0.2)',
    borderColor: 'rgba(0, 255, 0, 1)',
    borderWidth: 2,
    fill: false
},
{
    label: 'Gross Profit Margin',
    data: chartData.GrossProfitMargin,
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
</div>
<div class="dashboard-card" id="htmlReport"></div>

        <button class="button" onclick="generateHTMLReport()">View Report</button>
        <button class="button" onclick="generatePDFReport()">Export as PDF</button>

        <script>
           function generateHTMLReport() {
    var chartData = <?php echo json_encode($data); ?>;
    var categoryNames = <?php echo json_encode($categoryNames); ?>;
    var reportContent = '<h2>Sales Analytics</h2>';
    reportContent += '<table>';
    reportContent += '<tr><th>Category</th><th>Product</th><th>InventoryToSalesRatio</th><th>Return On Investment</th><th>Net Profit Margin</th><th>Gross Profit Margin</th></tr>';
    
    for (var i = 0; i < chartData.Products.length; i++) {
        var categoryId = chartData.Category[i]; // Corrected variable name
        var categoryName = categoryNames[categoryId] || 'Category not found'; // Use categoryNames, handle if not found
        reportContent += '<tr>';
        reportContent += '<td>' + categoryName + '</td>';
        reportContent += '<td>' + chartData.Products[i] + '</td>';
        reportContent += '<td>' + chartData.InventoryToSalesRatio[i] + '</td>';
        reportContent += '<td>' + chartData.ReturnOnInvestment[i] + '</td>';
        reportContent += '<td>' + chartData.NetProfitMargin[i] + '</td>';
        reportContent += '<td>' + chartData.GrossProfitMargin[i] + '</td>';
        reportContent += '</tr>';
    }

    reportContent += '</table>';

    // Display the HTML report in the "htmlReport" div
    document.getElementById('htmlReport').innerHTML = reportContent;
}
            function generatePDFReport() {
                var chartData = <?php echo json_encode($data); ?>;
                var reportContent = "Sales Report\n\n";
                reportContent += "Product\tInventoryToSalesRatio\tReturn On Investment\tNet Profit\tGross Profit\n";

                for (var i = 0; i < chartData.Products.length; i++) {
                    reportContent += chartData.Products[i] + "\t" + chartData.WeeklyProfit[i] + "\t" + chartData.MonthlyProfit[i] + "\t" + chartData.NetProfit[i] + "\t" + chartData.GrossProfit[i] +"\n";
                }

                var doc = new jsPDF();
                doc.text(reportContent, 10, 10);

                // Trigger PDF download
                doc.save("sales_report.pdf");
            }
        </script>
 <div>
  <a>Key Performance Indicators --</a>
  <a>Inventory-to-Sales Ratio (ISR) - High ratio means more stock, low ratio means less stock.</a>
  <a>Return on Investment (ROI) - High ROI means more profit, low ROI means less profit.</a>
  <a>Net Profit Margin (NPM) - Indicates business profitability. Higher values mean better profits.</a>
  <a>Gross Profit Margin (GPM) - Higher values indicate better profitability.</a>
</div>

   
<div class="dashboard-card product-metrics">
        <h2>Product Metrics</h2>
        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <table> 
                <tr>
                    <th>Product</th>
                    <th>Daily Sales Qty</th>
                    <th>Weekly Sales Qty</th>
                    <th>Monthly Sales Qty</th>
                    <th>Yearly Sales Qty</th>
                    <th>Sales Price</th>
                </tr>
                <?php
                $result_metrics->data_seek(0);
                while ($row = $result_metrics->fetch_assoc()) :
                    ?>
                   <tr>
                      <td><input type="text" name="Product[]" value="<?php echo $row['Product']; ?>"></td>
                      <td><input type="text" name="DailySalesQty[]" value="<?php echo $row['DailySalesQty']; ?>"></td>
                     <td><input type="text" name="WeeklySalesQty[]" value="<?php echo $row['WeeklySalesQty']; ?>"></td>
                     <td><input type="text" name="MonthlySalesQty[]" value="<?php echo $row['MonthlySalesQty']; ?>"></td>
                     <td><input type="text" name="YearlySalesQty[]" value="<?php echo $row['YearlySalesQty']; ?>"></td>
                     <td>$<input type="text" name="SalesPrice[]" value="<?php echo $row['SalesPrice']; ?>"></td>
                   </tr>

                <?php endwhile; ?>
            </table>
            <button class="button" type="submit" name="update">Update</button>
        </form>
        <a href="create-sales.html"><button class="button">Add</button></a>
        <a href="daily-sales.php"><button class="button">Daily Sales</button></a>
        <a href="weekly-sales.php"><button class="button">Weekly Sales</button></a>
        <a href="monthly-sales.php"><button class="button">Monthly Sales</button></a>
        <a href="yearly-sales.php"><button class="button">Yearly Sales</button></a>
    </div>
</div>

<div class="dashboard-card sales-metrics">
    <h2>Sales Metrics</h2>
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <table id="inventoryTable">
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Total Revenue</th>
                <th>Inventory Value</th>
                <th>Net Profit</th>
                <th>Gross Profit</th>
                <th>Total Expenses</th>
                <th>Date</th>
            </tr>
            <?php
            $result_metrics->data_seek(0);
            while ($row = $result_metrics->fetch_assoc()) :
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['Product']); ?></td>
                    <td><?php echo fetchCategoryName($connection, $row['categoryname']); ?></td>
                    <td>$<?php echo number_format($row['TotalRevenue'], 2); ?></td>
                    <td>$<?php echo number_format($row['InventoryValue'], 2); ?></td>
                    <td>$<?php echo number_format($row['NetProfit'], 2); ?></td>
                    <td>$<?php echo number_format($row['GrossProfit'], 2); ?></td>
                    <td>$<?php echo number_format($row['YearlyExpenses'], 2); ?></td>
                    <td><?php echo date('Y-m-d H:i:s', strtotime($row['Date'])); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>

            </form>
          
        </div>
        <div class="button-container">
        <a href="product.php"><button class="button">Products</button></a>
        <a href="inventory.php"><button class="button">Inventory</button></a>
        <a href="sales-dashboard.php"><button class="button">Dashboard</button></a>
    </div>
    </main>
    <footer>
        <p>&copy; 2023 Sales Pilot</p>
    </footer>
</body>
</html>