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

// Function to insert product

function insertProduct($connection, $userId, $Username, $product, $description, $author, $supplyQty, $stockQty, $costPrice, $salesPrice, $idCategories)
{
    // Prepare and bind the statement for the product
    $stmtProduct = $connection->prepare("INSERT INTO inventory (id_user, Username, Product, Description, Author, SupplyQty, StockQty, CostPrice, SalesPrice, category_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmtProduct->bind_param("issssddddi", $userId, $Username, $product, $description, $author, $supplyQty, $stockQty, $costPrice, $salesPrice, $idCategories);

    // Execute the statement and handle errors
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
    $description = isset($formData['Description']) ? sanitize($formData['Description']) : '';
    $author = isset($formData['Author']) ? sanitize($formData['Author']) : '';
    $supplyQty = isset($formData['SupplyQty']) ? (int)$formData['SupplyQty'] : 0;
    $stockQty = isset($formData['StockQty']) ? (int)$formData['StockQty'] : 0;
    $costPrice = isset($formData['CostPrice']) ? (float)$formData['CostPrice'] : 0.0;
    $salesPrice = isset($formData['SalesPrice']) ? (float)$formData['SalesPrice'] : 0.0;
    $categoryName = isset($formData['category']) ? sanitize($formData['category']) : '';

    // Insert or update category
    $idCategories = insertOrUpdateCategory($connection, $categoryName);

    // Insert the product
    insertProduct($connection, $userId, $Username, $product, $description, $author, $supplyQty, $stockQty, $costPrice, $salesPrice, $idCategories);

    // Redirect to the product page after the operation
    header('Location: http://localhost/WEB/product.php');
    exit();
}

// Close the prepared statement and database connection
$connection->close();
?>