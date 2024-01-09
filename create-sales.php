<?php

session_start(); // Start session

// Check if the 'Username' key exists in the session
if (isset($_SESSION['Username'])) {
    $username = $_SESSION['Username'];
} else {
    // If 'Username' is not set, redirect to the login page
    header('Location: loginpage.php');
    exit(); // Terminate script execution
}

// Include the database connection settings
require_once('config.php');

// Function to insert or update a category
function insertOrUpdateCategory($connection, $categoryName)
{
    // Prepare the SQL statement for insertion or update
    $sql = "INSERT INTO category (categoryname) VALUES (?) ON DUPLICATE KEY UPDATE categoryname = VALUES(categoryname)";
    $stmtCategory = $connection->prepare($sql);

    // Check if the statement was prepared successfully
    if (!$stmtCategory) {
        // Handle the database error here
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database error: ' . $connection->error]);
        exit();
    }

    // Bind the category name to the statement
    $stmtCategory->bind_param("s", $categoryName);

    // Execute the statement
    if (!$stmtCategory->execute()) {
        // Handle the database error here
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Failed to insert or update the category: ' . $stmtCategory->error]);
        exit();
    }

    // Get the ID of the inserted/updated category
    $idCategories = $stmtCategory->insert_id;

    // Close the prepared statement
    $stmtCategory->close();

    return $idCategories;
}

// Function to retrieve cost price, sales price, and inventory quantity from the inventory table
function getInventoryInfo($connection, $productName)
{
    $sql = "SELECT CostPrice, SalesPrice, InventoryQty FROM inventory WHERE Product = ?";
    $stmtInventory = $connection->prepare($sql);
    $stmtInventory->bind_param("s", $productName);

    if ($stmtInventory->execute()) {
        $result = $stmtInventory->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row;
        } else {
            // Handle the case where the product is not found in the inventory
            return null;
        }
    } else {
        // Handle the error if the query fails
        // You can log the error or handle it as needed
        return null;
    }
}

// Main logic to handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create a mysqli connection and check the connection
    include('config.php');

    try {
        $connection = new mysqli($hostname, $username, $password, $database);

        if ($connection->connect_error) {
            throw new Exception("Error: " . $connection->connect_error);
        }
    } catch (Exception $e) {
        exit($e->getMessage());
    }

    function getUserID($Username, $connection) {
        // Prepare and execute a query to fetch the user ID based on the username
        $stmt = $connection->prepare("SELECT id_business FROM business_records WHERE Username = ?");
        $stmt->bind_param("s", $Username);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                return $row['id_business'];
            } else {
                // Handle the case where the user is not found
                return null;
            }
        } else {
            // Handle the error if the query fails
            // You can log the error or handle it as needed
            return null;
        }
    }

    // Replace this with code to fetch the user ID based on the user's data or authentication
    $userId = getUserID($_SESSION['Username'], $connection);

    // Retrieve form data and sanitize it
    $formData = $_POST;

    // Define the sanitize function
    function sanitize($input)
    {
        return trim(strip_tags($input));
    }

    // Retrieve form data and sanitize it
    $Username = isset($formData['Username']) ? sanitize($formData['Username']) : '';
    $product = isset($formData['Product']) ? sanitize($formData['Product']) : '';
    $author = isset($formData['Author']) ? sanitize($formData['Author']) : '';
    $DailySalesQty = isset($formData['DailySalesQty']) ? (int)$formData['DailySalesQty'] : 0;
    $DailyExpenses = isset($formData['DailyExpenses']) ? (float)$formData['DailyExpenses'] : 0.0;
    $categoryName = isset($formData['category']) ? sanitize($formData['category']) : '';

    // Insert or update category
    $idCategories = insertOrUpdateCategory($connection, $categoryName);

    // Retrieve cost price, sales price, and inventory quantity from inventory
    $inventoryInfo = getInventoryInfo($connection, $product);

    if ($inventoryInfo !== null) {
        $costPrice = $inventoryInfo['CostPrice'];
        $salesPrice = $inventoryInfo['SalesPrice'];
        $inventoryQty = $inventoryInfo['InventoryQty'];

        // Insert the product into the sales table with the retrieved information
        $stmtProduct = $connection->prepare("INSERT INTO sales (id_business, Username, Product, Author, DailySalesQty, DailyExpenses, sales_categoryid, CostPrice, SalesPrice, InventoryQty) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtProduct->bind_param("isssddiddd", $userId, $Username, $product, $author, $DailySalesQty, $DailyExpenses, $idCategories, $costPrice, $salesPrice, $inventoryQty);

        if ($stmtProduct->execute()) {
            // The insertion was successful
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode(['success' => 'Product inserted successfully.']);
        } else {
            // Handle the error here
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to execute the product statement: ' . $stmtProduct->error]);
        }
    } else {
        // Handle the case where the product is not found in the inventory
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Product not found in inventory.']);
    }

    // Redirect to the product page after the operation
    header('Location: http://localhost/WEB/sales.php');
    exit();
}


// Get the date the product was first recorded sold
$productId = 123; // Replace with the actual product ID or identifier
$queryStartDate = "SELECT MIN(ExpenseDate) as StartDate FROM inventory WHERE id_inventory = $productId";
$resultStartDate = mysqli_query($connection, $queryStartDate);

