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
// Fetch user records based on their Username
$stmt = $connection->prepare('SELECT * FROM business_records WHERE Username = ?');
$stmt->bind_param('s', $Username);
$stmt->execute();
$result = $stmt->get_result();


// Fetch user records based on their Username and group by category
$stmt = $connection->prepare('SELECT category.categoryname AS categoryname, 
    GROUP_CONCAT(sales.Product) AS ProductNames, 
    SUM(sales.TotalRevenue) AS TotalRevenue, 
    SUM(sales.InventoryValue) AS TotalInventoryValue, 
    SUM(sales.DailyRevenue) AS TotalDailyRevenue, 
    SUM(sales.MonthlyRevenue) AS TotalMonthlyRevenue, 
    SUM(sales.MonthlyProfit) AS TotalMonthlyProfit, 
    SUM(sales.DailyProfit) AS TotalDailyProfit, 
    SUM(sales.MonthlyExpenses) AS TotalMonthlyExpenses,
    SUM(sales.YearlyExpenses) AS TotalYearlyExpenses,
    (SUM(GrossProfit) - SUM(YearlyExpenses)) AS TotalNetProfit,
    (SUM(TotalRevenue) - SUM(InventoryValue)) AS TotalGrossProfit
    FROM sales
    INNER JOIN category ON sales.sales_categoryid = category.id_category
    WHERE sales.Username = ?
    GROUP BY categoryname');

$stmt->bind_param('s', $Username);
$stmt->execute();
$resultCategories = $stmt->get_result();

// Initialize data array
$data = array(
    'categoryname' => [],
    'ProductNames' => [],
    'DayOfWeek' => [],
    'TotalRevenue' => [],
    'TotalInventoryValue' => [],
    'TotalDailyRevenue' => [],
    'TotalMonthlyRevenue' => [],
    'TotalDailyProfit' => [],
    'TotalMonthlyProfit' => [],
    'TotalNetProfit' => [],
    'TotalGrossProfit' => [],
    'Month' => [],
    'TotalMonthlyExpenses' => [],
    'TotalYearlyExpenses' => []
);

// Process main query results
while ($row = $resultCategories->fetch_assoc()) {
    $data['categoryname'][] = $row['categoryname'];
    $data['ProductNames'][] = $row['ProductNames'];
    $data['TotalDailyProfit'][] = $row['TotalDailyProfit'];
    $data['TotalMonthlyProfit'][] = $row['TotalMonthlyProfit'];
    $data['TotalMonthlyExpenses'][] = $row['TotalMonthlyExpenses'];
    $data['TotalYearlyExpenses'][] = $row['TotalYearlyExpenses'];
    $data['TotalNetProfit'][] = $row['TotalNetProfit'];
    $data['TotalGrossProfit'][] = $row['TotalGrossProfit'];
    $data['TotalRevenue'][] = $row['TotalRevenue'];
    $data['TotalDailyRevenue'][] = $row['TotalDailyRevenue'];
    $data['TotalMonthlyRevenue'][] = $row['TotalMonthlyRevenue'];
    $data['TotalInventoryValue'][] = $row['TotalInventoryValue'];
}


// Function to fetch data from sales table
function fetchSalesData($connection, $Username, $field, $alias)
{
    $result = $connection->query("SELECT $field, $alias FROM sales WHERE Username = '$Username'");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[$field][] = $row[$field];
            $data[$alias][] = $row[$alias];
        }
    } else {
        die("Error fetching $field data: " . $connection->error);
    }
}

// Fetch monthly profit data
fetchSalesData($connection, $Username, 'Month', 'MonthlyProfit');

// Fetch monthly revenue data
fetchSalesData($connection, $Username, 'Month', 'MonthlyRevenue');

