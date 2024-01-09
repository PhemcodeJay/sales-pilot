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

// Fetch category names and IDs
$query = "SELECT id_category, categoryname FROM category";
$result = $connection->query($query);

$categoryNames = array();

while ($row = $result->fetch_assoc()) {
    $categoryNames[$row['id_category']] = $row['categoryname'];
}

// Fetch user records based on their Username and group by category
$query = "SELECT category.categoryname AS categoryname, GROUP_CONCAT(inventory.Product) AS ProductNames, 
                 SUM(inventory.TotalRevenue) AS TotalRevenue, SUM(inventory.InventoryQty) AS TotalInventoryQty, 
                 SUM(inventory.StockValue) AS TotalStockValue, SUM(inventory.SupplyValue) AS TotalSupplyValue
          FROM inventory
          INNER JOIN category ON inventory.category_id = category.id_category
          WHERE inventory.Username = ?
          GROUP BY categoryname";

$stmt = $connection->prepare($query);
$stmt->bind_param('s', $Username);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the data and store it in an associative array
$productCategoryData = array();
while ($row = $result->fetch_assoc()) {
    $productCategoryData[] = $row;
}


// Fetch and store the inventory data
$query = "SELECT i.Product, i.Description, c.categoryname, i.CostPrice, i.SalesPrice, SUM(InventoryQty) AS StockQty, 
          SUM(CostPrice) AS TotalCost, SUM(SalesPrice) AS TotalSales, SUM(StockQty) AS StockValue, 
          SUM(TotalRevenue) AS Revenue
          FROM inventory AS i
          INNER JOIN category AS c ON i.category_id = c.id_category
          WHERE i.Username = ?
          GROUP BY i.Product, i.Description, c.categoryname, i.CostPrice, i.SalesPrice";

$stmt = $connection->prepare($query);
$stmt->bind_param('s', $Username);
$stmt->execute();
$result = $stmt->get_result();

// Initialize an array to store the fetched data
$inventoryData = array();
while ($row = $result->fetch_assoc()) {
    $inventoryData[] = $row;
}


$pieChartLabels = array();
$pieChartData = array();

// Create an associative array to store category-wise total revenue
$categoryRevenue = array();

foreach ($inventoryData as $row) {
    $category_name = $row['categoryname']; // Get the category name
    $total_revenue = $row['Revenue'];
    // Format the total revenue with dollar sign and commas
    $formatted_total_revenue = '$' . number_format($total_revenue, 2, '.', ',');

    // If the category is not in the list of labels, add it
    if (!in_array($category_name, $pieChartLabels)) {
        $pieChartLabels[] = $category_name;
    }

    // If the category is not in the list of category revenues, initialize it
    if (!isset($categoryRevenue[$category_name])) {
        $categoryRevenue[$category_name] = 0;
    }

    // Sum the TotalRevenue for products in the same category
    $categoryRevenue[$category_name] += $total_revenue;
}

// Get the total revenue for each category and maintain the original order
foreach ($pieChartLabels as $category_name) {
    $pieChartData[] = $categoryRevenue[$category_name];
}


$query2 = "SELECT category.categoryname AS categoryname, SUM(inventory.InventoryQty) AS TotalInventoryQty
          FROM inventory
          INNER JOIN category ON inventory.category_id = category.id_category
          WHERE inventory.Username = ?
          GROUP BY categoryname";
$stmt2 = $connection->prepare($query2);
$stmt2->bind_param('s', $Username);
$stmt2->execute();
$result2 = $stmt2->get_result();


$barChartLabels = array();
$barChartData = array();

// Create an associative array to store category-wise total inventory quantity
$categoryInventoryQty = array();

