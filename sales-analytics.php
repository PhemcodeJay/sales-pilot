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
$query = "SELECT category.categoryname AS categoryname, GROUP_CONCAT(sales.Product) AS ProductNames, 
                 SUM(sales.InventoryQty) AS TotalInventoryQty, SUM(sales.DailySalesQty) AS TotalDailySalesQty, 
                 SUM(sales.MonthlySalesQty) AS TotalMonthlySalesQty, SUM(sales.WeeklySalesQty) AS TotalWeeklySalesQty,
                 SUM(sales.YearlySalesQty) AS TotalYearlySalesQty,
                 SUM(sales.TotalRevenue) AS TotalSales
          FROM sales
          INNER JOIN category ON sales.sales_categoryid = category.id_category
          WHERE sales.Username = ?
          GROUP BY categoryname";

$stmt = $connection->prepare($query);
$stmt->bind_param('s', $Username);
$stmt->execute();
$result = $stmt->get_result();

// Fetch the data and store it in an associative array
$data = array();
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Fetch and store the sales data
$query = "SELECT 
            s.Product,  
            c.categoryname, 
            SUM(s.DailySalesQty) AS DailySalesQty, 
            SUM(s.WeeklySalesQty) AS WeeklySalesQty, 
            SUM(s.MonthlySalesQty) AS MonthlySalesQty, 
            SUM(s.YearlySalesQty) AS YearlySalesQty, 
            SUM(s.InventoryQty) AS 'Total Stock'
          FROM 
            sales AS s
          INNER JOIN 
            category AS c ON s.sales_categoryid = c.id_category
          WHERE 
            s.Username = ?
          GROUP BY 
            s.Product, c.categoryname";

$stmt = $connection->prepare($query);


// Use bind_param to bind parameters safely
$stmt->bind_param('s', $Username);

// Execute the query
$stmt->execute();

// Get the result set
$result = $stmt->get_result();

// Initialize an array to store the fetched data
$salesData = array();

// Fetch data and store it in the array
while ($row = $result->fetch_assoc()) {
    $salesData[] = $row;
}


$pieChartLabels = array();
$pieChartData = array();

// Create an associative array to store category-wise total revenue
$categoryTotalSales = array();

foreach ($data as $row) {
    $category_name = $row['categoryname']; // Get the category name
    $total_revenue = $row['TotalSales'];

    // If the category is not in the list of labels, add it
    if (!in_array($category_name, $pieChartLabels)) {
        $pieChartLabels[] = $category_name;
    }

    // If the category is not in the list of category revenues, initialize it
    if (!isset($categoryTotalSales[$category_name])) {
        $categoryTotalSales[$category_name] = 0;
    }

    // Sum the TotalInventoryQty for products in the same category
    $categoryTotalSales[$category_name] += $total_revenue;
}

// Get the total revenue for each category and maintain the original order
foreach ($pieChartLabels as $category_name) {
    $pieChartData[] = $categoryTotalSales[$category_name];
}


$query2 = "SELECT category.categoryname AS categoryname, SUM(sales.YearlySalesQty) AS TotalYearlySalesQty
          FROM sales
          INNER JOIN category ON sales.sales_categoryid = category.id_category
          WHERE sales.Username = ?
          GROUP BY categoryname";
$stmt2 = $connection->prepare($query2);
$stmt2->bind_param('s', $Username);
$stmt2->execute();
$result2 = $stmt2->get_result();


$barChartLabels = array();
$barChartData = array();

// Create an associative array to store category-wise total sales quantity
$categoryDailySalesQty = array();

foreach ($result2 as $row) {  // Use $result2 instead of $data to fetch the results
    $category_name = $row['categoryname']; // Get the category name
    $total_sales_qty = $row['TotalYearlySalesQty'];

    // If the category is not in the list of labels, add it
    if (!in_array($category_name, $barChartLabels)) {
        $barChartLabels[] = $category_name;
    }

    // If the category is not in the list of category sales quantities, initialize it
    if (!isset($categoryDailySalesQty[$category_name])) {
        $categoryDailySalesQty[$category_name] = 0;
    }

    // Sum the DailySalesQty for products in the same category
    $categoryDailySalesQty[$category_name] += $total_sales_qty;
}

// Get the total sales quantity for each category and maintain the original order
foreach ($barChartLabels as $category_name) {
    $barChartData[] = $categoryDailySalesQty[$category_name];
}