// Fetch monthly expenses data
fetchSalesData($connection, $Username, 'Month', 'MonthlyExpenses');


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
    $stmt = $connection->prepare('SELECT COUNT(*) AS TotalProduct, 
                                          SUM(InventoryValue) AS TotalInventoryValue,
                                          SUM(TotalRevenue) AS TotalRevenue,
                                          SUM(GrossProfit) AS GrossProfit,
                                          (SUM(GrossProfit) - SUM(YearlyExpenses)) AS TotalNetProfit,
                                          (SUM(TotalRevenue) - SUM(InventoryValue)) AS TotalGrossProfit,
                                          SUM(YearlyExpenses) AS TotalYearlyExpenses
                                   FROM sales 
                                   WHERE Username = ?');
    $stmt->bind_param('s', $Username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return [
            'TotalProduct' => $row['TotalProduct'],
            'TotalGrossProfit' => $row['TotalGrossProfit'],
            'TotalRevenue' => $row['TotalRevenue'],
            'TotalInventoryValue' => $row['TotalInventoryValue'],
            'TotalNetProfit' => $row['TotalNetProfit'],
            'TotalYearlyExpenses' => $row['TotalYearlyExpenses']
        ];
    } else {
        return [
            'TotalProduct' => 0,
            'TotalGrossProfit' => 0,
            'TotalRevenue' => 0,
            'TotalNetProfit' => 0,
            'TotalYearlyExpenses' => 0,
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
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="http://localhost/WEB/assets/img/apple-icon.png">
  <link rel="icon" type="image/png" href="http://localhost/WEB/newlogo.png">
  <title>
    Sales Overview
  </title>
  <!--     Fonts and icons     -->
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900|Roboto+Slab:400,700" />
  <!-- Nucleo Icons -->
  <link href="http://localhost/WEB/assets/css/nucleo-icons.css" rel="stylesheet" />
  <link href="http://localhost/WEB/assets/css/nucleo-svg.css" rel="stylesheet" />
  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <!-- Material Icons -->
  <link href="https://fonts.googleapis.com/icon?family=Material+Icons+Round" rel="stylesheet">
  <!-- CSS Files -->
  <link id="pagestyle" href="http://localhost/WEB/assets/css/material-dashboard.css?v=3.1.0" rel="stylesheet" />
  <!-- Nepcha Analytics (nepcha.com) -->
  <!-- Nepcha is a easy-to-use web analytics. No cookies and fully compliant with GDPR, CCPA and PECR. -->
  <script defer data-site="YOUR_DOMAIN_HERE" src="https://api.nepcha.com/js/nepcha-analytics.js"></script>
  <style>
    /* Card */
.main-content .col-lg-4:nth-child(4) .card{
 transform:translatex(710px) translatey(-307px);
  background-color:#f9f0f0;
 top:-325px;
 height:594px;
}

/* Text secondary */
#expenditureTable tr .text-secondary{
 background-color:#eed7d7;
 transform:translatex(0px) translatey(0px);
 font-size:13px !important;
 font-style:normal;
 line-height:1.3em;
}

/* Text secondary */
#expenditureTable tr .text-secondary:nth-child(3){
 background-color:#ceeac2;
}

/* Text secondary */
#expenditureTable tr .text-secondary:nth-child(4){
 background-color:#cad6ec;
}

/* Text secondary */
#expenditureTable tr .text-secondary:nth-child(5){
 background-color:#dae07e;
}

/* Text secondary */
#expenditureTable tr .text-secondary:nth-child(2){
 background-color:#f0b7e1;
}

/* Column 6/12 */
.mb-md-0 .card .col-7{
 transform:translatex(0px) translatey(0px);
 background-color:#fbb7ab;
}


/* Th */
.col-lg-4 .card .card-body .table thead tr th{
 background-color:#bae0ec;
}

/* Row */
.mb-md-0 .card .row{
 transform:translatex(0px) translatey(0px);
 background-color:#fab7b7;
}

/* Card header */
.mb-md-0 .card .card-header{
 background-color:#ed8787;
 transform:translatex(0px) translatey(0px);
 min-height:97px;
}


@media (min-width:992px){

 /* Column 6/12 */
 .main-content .container-fluid .row .mb-md-0 .card .card-header .row .col-7{
  width:33% !important;
 }
 
}
  </style>
</head>