foreach ($result2 as $row) {  // Use $result2 instead of $data to fetch the results
    $category_name = $row['categoryname']; // Get the category name
    $total_inventory_qty = $row['TotalInventoryQty'];

    // If the category is not in the list of labels, add it
    if (!in_array($category_name, $barChartLabels)) {
        $barChartLabels[] = $category_name;
    }

    // If the category is not in the list of category inventory quantities, initialize it
    if (!isset($categoryInventoryQty[$category_name])) {
        $categoryInventoryQty[$category_name] = 0;
    }

    // Sum the InventoryQty for products in the same category
    $categoryInventoryQty[$category_name] += $total_inventory_qty;
}

// Get the total inventory quantity for each category and maintain the original order
foreach ($barChartLabels as $category_name) {
    $barChartData[] = $categoryInventoryQty[$category_name];
}

// Fetch and store the inventory data
$query = "SELECT i.Product, i.Description, c.categoryname, i.CostPrice, i.SalesPrice, SUM(InventoryQty) AS StockQty, 
          SUM(CostPrice) AS TotalCost, SUM(SalesPrice) AS TotalSales, SUM(StockQty) AS StockValue, 
          SUM(TotalRevenue) AS Revenue, SUM(YearlySalesQty) AS YearlySalesQty
          FROM inventory AS i
          INNER JOIN category AS c ON i.category_id = c.id_category
          WHERE i.Username = ?
          GROUP BY i.Product, i.Description, c.categoryname, i.CostPrice, i.SalesPrice";

$stmt = $connection->prepare($query);


// Use bind_param to bind parameters safely
$stmt->bind_param('s', $Username);

// Execute the query
$stmt->execute();

// Get the result set
$result = $stmt->get_result();

// Initialize an array to store the fetched data
$inventoryData = array();

// Fetch data and store it in the array
while ($row = $result->fetch_assoc()) {
    $inventoryData[] = $row;
}

// Now, let's sort the $inventoryData array based on YearlySalesQty in descending order
usort($inventoryData, function ($a, $b) {
    return $b['YearlySalesQty'] - $a['YearlySalesQty'];
});

// Now $inventoryData contains the top-selling products based on YearlySalesQty
// You can use this array to display the information as needed

// Fetch user records based on their Username and group by category and date
$query = "SELECT category.categoryname AS categoryname, 
                 SUM(inventory.InventoryQty) AS InventoryQty, 
                 SUM(inventory.StockQty) AS StockQty, 
                 SUM(inventory.SupplyQty) AS SupplyQty
          FROM inventory
          INNER JOIN category ON inventory.category_id = category.id_category
          WHERE inventory.Username = ?
          GROUP BY categoryname";

$stmt = $connection->prepare($query);
$stmt->bind_param('s', $Username);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the data and store it in an associative array
$categoryQtyData = array();
while ($row = $result->fetch_assoc()) {
    $categoryQtyData[] = $row;
}


$lineChartLabels = array();
$inventoryQtyData = array();
$StockQtyData = array();
$supplyQtyData = array();

foreach ($categoryQtyData as $row) {
    $inventoryQty = $row['InventoryQty'];
    $StockQty = $row['StockQty'];
    $SupplyQty = $row['SupplyQty'];
    $lineChartLabels[] = $row['categoryname'];
    $inventoryQtyData[] = $inventoryQty;
    $StockQtyData[] = $StockQty;
    $supplyQtyData[] = $SupplyQty;
}

$connection->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Inventory Reports</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,700">
    <!-- https://fonts.google.com/specimen/Roboto -->
    <link rel="stylesheet" href="http://localhost/WEB/css/fontawesome.min.css">
    <!-- https://fontawesome.com/ -->
    <link rel="stylesheet" href="http://localhost/WEB/css/bootstrap.min.css">
    <!-- https://getbootstrap.com/ -->
    <link rel="stylesheet" href="http://localhost/WEB/css/templatemo-style.css">
    <!--
	Product Admin CSS Template
	https://templatemo.com/tm-524-product-admin
	-->
<style>
    /* Font Icon */
