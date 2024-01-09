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

if (!isset($_SESSION['Username'])) {
    header('Location: loginpage.php');
    exit();
}

$Username = $_SESSION['Username'];

if (isset($_POST['update'])) {
    // Handle the "Update" button click
    $products = $_POST['Product'];
    $stockQty = $_POST['StockQty'];
    $supplyQty = $_POST['SupplyQty'];
    $costPrice = $_POST['CostPrice'];
    $salesPrice = $_POST['SalesPrice'];

    // Loop through the form data and update the database
    $stmt = $connection->prepare('UPDATE inventory SET StockQty = ?, SupplyQty = ?, CostPrice = ?, SalesPrice = ? WHERE Product = ?');
    $stmt->bind_param('dddds', $stockQtyValue, $supplyQtyValue, $costPriceValue, $salesPriceValue, $product);

    foreach ($products as $key => $product) {
        $stockQtyValue = $stockQty[$key];
        $supplyQtyValue = $supplyQty[$key];
        $costPriceValue = $costPrice[$key];
        $salesPriceValue = $salesPrice[$key];

        $stmt->execute();
    }

    // Redirect to the current page to refresh the data
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}


if (isset($_POST['delete_metrics'])) {
    // Handle the "Delete" button click
    $selectedMetrics = $_POST['selected_metrics'];

    // Loop through the selected metrics and delete them from the database
    $stmt = $connection->prepare('DELETE FROM inventory WHERE id_inventory = ?');
    $stmt->bind_param('d', $idInventory);

    foreach ($selectedMetrics as $idInventory) {
        $stmt->execute();
    }

    // Redirect to the current page to refresh the data
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Function to fetch and calculate totals
function fetchAndCalculateTotals($connection, $Username)
{
    $stmt = $connection->prepare('SELECT COUNT(*) AS TotalProduct, SUM(InventoryValue) AS TotalInventoryValue FROM inventory WHERE Username = ?');
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

// Function to fetch the category name based on category ID
function fetchCategoryName($connection, $categoryId)
{
    $stmt_category = $connection->prepare("SELECT categoryname FROM category WHERE id_category = ?");
    $stmt_category->bind_param('i', $categoryId);
    $stmt_category->execute();
    $result_category = $stmt_category->get_result();

    if ($result_category->num_rows > 0) {
        $row = $result_category->fetch_assoc();
        return $row['categoryname'];
    } else {
        return 'Uncategorized'; // Provide a default value if no category is found
    }
}


// Fetch data for product metrics with category information
$stmt_metrics = $connection->prepare('
    SELECT inventory.*, category.categoryname
    FROM inventory
    JOIN category ON inventory.category_id = category.id_category
    WHERE inventory.Username = ?
');
$stmt_metrics->bind_param('s', $Username);
$stmt_metrics->execute();
$result_metrics = $stmt_metrics->get_result();

// Function to fetch the product with the highest inventory value
function fetchProductWithHighestTotalRevenue($connection, $Username)
{
    $stmt = $connection->prepare("SELECT Product FROM inventory WHERE Username = ? ORDER BY TotalRevenue DESC LIMIT 1");
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

$HighestTotalRevenue = fetchProductWithHighestTotalRevenue($connection, $Username);

// Function to fetch the product with the lowest inventory value
function fetchProductWithLowestTotalRevenue($connection, $Username)
{
    $stmt = $connection->prepare("SELECT Product FROM inventory WHERE Username = ? ORDER BY TotalRevenue ASC LIMIT 1");
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

$LowestTotalRevenue = fetchProductWithLowestTotalRevenue($connection, $Username);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="http://localhost/WEB/newlogo.png">
    <title>Product</title>
    <style>
        /* Reset default styles and set a base font size */
        body, html {
            margin: 0;
            padding: 0;
            font-size: 16px;
            font-family: Arial, sans-serif;
            background-color:#5f6e76;
        }

        /* Header styling */
        header {
            background-color:#5f6e76;
            color: #fff;
            text-align: center;
            padding: 20px;
        }

        header h1 {
            font-size: 28px;
            margin-top: 10px;
        }

        /* Dashboard card styling */
        .dashboard-card {
            background-color: #f3e7bd;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 20px;
            box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .dashboard-card h2 {
            margin-top: 0;
            color: #007bff;
        }

        .dashboard-card p {
            margin: 0;
            font-weight: 700;
        }

        /* Button styling */
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

        /* Table styling */
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

        /* Heading */
.dashboard-container .product-overview h2{
 color:#5f6e76;
}

/* Heading */
.dashboard-container .product-statistics h2{
 color:#5f6e76;
}

/* Th */
.product-statistics tr th{
 background-color:#5f6e76;
}

/* Th */
#inventoryTable tr th{
 background-color:#5f6e76;
}

/* Button */
.product-statistics form .button{
 background-color:#5f6e76;
}

/* Button */
.product-statistics a .button{
 background-color:#5f6e76;
}

/* Button */
.product-metrics a .button{
 background-color:#5f6e76;
}

/* Button */
.product-metrics form .button{
 background-color:#5f6e76;
}

/* Dashboard container */
.dashboard-container{
 transform:translatex(0px) translatey(0px);
}
/* Heading */
.product-metrics h2{
 transform:translatex(0px) translatey(0px);
 color:#5f6e76;
}


        /* Footer styling */
        footer {
            background-color: #333;
            color: #fff;
            text-align: center;
            padding: 10px;
        }

        footer p {
            margin: 5px;
        }

/* Scrollable product statistics table */
.product-statistics table {
    display: block;
    max-height: 300px; /* Adjust the max height as needed */
    overflow-y: auto;
}

/* Scrollable product metrics table */
.product-metrics table {
    display: block;
    max-height: 300px; /* Adjust the max height as needed */
    overflow-y: auto;
}


    </style>
</head>
<body>
<header>
    <img class="app-logo" src="http://localhost/WEB/salespilot.png" alt="Sales Pilot Logo">
    <h1><?php echo $_SESSION['Username']; ?></h1>
</header>

<div class="dashboard-container">
    <div class="dashboard-card product-overview">
        <h2>Product Overview</h2>
        <p>Total Product: <?php echo $totalData['TotalProduct']; ?></p>
        <p>Most valuable Product: <?php echo $HighestTotalRevenue; ?></p>
        <p>Least Valuable Product: <?php echo $LowestTotalRevenue; ?></p>
    </div>

    <div class="dashboard-card product-statistics">
        <h2>Product Statistics</h2>
        <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
            <table>
                <tr>
                    <th>Product</th>
                    <th>Stock Quantity</th>
                    <th>Supply Quantity</th>
                    <th>Inventory Quantity</th>
                    <th>Cost Price</th>
                    <th>Sales Price</th>
                </tr>
                <?php
                $result_metrics->data_seek(0);
                while ($row = $result_metrics->fetch_assoc()) :
                    ?>
                    <tr>
                        <td><input type="text" name="Product[]" value="<?php echo $row['Product']; ?>"></td>
                        <td><input type="text" name="StockQty[]" value="<?php echo $row['StockQty']; ?>"></td>
                        <td><input type="text" name="SupplyQty[]" value="<?php echo $row['SupplyQty']; ?>"></td>
                        <td><input type="text" name="InventoryQty[]" value="<?php echo $row['InventoryQty']; ?>"></td>
                        <td>$<input type="text" name="CostPrice[]" value="<?php echo $row['CostPrice']; ?>"></td>
                        <td>$<input type="text" name="SalesPrice[]" value="<?php echo $row['SalesPrice']; ?>"></td>
                    </tr>
                <?php endwhile; ?>
            </table>
            <button class="button" type="submit" name="update">Update</button>
        </form>
        <a href="create-product.html"><button class="button">Add</button></a>
    </div>
</div>

<div class="dashboard-card product-metrics">
    <h2>Product Metrics</h2>
    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <table id="inventoryTable">
            <tr>
                <th>Select</th>
                <th>Product</th>
                <th>Author</th>
                <th>Category</th>
                <th>Stock Value</th>
                <th>Supply Value</th>
                <th>Inventory Value</th>
                <th>Total Revenue</th>
                <th>Date</th>
            </tr>
            <?php
            $result_metrics->data_seek(0);
            while ($row = $result_metrics->fetch_assoc()) :
                ?>
                <tr>
                    <td><input type="checkbox" name="selected_metrics[]" value="<?php echo $row['id_inventory']; ?>"></td>
                    <td><?php echo htmlspecialchars($row['Product']); ?></td>
                    <td><?php echo htmlspecialchars($row['Author']); ?></td>
                    <td><?php echo fetchCategoryName($connection, $row['categoryname']); ?></td>
                    <td>$<?php echo number_format($row['StockValue'], 2); ?></td>
                    <td>$<?php echo number_format($row['SupplyValue'], 2); ?></td>
                    <td>$<?php echo number_format($row['InventoryValue'], 2); ?></td>
                    <td>$<?php echo number_format($row['TotalRevenue'], 2); ?></td>
                    <td><?php echo date('Y-m-d H:i:s', strtotime($row['Date'])); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>
        <button class="button" type="submit" value="delete" name="delete_metrics">Delete</button>
    </form>
    <div class="button-container">
            <a href="inventory-dashboard.php"><button class="button">Inventory</button></a>
            <a href="sales-dashboard.php"><button class="button">Sales</button></a>
        </div>
</div>

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
</body>
</html>