<body class="g-sidenav-show bg-gray-200">
  <aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3 bg-gradient-dark" id="sidenav-main">
    <div class="sidenav-header">
      <i class="fas fa-times p-3 cursor-pointer text-white opacity-5 position-absolute end-0 top-0 d-none d-xl-none" aria-hidden="true" id="iconSidenav"></i>
      <a class="navbar-brand m-0" href="http://localhost/WEB/sales-dashboard.php" target="_blank">
        <img src="http://localhost/WEB/salespilot.png" class="navbar-brand-img h-100" alt="main_logo">
        <span class="ms-1 font-weight-bold text-white"><?php echo $_SESSION['Username']; ?></span>
      </a>
    </div>
    <hr class="horizontal light mt-0 mb-2">
    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link text-white active bg-gradient-primary" href="http://localhost/WEB/create-sales.html">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">money</i>
            </div>
            <span class="nav-link-text ms-1">Sales</span>
          </a>
        </li>
        <li class="nav-item mt-3">
          <h6 class="ps-4 ms-2 text-uppercase text-xs text-white font-weight-bolder opacity-8">Menu</h6>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="http://localhost/WEB/inventory-dashboard.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">inventory</i>
            </div>
            <span class="nav-link-text ms-1">Inventory</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="http://localhost/WEB/sales-analytics.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">analytics</i>
            </div>
            <span class="nav-link-text ms-1">Sales Analytics</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="http://localhost/WEB/sales.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">assignment</i>
            </div>
            <span class="nav-link-text ms-1">Sales Reports</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-white" href="http://localhost/WEB/profile.php">
            <div class="text-white text-center me-2 d-flex align-items-center justify-content-center">
              <i class="material-icons opacity-10">face</i>
            </div>
            <span class="nav-link-text ms-1">Profile</span>
          </a>
        </li>
      </ul>
    </div>
  </aside>
</body>

  
    </div>
  </aside>
  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
    <!-- Navbar -->
    <nav class="navbar navbar-main navbar-expand-lg px-0 mx-4 shadow-none border-radius-xl" id="navbarBlur" data-scroll="true">
  <div class="container-fluid py-1 px-3">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
        <li class="breadcrumb-item text-sm"><a class="opacity-5 text-dark" href="javascript:;"></a></li>
        <li class="breadcrumb-item text-sm text-dark active" aria-current="page"></li>
      </ol>
      <h6 class="font-weight-bolder mb-0">Sales Dashboard</h6>
      
    </nav>
  </div>
  <div>
    <ul class="navbar-nav">
      <li class="nav-item d-flex align-items-center">
        <a href="http://localhost/WEB/logout.php" class="nav-link text-body font-weight-bold px-0">
          <i class="material-icons opacity-10">face</i>
          <span class="d-sm-inline d-none"><?php echo $_SESSION['Username'];?></span>
          Logout
        </a>
      </li>
    </ul>
  </div>