if ($resultStartDate) {
    $rowStartDate = mysqli_fetch_assoc($resultStartDate);
    $startDate = $rowStartDate['StartDate'];

    // Calculate the end date as 7 days after the start date
    $endDate = date('Y-m-d', strtotime($startDate . ' + 7 days'));

    // Calculate the sum of daily expenses for 7 days
    $query = "SELECT SUM(DailyExpenses) as WeeklyExpenses FROM inventory WHERE id_inventory = $productId AND ExpenseDate BETWEEN '$startDate' AND '$endDate'";
    $result = mysqli_query($connection, $query);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $weeklyExpenses = $row['WeeklyExpenses'];

        // Now, you can insert the calculated weekly expenses into your table
        $insertQuery = "INSERT INTO sales (WeeklyExpenses) VALUES (?)";
        $stmtInsert = $connection->prepare($insertQuery);
        $stmtInsert->bind_param("d", $weeklyExpenses);

        if ($stmtInsert->execute()) {
            echo "Weekly expenses have been successfully inserted.";
        } else {
            echo "Error inserting data: " . $stmtInsert->error;
        }

        $stmtInsert->close();
    } else {
        echo "Error calculating weekly expenses: " . mysqli_error($connection);
    }
} else {
    echo "Error getting start date: " . mysqli_error($connection);
}


// Get the date of the first sale of the product
$productId = 123; // Replace with the actual product ID or identifier
$queryFirstSaleDate = "SELECT MIN(ExpenseDate) as FirstSaleDate FROM inventory WHERE id_inventory = $productId";
$resultFirstSaleDate = mysqli_query($connection, $queryFirstSaleDate);

if ($resultFirstSaleDate) {
    $rowFirstSaleDate = mysqli_fetch_assoc($resultFirstSaleDate);
    $firstSaleDate = $rowFirstSaleDate['FirstSaleDate'];

    // Calculate the end date as 7 days after the first sale date
    $endDate = date('Y-m-d', strtotime($firstSaleDate . ' + 7 days'));

    // Calculate the sum of daily sales quantity for 7 days
    $query = "SELECT SUM(DailySalesQty) as WeeklySalesQty FROM inventory WHERE id_inventory = $productId AND ExpenseDate BETWEEN '$firstSaleDate' AND '$endDate'";
    $result = mysqli_query($connection, $query);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $weeklySalesQty = $row['WeeklySalesQty'];

        // Now, you can insert the calculated weekly sales quantity into your table
        $insertQuery = "INSERT INTO sales (WeeklySalesQty) VALUES (?)";
        $stmtInsert = $connection->prepare($insertQuery);
        $stmtInsert->bind_param("i", $weeklySalesQty); // Assuming WeeklySalesQty is an integer

        if ($stmtInsert->execute()) {
            echo "Weekly sales quantity has been successfully inserted.";
        } else {
            echo "Error inserting data: " . $stmtInsert->error;
        }

        $stmtInsert->close();
    } else {
        echo "Error calculating weekly sales quantity: " . mysqli_error($connection);
    }
} else {
    echo "Error getting the date of the first sale: " . mysqli_error($connection);
}

// Calculate the sum of monthly sales quantities and expenses
$productId = 123; // Replace with the actual product ID or identifier
$queryStartDate = "SELECT MIN(ExpenseDate) as StartDate FROM inventory WHERE id_inventory = $productId";
$resultStartDate = mysqli_query($connection, $queryStartDate);

if ($resultStartDate) {
    $rowStartDate = mysqli_fetch_assoc($resultStartDate);
    $startDate = $rowStartDate['StartDate'];

    // Calculate the end date as 28 days (4 weeks) after the start date for monthly calculations
    $endDate = date('Y-m-d', strtotime($startDate . ' + 28 days'));

    // Calculate the sum of daily expenses for 4 weeks (28 days)
    $query = "SELECT SUM(DailyExpenses) as MonthlyExpenses FROM inventory WHERE id_inventory = $productId AND ExpenseDate BETWEEN '$startDate' AND '$endDate'";
    $result = mysqli_query($connection, $query);

    if ($result) {
        $row = mysqli_fetch_assoc($result);
        $monthlyExpenses = $row['MonthlyExpenses'];

        // Now, you can insert the calculated monthly expenses into your table
        $insertQuery = "INSERT INTO sales (MonthlyExpenses) VALUES (?)";
        $stmtInsert = $connection->prepare($insertQuery);
        $stmtInsert->bind_param("d", $monthlyExpenses);

        if ($stmtInsert->execute()) {
            echo "Monthly expenses have been successfully inserted.";
        } else {
            echo "Error inserting data: " . $stmtInsert->error;
        }

        $stmtInsert->close();
    } else {
        echo "Error calculating monthly expenses: " . mysqli_error($connection);
    }

    // Calculate the sum of yearly sales quantities and expenses
    $queryYearly = "SELECT SUM(DailySalesQty) as YearlySalesQty, SUM(DailyExpenses) as YearlyExpenses FROM inventory WHERE id_inventory = $productId";
    $resultYearly = mysqli_query($connection, $queryYearly);

    if ($resultYearly) {
        $rowYearly = mysqli_fetch_assoc($resultYearly);
        $yearlySalesQty = $rowYearly['YearlySalesQty'];
        $yearlyExpenses = $rowYearly['YearlyExpenses'];

        // Now, you can insert the calculated yearly sales quantities and expenses into your table
        $insertQueryYearly = "INSERT INTO sales (YearlySalesQty, YearlyExpenses) VALUES (?, ?)";
        $stmtInsertYearly = $connection->prepare($insertQueryYearly);
        $stmtInsertYearly->bind_param("id", $yearlySalesQty, $yearlyExpenses);

        if ($stmtInsertYearly->execute()) {
            echo "Yearly sales quantities and expenses have been successfully inserted.";
        } else {
            echo "Error inserting data: " . $stmtInsertYearly->error;
        }

        $stmtInsertYearly->close();
    } else {
        echo "Error calculating yearly sales quantities and expenses: " . mysqli_error($connection);
    }
} else {
    echo "Error getting start date: " . mysqli_error($connection);
}

// Close the prepared statement and database connection
$connection->close();
?>