$connection->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Sales - Analytics</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,700">
    <!-- https://fonts.google.com/specimen/Roboto -->
    <link rel="stylesheet" href="http://localhost/WEB/css/fontawesome.min.css">
    <link rel="stylesheet" href="http://localhost/WEB/css/bootstrap.min.css">
    <link rel="stylesheet" href="http://localhost/WEB/css/templatemo-style.css">
    <link rel="icon" type="image/png" href="http://localhost/WEB/newlogo.png">
    <style>
        /* Add your custom styles here to handle overlapping issues */
        .tm-content-row {
            display: flex;
            flex-wrap: wrap;
        }

        .tm-block-col {
            flex: 1 1 100%;
            margin: 0 10px 20px 0;
        }

        #pieChartContainer {
            width: 100%;
            text-align: center;
        }

        #pieChart {
            max-width: 100%;
            height: auto;
        }
    
         /* Block title */
            .tm-content-row .tm-block-col .tm-block-title{
            min-height:22px;
            }

        /* Block taller */
            #home .tm-block-col:nth-child(1) .tm-block-taller{
            transform:translatex(589px) translatey(50px);
            position:relative;
            top:21px;
            left:156px !important;
            }

            /* Block taller */
            #home .tm-content-row .tm-block-col:nth-child(1) .tm-block-taller{
            width:82% !important;
            right:auto !important;
            top:-29px !important;
            bottom:auto !important;
            }

            #home .tm-content-row .tm-block-col:nth-child(1) .tm-block-taller .tm-block-title{
            bottom:auto !important;
            top:-18px !important;
            left:-18px;
            }


            /* Block taller */
            #home .tm-block-col:nth-child(2) .tm-block-taller{
            bottom:auto !important;
            top:-23px !important;
            transform:translatex(25px) translatey(-362px) !important;
            }

            /* Pie chart */
            #pieChart{
            width:100% !important;
            transform:translatex(36px) translatey(-48px);
            height:393px !important;
            left:-35px;
            }

            /* Block taller */
            #home .tm-content-row .tm-block-col:nth-child(2) .tm-block-taller{
            right:auto !important;
            left:-18px !important;
            width:81% !important;
            transform:translatex(28px) translatey(-431px) !important;
            top:-23px !important;
            bottom:auto !important;
            }

            /* Block */
            #home .tm-block-col:nth-child(3) .tm-block{
            transform:translatex(800px) translatey(-521px);
            top:60px;
            left:-5px;
            width:473px !important;
            height: 500px !important;
            }

            /* Bar chart */
            #barChart{
            transform:translatex(-30px) translatey(-26px);
            width:116% !important;
            height:80% !important;
            position:relative;
            left:-5px;
            }

            /* Block taller */
            #home .tm-block-col:nth-child(4) .tm-block-taller{
            width: 780px;
            padding-right:30px;
            z-index: 1;
            transform:translatex(-5px) translatey(-1000px);
            }

            /* Block title */
            #home .tm-block .tm-block-title{
            color:#221f1f;
            position:relative;
            top:-1px !important;
            text-align:center;
            }

            /* Block title */
            #home .tm-block-taller .tm-block-title{
            text-align:center;
            }

            /* Import Google Fonts */
            @import url("//fonts.googleapis.com/css2?family=Roboto+Condensed:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap");
            @import url("//fonts.googleapis.com/css2?family=TimesNewRoman:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap");
            @import url("//fonts.googleapis.com/css2?family=Ribeye+Marrow:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap");
            
            /* Font Icon */
                #navbarSupportedContent .active i{
                position:relative;
                top:2px;
                left:-3px;
                }

                /* Font Icon */
                #navbarDropdown .fa-file-alt{
                position:relative;
                top:2px;
                left:-3px;
                }

                /* Font Icon */
                #navbarSupportedContent .nav-item .fa-shopping-cart{
                position:relative;
                top:2px;
                left:-2px;
                }

                /* Font Icon */
                #navbarSupportedContent .nav-item .fa-user{
                position:relative;
                top:2px;
                left:-3px;
                }

                /* Heading */
                .navbar a h1{
                text-transform:capitalize;
                font-style:normal;
                font-weight:600;
                font-size:26px;
                font-family:'Ribeye Marrow', display;
                color:#f5f3f3;
                }

                /* Nav link */
                #navbarSupportedContent .nav-item .nav-link{
                color:#121111;
                font-size:20px;
                font-weight:500;
                font-size:15px;
                font-family:'TimesNewRoman','Times New Roman',Times,Baskerville,Georgia,serif;
                font-style:normal;
                text-transform:none;
                color:#e8e2e2; 
                }
                /* Paragraph */
                .tm-mt-small p{
                position:relative;
                top:-800px;
                color:#070707 !important;
                font-weight:500;
                }
    </style>