</nav>

    <!-- End Navbar -->
    <div class="container-fluid py-4">
      <div class="row">
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
          <div class="card">
            <div class="card-header p-3 pt-2">
              <div class="icon icon-lg icon-shape bg-gradient-dark shadow-dark text-center border-radius-xl mt-n4 position-absolute">
                <i class="material-icons opacity-10">money</i>
              </div>
              <div class="text-end pt-1">
                <p class="text-sm mb-0 text-capitalize">Total Revenue</p>
                <h4 class="mb-0">$<?php echo number_format($totalData['TotalRevenue']); ?></h4>
              </div>
            </div>
            <hr class="dark horizontal my-0">
            <div class="card-footer p-3">
              <p class="mb-0"><span class="text-success text-sm font-weight-bolder"></span>Total Sales Revenue</p>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
          <div class="card">
            <div class="card-header p-3 pt-2">
              <div class="icon icon-lg icon-shape bg-gradient-primary shadow-primary text-center border-radius-xl mt-n4 position-absolute">
                <i class="material-icons opacity-10">money</i>
              </div>
              <div class="text-end pt-1">
                <p class="text-sm mb-0 text-capitalize">Gross Profit</p>
                <h4 class="mb-0">$<?php echo number_format($totalData['TotalGrossProfit']); ?></h4>
              </div>
            </div>
            <hr class="dark horizontal my-0">
            <div class="card-footer p-3">
              <p class="mb-0"><span class="text-success text-sm font-weight-bolder"></span>Estimated Total Profit</p>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
          <div class="card">
            <div class="card-header p-3 pt-2">
              <div class="icon icon-lg icon-shape bg-gradient-success shadow-success text-center border-radius-xl mt-n4 position-absolute">
                <i class="material-icons opacity-10">money</i>
              </div>
              <div class="text-end pt-1">
                <p class="text-sm mb-0 text-capitalize">Total Expenses</p>
                <h4 class="mb-0">$<?php echo number_format($totalData['TotalYearlyExpenses']); ?></h4>
              </div>
            </div>
            <hr class="dark horizontal my-0">
            <div class="card-footer p-3">
              <p class="mb-0"><span class="text-danger text-sm font-weight-bolder"></span>Total Annual Expenses</p>
            </div>
          </div>
        </div>
        <div class="col-xl-3 col-sm-6">
          <div class="card">
            <div class="card-header p-3 pt-2">
              <div class="icon icon-lg icon-shape bg-gradient-info shadow-info text-center border-radius-xl mt-n4 position-absolute">
                <i class="material-icons opacity-10">money</i>
              </div>
              <div class="text-end pt-1">
                <p class="text-sm mb-0 text-capitalize">Net Profit</p>
                <h4 class="mb-0">$<?php echo number_format($totalData['TotalNetProfit']); ?></h4>
              </div>
            </div>
            <hr class="dark horizontal my-0">
            <div class="card-footer p-3">
              <p class="mb-0"><span class="text-success text-sm font-weight-bolder"></span>Actual Profit in a Year</p>
            </div>
          </div>
        </div>
      </div>
      <div class="row mt-4">
        <div class="col-lg-4 col-md-6 mt-4 mb-4">
          <div class="card z-index-2 ">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
              <div class="bg-gradient-primary shadow-primary border-radius-lg py-3 pe-1">
                <div class="chart">
                  <canvas id="chart-bars" class="chart-canvas" height="170"></canvas>
                </div>
              </div>
            </div>
            <div class="card-body">
              <h6 class="mb-0 ">Monthly Expenses</h6>
              <p class="text-sm ">Total Monthly Expenses</p>
              <hr class="dark horizontal">
              <div class="d-flex ">
                <i class="material-icons text-sm my-auto me-1">schedule</i>
                <p class="mb-0 text-sm">Monthly Expenses for all Products</p>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4 col-md-6 mt-4 mb-4">
          <div class="card z-index-2  ">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
              <div class="bg-gradient-success shadow-success border-radius-lg py-3 pe-1">
                <div class="chart">
                  <canvas id="chart-line" class="chart-canvas" height="170"></canvas>
                </div>
              </div>
            </div>
            <div class="card-body">
              <h6 class="mb-0 ">Monthly Revenue</h6>
              <p class="text-sm "><span class="font-weight-bolder"></span>Total Monthly Revenue</p>
              <hr class="dark horizontal">
              <div class="d-flex ">
                <i class="material-icons text-sm my-auto me-1">schedule</i>
                <p class="mb-0 text-sm">Monthly Revenue for all products</p>
              </div>
            </div>
          </div>
        </div>
        <div class="col-lg-4 mt-4 mb-3">
          <div class="card z-index-2 ">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
              <div class="bg-gradient-dark shadow-dark border-radius-lg py-3 pe-1">
                <div class="chart">
                  <canvas id="chart-line-tasks" class="chart-canvas" height="170"></canvas>
                </div>
              </div>
            </div>
            <div class="card-body">
              <h6 class="mb-0 ">Monthly Profit</h6>
              <p class="text-sm ">Total Monthly Profit</p>
              <hr class="dark horizontal">
              <div class="d-flex ">
                <i class="material-icons text-sm my-auto me-1">schedule</i>
                <p class="mb-0 text-sm">Monthly Profit for all Products</p>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="row mb-4">
    <div class="col-lg-8 col-md-6 mb-md-0 mb-4">
        <div class="card">
            <div class="card-header pb-0">
                <div class="row">
                    <div class="col-lg-6 col-7">
                        <h6>Expenditure Overview</h6>
                        <p class="text-sm mb-0">
                            <i class="fa fa-check text-info" aria-hidden="true"></i>
                            <span class="font-weight-bold ms-1">Expenditure by Category</span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="card-body px-0 pb-2">
                <div class="table-responsive">
                    <table class="table align-items-center mb-0" id="expenditureTable">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-10">Products</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-10 ps-2">Category</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-10 sortable" data-sort="gross-profit">Gross Profit</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-10 sortable" data-sort="net-profit">Net Profit</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-10 sortable" data-sort="expenses">Expenses</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Loop through the fetched data and populate the table rows
                            foreach ($data['categoryname'] as $index => $categoryname) {
                                echo '<tr>';
                                echo '<td>' . $data['ProductNames'][$index] . '</td>';
                                echo '<td>' . fetchCategoryName($connection, $categoryname) . '</td>';
                                echo '<td class="text-center">$' . number_format($data['TotalGrossProfit'][$index]) . '</td>';
                                echo '<td class="text-center">$' . number_format($data['TotalNetProfit'][$index]) . '</td>';
                                echo '<td class="text-center">$' . number_format($data['TotalYearlyExpenses'][$index]) . '</td>';
                                echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
        <div class="col-lg-4 col-md-6">
  <div class="card h-200">
    <div class="card-header pb-0">
      <h6>Highest Earning Categories</h6>
      <p class="text-sm">
        <i class="fa fa-arrow-up text-success" aria-hidden="true"></i>
        <span class="font-weight-bold">Total Revenue by Category</span>
      </p>
    </div>
    <div class="card-body p-3">
      <table class="table">
        <thead>
          <tr>
            <th>Category</th>
            <th class="text-center">Revenue</th>
          </tr>
        </thead>
        <tbody>
           <?php
                    // Combine category names and total revenues into an associative array
                    $categoryRevenue = array_combine($data['categoryname'], $data['TotalRevenue']);

                    // Sort the array in descending order based on total revenue
                    arsort($categoryRevenue);

                    // Loop through the sorted data and populate the table rows
                    foreach ($categoryRevenue as $categoryname => $totalRevenue) {
                        echo '<tr>';
                        echo '<td>' . fetchCategoryName($connection, $categoryname) . '</td>';
                        echo '<td class="text-center">$' . number_format($totalRevenue) . '</td>';
                        echo '</tr>';
                    }
            ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

      <footer class="footer py-4  ">
        <div class="container-fluid">
          <div class="row align-items-center justify-content-lg-between">
            <div class="col-lg-6 mb-lg-0 mb-4">
              <div class="copyright text-center text-sm text-muted text-lg-start">
                © <script>
                  document.write(new Date().getFullYear())
                </script>,
                made with <i class="fa fa-heart"></i> by
                <a href="https://phemcode.nicepage.io" class="font-weight-bold" target="_blank">Sales Pilot</a>
                for a better web.
              </div>
            </div>
            <div class="col-lg-6">
              <ul class="nav nav-footer justify-content-center justify-content-lg-end">
                <li class="nav-item">
                  <a href="https://phemcode.nicepage.io" class="nav-link text-muted" target="_blank">Sales Pilot</a>
                </li>
                <li class="nav-item">
                  <a href="https://phemcode.nicepage.io/Home.html" class="nav-link text-muted" target="_blank">About Us</a>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </footer>
    </div>
  </main>        
  <!--   Core JS Files   -->
  <script src="http://localhost/WEB/assets/js/core/popper.min.js"></script>
  <script src="http://localhost/WEB/assets/js/core/bootstrap.min.js"></script>
  <script src="http://localhost/WEB/assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="http://localhost/WEB/assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script src="http://localhost/WEB/assets/js/plugins/chartjs.min.js"></script>
  <script>
    var ctx = document.getElementById("chart-bars").getContext("2d");
    var chartData = <?php echo json_encode($data); ?>;
    new Chart(ctx, {
      type: "bar",
      data: {
        labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
        datasets: [{
          label: "Monthly Expenses",
          tension: 0.4,
          borderWidth: 0,
          borderRadius: 4,
          borderSkipped: false,
          backgroundColor: "rgba(255, 255, 255, .8)",
          data: chartData.TotalMonthlyExpenses,
          maxBarThickness: 6
        }, ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          }
        },
        interaction: {
          intersect: false,
          mode: 'index',
        },
        scales: {
          y: {
            grid: {
              drawBorder: false,
              display: true,
              drawOnChartArea: true,
              drawTicks: false,
              borderDash: [5, 5],
              color: 'rgba(255, 255, 255, .2)'
            },
            ticks: {
              suggestedMin: 0,
              suggestedMax: 500,
              beginAtZero: true,
              padding: 10,
              font: {
                size: 14,
                weight: 300,
                family: "Roboto",
                style: 'normal',
                lineHeight: 2
              },
              color: "#fff"
            },
          },
          x: {
            grid: {
              drawBorder: false,
              display: true,
              drawOnChartArea: true,
              drawTicks: false,
              borderDash: [5, 5],
              color: 'rgba(255, 255, 255, .2)'
            },
            ticks: {
              display: true,
              color: '#f8f9fa',
              padding: 10,
              font: {
                size: 14,
                weight: 300,
                family: "Roboto",
                style: 'normal',
                lineHeight: 2
              },
            }
          },
        },
      },
    });


    var ctx2 = document.getElementById("chart-line").getContext("2d");
