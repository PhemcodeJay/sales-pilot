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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_image"])) {
    // Check if a file was uploaded
    if (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] === 0) {
        // Define allowed file extensions and file size limit (adjust as needed)
        $allowed_extensions = ["jpg", "jpeg", "png", "gif"];
        $max_file_size = 5 * 1024 * 1024; // 5 MB

        // Get the file details
        $file_name = $_FILES["profile_image"]["name"];
        $file_tmp = $_FILES["profile_image"]["tmp_name"];
        $file_size = $_FILES["profile_image"]["size"];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Check file extension and size
        if (in_array($file_extension, $allowed_extensions) && $file_size <= $max_file_size) {
            // Generate a unique filename (you can use a better method for this)
            $unique_filename = uniqid() . "." . $file_extension;

            // Define the upload directory
            $upload_dir = "uploads/";

            // Move the uploaded file to the upload directory
            $upload_path = $upload_dir . $unique_filename;
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // File upload was successful, now update the database

                // Update the profile_image column in the business_records table
                $sql = "UPDATE business_records SET profile_image = ? WHERE Username = ?";
                $stmt = $connection->prepare($sql);
                $stmt->bind_param("ss", $upload_path, $Username);

                if ($stmt->execute()) {
                    // Database update was successful
                    echo "File uploaded and database updated successfully.";
                } else {
                    echo "Error updating the database: " . $stmt->error;
                }
            } else {
                echo "Error moving the uploaded file to the server.";
            }
        } else {
            echo "Invalid file. Please upload a valid image file (JPG, JPEG, PNG, GIF) up to 5 MB in size.";
        }
    } else {
        echo "No file was uploaded.";
    }
}

// Close the database connection
$connection->close();
?>