#navbarSupportedContent .active i{
 position:relative;
 top:3px;
 left:-3px;
}

/* Font Icon */
#navbarDropdown .fa-file-alt{
 position:relative;
 left:-3px;
 top:3px;
}

/* Font Icon */
#navbarDropdown span i{
 display:none;
}

/* Font Icon */
#navbarSupportedContent .nav-item .fa-shopping-cart{
 position:relative;
 top:3px;
 left:-3px;
}

/* Font Icon */
#navbarSupportedContent .nav-item .fa-user{
 position:relative;
 top:3px;
 left:-3px;
}

/* Block */
#home .tm-block-col:nth-child(2) .tm-block{
 transform:translatex(66px) translatey(-28px);
}

/* Block */
#home .tm-block-col:nth-child(1) .tm-block{
 transform:translatex(-103px) translatey(-27px);
}

/* Block */
#home .container .tm-content-row .tm-block-col:nth-child(1) .tm-block{
 width:133% !important;
}

/* Line chart */
#lineChart{
 transform:translatex(-30px) translatey(-27px);
 width:109% !important;
}

/* Bar chart */
#barChart{
 position:relative;
 left:-5px;
 width:506px !important;
 transform:translatex(-28px) translatey(-36px);
 min-height:358px;
}

/* Block taller */
#home .tm-block-col:nth-child(3) .tm-block-taller{
 transform:translatex(-96px) translatey(-72px);
 width:707px;
 left:-67px !important;
}

/* Pie chart container */
#pieChartContainer{
 width:99% !important;
 transform:translatex(-11px) translatey(-59px);
}

/* Pie chart */
#pieChart{
 width:618px !important;
 transform:translatex(-13px) translatey(49px);
 height:360px !important;
}

/* Block overflow */
#home .tm-block-col .tm-block-overflow{
 top:-74px !important;
 padding-right:56px;
}

/* Block taller */
#home .tm-block-col .tm-block-taller{
 transform:translatex(-37px) translatey(-3px) !important;
 background-color:#97b1c3;
 top:7px !important;
 left:-42px !important;
}

/* Block taller */
#home .tm-block-col:nth-child(5) .tm-block-taller{
 position:relative;
 transform:translatex(-34px) translatey(-10px) !important;
 top:44px !important;
 left:-71px !important;
}

/* Block taller */
#home .container .tm-content-row .tm-block-col:nth-child(5) .tm-block-taller{
 width:731px !important;
}

/* Block taller */
#home .tm-block-taller:nth-child(3){
 width:529px;
 transform:translatex(814px) translatey(-369px);
 position:relative;
 top:-44px;
 left:-37px;
}

/* Block taller */
#home .tm-block-taller{
 background-color:#97b1c3;
}

/* Block taller */
#home .container .tm-content-row .tm-block-col .tm-block-taller{
 width:652px !important;
}

/* Block overflow */
#home .container .tm-content-row .tm-block-col .tm-block-overflow{
 transform:translatex(59px) translatey(0px) !important;
 left:-34px !important;
 width:588px !important;
}

/* Paragraph */
.tm-mt-small p{
 color:#221f1f !important;
 font-weight:500;
}

/* Block taller */
#home .tm-block-col:nth-child(4) .tm-block-taller{
 transform:translatex(76px) translatey(-5px) !important;
 left:-67px !important;
}

/* Block taller */
#home .container .tm-content-row .tm-block-col:nth-child(4) .tm-block-taller{
 width:599px !important;
}

</style>
</head>