</head>

<body id="reportsPage">
    <div class="" id="home">
        <nav class="navbar navbar-expand-xl">
            <div class="container h-100">
                <a class="navbar-brand" href="sales-dashboard.php">
                    <h1 class="tm-site-title mb-0">Sales Overview</h1>
                </a>
                <button class="navbar-toggler ml-auto mr-0" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                    aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <i class="fas fa-bars tm-nav-icon"></i>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav mx-auto h-200">
                        <li class="nav-item">
                            <a class="nav-link active" href="sales-analytics.php">
                                <i class="fas fa-tachometer-alt"></i>
                                Analytics
                                <span class="sr-only">(current)</span>
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="report.php" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="far fa-file-alt"></i>
                                Reports
                                <span> <i class="fas fa-angle-down"></i></span>
                            </a>
                            <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="daily-sales.php">Daily</a>
                                <a class="dropdown-item" href="weekly-sales.php">Weekly</a>
                                <a class="dropdown-item" href="monthly-sales.php">Monthly</a>
                                <a class="dropdown-item" href="yearly-sales.php">Annual</a>
                            </div>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="sales-dashboard.php">
                                <i class="fas fa-shopping-cart"></i>
                                Sales
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

        <!-- row -->
        <div class="row tm-content-row">
            <div class="col-sm-8 col-md-12 col-lg-6 col-xl-6 tm-block-col">
                <div class="tm-bg-primary-dark tm-block tm-block-taller">
                    <h2 class="tm-block-title">Total Revenue by Category ($)</h2>
                    <div id="pieChartContainer">
                        <canvas id="pieChart" class="chartjs-render-monitor"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-8 tm-block-col">
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
                            foreach ($data as $item) {
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
        <div class="col-sm-8 col-md-12 col-lg-6 col-xl-6 tm-block-col">
                    <div class="tm-bg-primary-dark tm-block ">
                        <h2 class="tm-block-title">Total Sales by Category</h2>
                        <canvas id="barChart"></canvas>
                        
                    </div>
                </div>
                <div class="col-10 tm-block-col">
                    <div class="tm-bg-primary-dark tm-block tm-block-taller tm-block-scroll">
                        <h2 class="tm-block-title">Products Sold </h2>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th scope="col">Product</th>
                                    <th scope="col">Category</th>
                                    <th scope="col">Daily Sales</th>
                                    <th scope="col">Weekly Sales</th>
                                    <th scope="col">Monthly Sales</th>
                                    <th scope="col">Yearly Sales</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                   foreach ($salesData as $item) {
                                    echo "<tr>";
                                    echo "<td>" . $item['Product'] . "</td>";
                                    echo "<td>" . $categoryNames[$item['categoryname']] . "</td>"; // Use categoryNames array to get category name
                                    echo "<td>" . $item['DailySalesQty'] . "</td>";
                                    echo "<td>" . $item['WeeklySalesQty'] . "</td>";
                                    echo "<td>" . $item['MonthlySalesQty'] . "</td>";
                                    echo "<td>" . $item['YearlySalesQty'] . "</td>";
                                    echo "</tr>";
                                }
                                
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <footer class="tm-footer row tm-mt-small">
            <div class="col-12 font-weight-light">
                <p class="text-center text-white mb-0 px-4 small">
                    Copyright &copy; <b>2023</b> All rights reserved.

                    Design: <a>Phemcode - Sales Pilot</a>
                </p>
            </div>
        </footer>
    </div>

    <script src="http://localhost/WEB/js/jquery-3.3.1.min.js"></script>
    <!-- https://jquery.com/download/ -->
    <script src="http://localhost/WEB/js/moment.min.js"></script>
    <!-- https://momentjs.com/ -->
    <script src="http://localhost/WEB/js/Chart.min.js"></script>

    <script src="http://localhost/WEB/js/bootstrap.min.js"></script>

    <script>
        const width_threshold = 600;

        function drawBarChart() {
    if ($("#barChart").length) {
        const ctxBar = document.getElementById("barChart").getContext("2d");

        // Define your PHP data variables for the bar chart here
        const barChartLabels = [
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
                        label: "Total Sales Qty",
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
                                label: "Total Revenue ($)",
                            },
                        ],
                        labels: pieChartLabels,
                    },
                    options: optionsPie,
                };

                const pieChart = new Chart(ctxPie, configPie);
            }
        }

        // Call the chart functions
        drawPieChart();
        drawBarChart();
    </script>
</body>

</html>