var chartData = <?php echo json_encode($data); ?>;
new Chart(ctx2, {
  type: "line",
  data: {
    labels: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
    datasets: [{
      label: "Monthly Revenue",
      tension: 0,
      borderWidth: 4,
      pointRadius: 5,
      pointBackgroundColor: "rgba(255, 255, 255, .8)",
      pointBorderColor: "transparent",
      borderColor: "rgba(255, 255, 255, .8)",
      backgroundColor: "transparent",
      fill: true,
      data: chartData.TotalMonthlyRevenue,
      maxBarThickness: 6
    }],
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: {
        display: false,
      }
    },
    interaction: {
      intersect: false,
      mode: 'index',
    },
    scales: {
      y: {
        grid: {
          drawBorder: false,
          display: true,
          drawOnChartArea: true,
          drawTicks: false,
          borderDash: [5, 5],
          color: 'rgba(255, 255, 255, .2)'
        },
        ticks: {
          display: true,
          color: '#f8f9fa',
          padding: 10,
          font: {
            size: 14,
            weight: 300,
            family: "Roboto",
            style: 'normal',
            lineHeight: 2
          },
        }
      },
      x: {
        grid: {
          drawBorder: false,
          display: false,
          drawOnChartArea: false,
          drawTicks: false,
          borderDash: [5, 5]
        },
        ticks: {
          display: true,
          color: '#f8f9fa',
          padding: 10,
          font: {
            size: 14,
            weight: 300,
            family: "Roboto",
            style: 'normal',
            lineHeight: 2
          },
        }
      },
    },
  },
});


    var ctx3 = document.getElementById("chart-line-tasks").getContext("2d");

    new Chart(ctx3, {
      type: "line",
      data: {
        labels: ["Jan","Feb","Mar","Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
        datasets: [{
          label: "Monthly Profit",
          tension: 0,
          borderWidth: 0,
          pointRadius: 5,
          pointBackgroundColor: "rgba(255, 255, 255, .8)",
          pointBorderColor: "transparent",
          borderColor: "rgba(255, 255, 255, .8)",
          borderWidth: 4,
          backgroundColor: "transparent",
          fill: true,
          data: chartData.TotalMonthlyProfit,
          maxBarThickness: 6

        }],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false,
          }
        },
        interaction: {
          intersect: false,
          mode: 'index',
        },
        scales: {
          y: {
            grid: {
              drawBorder: false,
              display: true,
              drawOnChartArea: true,
              drawTicks: false,
              borderDash: [5, 5],
              color: 'rgba(255, 255, 255, .2)'
            },
            ticks: {
              display: true,
              padding: 10,
              color: '#f8f9fa',
              font: {
                size: 14,
                weight: 300,
                family: "Roboto",
                style: 'normal',
                lineHeight: 2
              },
            }
          },
          x: {
            grid: {
              drawBorder: false,
              display: false,
              drawOnChartArea: false,
              drawTicks: false,
              borderDash: [5, 5]
            },
            ticks: {
              display: true,
              color: '#f8f9fa',
              padding: 10,
              font: {
                size: 14,
                weight: 300,
                family: "Roboto",
                style: 'normal',
                lineHeight: 2
              },
            }
          },
        },
      },
    });
  </script>

  
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
    $(document).ready(function () {
        // Function to sort the table
        function sortTable(table, column, order) {
            var rows = table.find('tbody > tr').get();
            rows.sort(function (a, b) {
                var keyA = parseFloat($(a).children('td').eq(column).text().replace('$', '').replace(',', ''));
                var keyB = parseFloat($(b).children('td').eq(column).text().replace('$', '').replace(',', ''));
                if (order === 'asc') {
                    return keyA - keyB;
                } else {
                    return keyB - keyA;
                }
            });
            $.each(rows, function (index, row) {
                table.children('tbody').append(row);
            });
        }

        // Enable sorting when header is clicked
        $('.sortable').click(function () {
            var table = $('#expenditureTable');
            var column = $(this).index();
            var order = $(this).hasClass('asc') ? 'desc' : 'asc';

            // Remove existing classes
            $('.sortable').removeClass('asc desc');
            // Add the new order class
            $(this).addClass(order);

            // Call the sort function
            sortTable(table, column, order);
        });
    });
</script>

  
</body>

</html>