<body id="reportsPage">
    <div class="" id="home">
        <nav class="navbar navbar-expand-xl">
            <div class="container h-100">
                <a class="navbar-brand" href="inventory-dashboard.php">
                    <h1 class="tm-site-title mb-0">Dashboard</h1>
                </a>
                <button class="navbar-toggler ml-auto mr-0" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                    aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="fas fa-bars tm-nav-icon"></i>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav mx-auto h-100">
                    <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="inventorynew.php" id="navbarDropdown" role="button" data-toggle="dropdown"
                                    aria-haspopup="true" aria-expanded="false">
                                    <i class="far fa-file-alt"></i>
                                    <span>
                                        New <i class="fas fa-angle-down"></i>
                                    </span>
                                </a>
                                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                    <a class="dropdown-item" href="create-product.html">Product</a>
                                    <a class="dropdown-item" href="create-sales.html">Sales</a>
                                    <a class="dropdown-item" href="#">New Staff</a>
                                    <a class="dropdown-item" href="#">New Customer</a>
                                </div>
                                </li>
                        <li class="nav-item dropdown">

                            <a class="nav-link dropdown-toggle" href="inventorynew.php" id="navbarDropdown" role="button" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                <i class="far fa-file-alt"></i>
                                <span>
                                    Reports <i class="fas fa-angle-down"></i>
                                </span>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="inventory.php">All-Time</a>
                                <a class="dropdown-item" href="weeklyreport.php">Weekly</a>
                                <a class="dropdown-item" href="monthlyreport.php">Monthly</a>
                                <a class="dropdown-item" href="yearlyreport.php">Yearly</a>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="product.php">
                                <i class="fas fa-shopping-cart"></i>
                                Products
                            </a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
                                <i class="far fa-user"></i>
                                Accounts
                            </a>
                        </li>
                    </ul>
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link d-block" href="loginpage.php">
                                <?php
                                $Username = $_SESSION['Username'];
                                echo "Welcome, $Username <b>Logout</b>";
                                ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

        </nav>
        <div class="container">
            <div class="row">
                <div class="col">
                    <p class="text-white mt-5 mb-5"></p>
                </div>
            </div>
            <!-- row -->
            <div class="row tm-content-row">
                <div class="col-sm-12 col-md-12 col-lg-6 col-xl-6 tm-block-col">
                    <div class="tm-bg-primary-dark tm-block">
                        <h2 class="tm-block-title">Stock Overview</h2>
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>
                <div class="col-sm-12 col-md-12 col-lg-6 col-xl-6 tm-block-col">
                    <div class="tm-bg-primary-dark tm-block">
                        <h2 class="tm-block-title">Category Qty</h2>
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
                <div class="col-sm-12 col-md-12 col-lg-6 col-xl-6 tm-block-col">
                    <div class="tm-bg-primary-dark tm-block tm-block-taller">
                        <h2 class="tm-block-title">Category Value ($)</h2>
                        <div id="pieChartContainer">
                            <canvas id="pieChart" class="chartjs-render-monitor" width="200" height="200"></canvas>
                        </div>                        
                    </div>
                </div>
                    <!-- Display the top 10 selling items information in HTML with scrolling effects -->
                <div class="col-sm-12 col-md-12 col-lg-6 col-xl-6 tm-block-col">
                    <div class="tm-bg-primary-dark tm-block tm-block-taller tm-block-scroll">
                        <h2 class="tm-block-title">Top Selling Items</h2>
                        <div class="tm-notification-items">
                            <?php $counter = 0; ?>
                            <?php foreach ($inventoryData as $item) : ?>
                                <?php if ($counter < 10) : ?>
                                    <div class="media tm-notification-item">
                                        <div class="media-body">
                                            <p class="mb-2">
                                                <b><?php echo $item['Product']; ?></b> in <b><?php echo '<b>' . htmlspecialchars($categoryNames[$item['categoryname']]) . '</b>'; ?></b> -
                                                Total Sales: <?php echo $item['YearlySalesQty']; ?>, Inventory Quantity: <?php echo $item['StockQty']; ?>
                                            </p>
                                            <span class="tm-small tm-text-color-secondary">6h ago.</span>
                                        </div>
                                    </div>
                                    <?php $counter++; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                    <div class="col-10 tm-block-col">
                        <div class="tm-bg-primary-dark tm-block tm-block-taller tm-block-scroll">
                            <h2 class="tm-block-title">Inventory Overview</h2>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th scope="col">Product</th>
                                        <th scope="col">Category</th>
                                        <th scope="col">Total Stock</th>
                                        <th scope="col">Unit Price</th>
                                        <th scope="col">Sales Price</th>
                                        <th scope="col">Stock Value</th>
                                        <th scope="col">Total Worth</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                       foreach ($inventoryData as $item) {
                                        echo "<tr>";
                                        echo "<td>" . $item['Product'] . "</td>";
                                        echo "<td>" . $categoryNames[$item['categoryname']] . "</td>"; // Use categoryNames array to get category name
                                        echo "<td>" . $item['StockQty'] . "</td>";
                                        echo "<td>" . $item['TotalCost'] . "</td>";
                                        echo "<td>" . $item['TotalSales'] . "</td>";
                                        echo "<td>" . $item['StockValue'] . "</td>";
                                        echo "<td>" . $item['Revenue'] . "</td>";
                                        echo "</tr>";
                                    }
                                    
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="tm-bg-primary-dark tm-block tm-block-taller tm-block-scroll">
                    <h2 class="tm-block-title">Products & Category</h2>
                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">Category</th>
                                <th scope="col">Items</th>
                                <th scope="col">Product Names</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ($productCategoryData as $item) {
                                echo "<tr>";
                                echo "<td>" . $categoryNames[$item['categoryname']] . "</td>";
                                echo "<td>" . count(explode(",", $item['ProductNames'])) . "</td>";
                                echo "<td>" . $item['ProductNames'] . "</td>";
                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <footer class="tm-footer row tm-mt-small">
            <div class="col-12 font-weight-light">
                <p class="text-center text-white mb-0 px-4 small">
                    Copyright &copy; <b>2023</b> All rights reserved. 
                    
                    Design: <a rel="nofollow noopener" href="https://phemcode.nicepage.io" class="tm-footer-link">Sales-Pilot</a>
                </p>
            </div>
        </footer>
    </div>

    <script src="http://localhost/WEB/js/jquery-3.3.1.min.js"></script>
    <!-- https://jquery.com/download/ -->
    <script src="http://localhost/WEB/js/moment.min.js"></script>
    <!-- https://momentjs.com/ -->
    <script src="http://localhost/WEB/js/Chart.min.js"></script>
    <!-- http://www.chartjs.org/docs/latest/ -->
    <script src="http://localhost/WEB/js/bootstrap.min.js"></script>
    <!-- https://getbootstrap.com/ -->
    <script>
        Chart.defaults.global.defaultFontColor = 'black';
        let ctxLine,
            ctxBar,
            ctxPie,
            optionsLine,
            optionsBar,
            optionsPie,
            configLine,
            configBar,
            configPie,
            lineChart;
        barChart, pieChart;
        // DOM is ready
        $(function () {
            drawLineChart(); // Line Chart
            drawBarChart(); // Bar Chart
            drawPieChart(); // Pie Chart

            $(window).resize(function () {
                updateLineChart();
                updateBarChart();                
            });
        })

const width_threshold = 480;

function drawLineChart() {
  if ($("#lineChart").length) {
    ctxLine = document.getElementById("lineChart").getContext("2d");
    
    const barChartLabels = [
      "Tech",
      "Fashion",
      "Food",
      "Home",
      "Health",
      "Automotives",
      "Sports",
      "Other",
      "Kids",
      "Office"
    ];

    optionsLine = {
      scales: {
        yAxes: [
          {
            scaleLabel: {
              display: true,
              labelString: "Amount($)"
            }
          }
        ]
      }
    };

    // Set aspect ratio based on window width
    optionsLine.maintainAspectRatio =
      $(window).width() < width_threshold ? false : true;

    configLine = {
      type: "line",
      data: {
        labels: barChartLabels,
        datasets: [
          {
            label: "Inventory",
            data: <?php echo json_encode($inventoryQtyData); ?>,
            fill: false,
            borderColor: "rgb(2, 255, 192, 92)",
            cubicInterpolationMode: "monotone",
            pointRadius: 0
          },
          {
            label: "Stock",
            data: <?php echo json_encode($StockQtyData); ?>,
            fill: false,
            borderColor: "rgba(255,99,132,1)",
            cubicInterpolationMode: "monotone",
            pointRadius: 0
          },
          {
            label: "Supply",
            data: <?php echo json_encode($supplyQtyData); ?>,
            fill: false,
            borderColor: "rgba(153, 0, 255, 255)",
            cubicInterpolationMode: "monotone",
            pointRadius: 0
          }
        ]
      },
      options: optionsLine
    };

    lineChart = new Chart(ctxLine, configLine);
  }
}
 

function drawBarChart() {
  if ($("#barChart").length) {
      const ctxBar = document.getElementById("barChart").getContext("2d");

      // Define your PHP data variables for the bar chart here
      const barChartLabels = [
                  "Tech",
                  "Fashion",
                  "Food",
                  "Home",
                  "Health",
                  "Automotives",
                  "Sports",
                  "Other",
                  "Kids",
                  "Office"
                ];
      const barChartData = <?php echo json_encode($barChartData); ?>;
      const barChartColors = ["#F7604D", "#4ED6B8", "#A8D582", "#FF5733", "#1E90FF", "#FF1493", "#32CD32", "#8A2BE2", "#FFD700", "#FF8C00"];

      const optionsBar = {
          responsive: true,
          scales: {
              yAxes: [
                  {
                      barPercentage: 0.2,
                      ticks: {
                          beginAtZero: true,
                      },
                      scaleLabel: {
                          display: true,
                          labelString: "Category",
                      },
                  },
              ],
          },
      };

      optionsBar.maintainAspectRatio =
          $(window).width() < width_threshold ? false : true;

      const configBar = {
          type: "horizontalBar",
          data: {
              labels: barChartLabels,
              datasets: [
                  {
                      label: "Total Stock Qty",
                      data: barChartData,
                      backgroundColor: barChartColors,
                      borderWidth: 0,
                  },
              ],
          },
          options: optionsBar,
      };

      const barChart = new Chart(ctxBar, configBar);
  }
}


function drawPieChart() {
  if ($("#pieChart").length) {
      const chartHeight = 400;
      $("#pieChartContainer").css("height", chartHeight + "px");
      const ctxPie = document.getElementById("pieChart").getContext("2d");

      // Define your PHP data variables for the pie chart here
      const pieChartData = <?php echo json_encode($pieChartData); ?>;
      const pieChartLabels = [
                  "Tech",
                  "Fashion",
                  "Food",
                  "Home",
                  "Health",
                  "Automotives",
                  "Sports",
                  "Other",
                  "Kids",
                  "Office"
      ];
      const pieChartColors = ["#F7604D", "#4ED6B8", "#A8D582", "#FF5733", "#1E90FF", "#FF1493", "#32CD32", "#8A2BE2", "#FFD700", "#FF8C00"];


      const optionsPie = {
          responsive: true,
          maintainAspectRatio: false,
          layout: {
              padding: {
                  left: 10,
                  right: 10,
                  top: 10,
                  bottom: 10,
              },
          },
          legend: {
              position: "top",
          },
      };

      const configPie = {
          type: "pie",
          data: {
              datasets: [
                  {
                      data: pieChartData,
                      backgroundColor: pieChartColors,
                      label: "Total Value ($)",
                  },
              ],
              labels: pieChartLabels,
          },
          options: optionsPie,
      };

      const pieChart = new Chart(ctxPie, configPie);
  }
}

    </script>
</body>

</html>
