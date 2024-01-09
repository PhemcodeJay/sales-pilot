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

// Initialize $user_data
$user_data = array();

// Check if the form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if the update button was clicked
    if (isset($_POST['update_profile'])) {
        // Handle the form submission for updating user data
        $Username = $_POST['Username']; // Updated to match the input name attribute
        $BusinessName = $_POST['BusinessName'];
        $Phone = $_POST['Phone']; // Updated to match the input name attribute
        $Email = $_POST['Email']; // Updated to match the input name attribute
        $Location = $_POST['Location'];

        $updateStmt = $connection->prepare("UPDATE business_records SET Username = ?, BusinessName = ?, Phone = ?, Email = ?, Location = ? WHERE Username = ?");
        $updateStmt->bind_param('ssssss', $Username, $BusinessName, $Phone, $Email, $Location, $Username);

        if ($updateStmt->execute()) {
            // Database update was successful
            echo "User data updated successfully.";
        } else {
            echo "Error updating user data: " . $updateStmt->error;
        }
    }

    // Check if the upload button was clicked
    if (isset($_POST['upload_photo'])) {
        // Handle image upload
        handleImageUpload($connection, $Username);
    }
}

// Fetch user records based on their Username
$selectStmt = $connection->prepare('SELECT * FROM business_records WHERE Username = ?');
$selectStmt->bind_param('s', $Username);
$selectStmt->execute();
$result = $selectStmt->get_result();

if ($result->num_rows > 0) {
    $user_data = $result->fetch_assoc();
} else {
    echo "User not found in the database.";
}

// Function to handle image upload
function handleImageUpload($connection, $Username)
{
    // Check if a file was uploaded
    if (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] === 0) {
        // Define allowed file extensions and file size limit (adjust as needed)
        $allowed_extensions = ["jpg", "jpeg", "png", "gif"];
        $max_file_size = 5 * 1024 * 1024; // 120 MB

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
                // Update the profile_image column in the business_records table
                $updateImageStmt = $connection->prepare("UPDATE business_records SET profile_image = ? WHERE Username = ?");
                $updateImageStmt->bind_param("ss", $upload_path, $Username);

                if ($updateImageStmt->execute()) {
                    echo "File uploaded and database updated successfully.";
                } else {
                    echo "Error updating the database: " . $updateImageStmt->error;
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Admin Profile - Sales Pilot</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400,700">
    <link rel="stylesheet" href="http://localhost/WEB/css/fontawesome.min.css">
    <link rel="stylesheet" href="http://localhost/WEB/css/bootstrap.min.css">
    <link rel="stylesheet" href="http://localhost/WEB/css/templatemo-style.css">
    <link rel="icon" type="image/png" href="http://localhost/WEB/newlogo.png">
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="http://localhost/WEB/js/bootstrap.min.js"></script>
    <style>
       /* Font Icons */
#navbarSupportedContent .active i,
#navbarSupportedContent .nav-item .fa-shopping-cart,
#navbarDropdown .fa-cog,
#navbarDropdown .fa-file-alt,
#navbarSupportedContent .nav-item .fa-tachometer-alt,
#navbarDropdown span i {
    position:relative;
    top:3px;
    left:-2px;
}

/* Font Icon */
#navbarDropdown span i{
 display:none;
}


/* Block auto */
#home .tm-block-col .tm-block-h-auto {
    transform:translatex(-6px) translatey(-33px);
}

/* Block settings */
#home .tm-col-account-settings .tm-block-settings,
#home .container .tm-content-row .tm-col-avatar .tm-block-avatar {
    width:59%; /* Adjust as needed */
    min-height:467px; /* Adjust as needed */
}

/* Button */
.tm-block-settings .form-group .text-uppercase {
    transform:translatex(-10px) translatey(31px);
}

/* Button within specific context */
#home .container .tm-col-account-settings .tm-block-settings .tm-signup-form .form-group .form-group .text-uppercase {
    width:240% !important;
}

/* Heading */
.tm-col-account-settings .tm-block-settings h2,
.tm-col-avatar .tm-block-avatar h2,
#home .tm-block-h-auto h2 {
    font-size:43px; /* Adjust as needed */
    text-align:center;
    text-decoration:underline;
    color:#068d49 !important;
}

/* Paragraph */
#home .tm-block-col p,
.tm-mt-small p {
    position:relative;
    top:-400px; /* Adjust as needed */
    min-height:24px;
    border-color:#97a4dc;
    color:#264b87 !important;
}

/* Block avatar */
#home .container .tm-content-row .tm-col-avatar .tm-block-avatar {
    width: 100% !important; /* Updated width to 100% */
}

/* Block settings */
#home .tm-col-account-settings .tm-block-settings {
    transform: translatex(352px) translatey(-544px);
    min-height: 483px;
    width: 755px;
}

/* Block auto */
#home .tm-block-col .tm-block-h-auto {
    height: 133px; /* Updated height to 133px */
}

/* Heading */
#home .tm-block-h-auto h2 {
    transform: translatex(-14px) translatey(-32px);
}

/* Select */
#home .tm-block-col select {
    transform: translatex(-16px) translatey(-99px) !important;
}

* Heading */
#home .tm-block-h-auto h2{
 color:#315681 !important;
 font-weight:500;
 font-size:30px;
}

/* Heading */
.tm-col-account-settings .tm-block-settings h2{
 color:#315681 !important;
 font-weight:500;
 font-size:30px;
}

/* Heading */
.tm-col-avatar .tm-block-avatar h2{
 color:#315681 !important;
 font-weight:500;
 font-size:30px;
}



    </style>
</head>
  <body id="reportsPage">
    <div class="" id="home">
      <nav class="navbar navbar-expand-xl">
        <div class="container h-100">
          <a class="navbar-brand" href="profile.php">
          <h1 class="tm-site-title mb-0">
                    <?php
                        $Username = $_SESSION['Username'];
                        echo $Username;
                    ?>
                </h1>
          </a>
          <button
            class="navbar-toggler ml-auto mr-0"
            type="button"
            data-toggle="collapse"
            data-target="#navbarSupportedContent"
            aria-controls="navbarSupportedContent"
            aria-expanded="false"
            aria-label="Toggle navigation"
          >
            <i class="fas fa-bars tm-nav-icon"></i>
          </button>

          <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav mx-auto h-100">
              <li class="nav-item">
                <a class="nav-link" href="inventory-dashboard.php">
                  <i class="fas fa-tachometer-alt"></i> Dashboard
                  <span class="sr-only">(current)</span>
                </a>
              </li>
              <li class="nav-item dropdown">
                <a
                  class="nav-link dropdown-toggle"
                  href="#"
                  id="navbarDropdown"
                  role="button"
                  data-toggle="dropdown"
                  aria-haspopup="true"
                  aria-expanded="false"
                >
                  <i class="far fa-file-alt"></i>
                  <span> Reports <i class="fas fa-angle-down"></i> </span>
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                  <a class="dropdown-item" href="inventory.php">All-Time</a>
                  <a class="dropdown-item" href="weeklyreport.php">Weekly</a>
                  <a class="dropdown-item" href="motnhlyreport.php">Monthly</a>
                  <a class="dropdown-item" href="yearlyreport.php">Yearly</a>
                </div>
              </li>
              <li class="nav-item">
                <a class="nav-link" href="products.php">
                  <i class="fas fa-shopping-cart"></i> Products
                </a>
              </li>
              <li class="nav-item dropdown">
                <a
                  class="nav-link dropdown-toggle"
                  href="#"
                  id="navbarDropdown"
                  role="button"
                  data-toggle="dropdown"
                  aria-haspopup="true"
                  aria-expanded="false"
                >
                  <i class="fas fa-cog"></i>
                  <span> New <i class="fas fa-angle-down"></i> </span>
                </a>
                <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                  <a class="dropdown-item" href="create-product.html">Add Product</a>
                  <a class="dropdown-item" href="create-sales.html">Add Sales</a>
                </div>
              </li>
            </ul>
            <ul class="navbar-nav">
              <li class="nav-item">
                <a class="nav-link d-block" href="loginpage.php">
                <?php
                        $Username = $_SESSION['Username'];
                        echo "Hello, $Username <b>Logout</b>";
                        ?>
                </a>
              </li>
            </ul>
          </div>
        </div>
      </nav>
      <div class="container mt-5">
        <div class="row tm-content-row">
          <div class="col-12 tm-block-col">
            <div class="tm-bg-primary-dark tm-block tm-block-h-auto">
              <h2 class="tm-block-title">Menu</h2>
              <p class="text-white"></p>
              <select class="custom-select">
                <option value="0">Create User</option>
                <option href="staff.php">Staff</option>
                <option href="customer.php">Customer</option>
              </select>
            </div>
          </div>
        </div>
        <!-- row -->
        <div class="row tm-content-row">
            <div class="tm-block-col tm-col-avatar">
                <div class="tm-bg-primary-dark tm-block tm-block-avatar">
                    <h2 class="tm-block-title">Avatar</h2>
                    <div class="tm-avatar-container">
                    <?php
                            // Check if a new image is uploaded
                            if (!empty($user_data['profile_image'])) {
                                echo '<img src="' . $user_data['profile_image'] . '" alt="Avatar" class="tm-avatar img-fluid mb-4" />';
                            } elseif (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == UPLOAD_ERR_OK) {
                                // Move the uploaded file to the destination directory
                                move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadFile);

                                // Display the uploaded image
                                echo '<img src="' . $uploadFile . '" alt="Avatar" class="tm-avatar img-fluid mb-4" />';
                            } else {
                                // Display the default image if no new image is uploaded
                                echo '<img src="uploads/avatar.png" alt="Avatar" class="tm-avatar img-fluid mb-4" />';
                            }
                            ?>
                    </div>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="tm-signup-form row" method="post" enctype="multipart/form-data">
                        <label for="profile_image" class="btn btn-primary btn-block text-uppercase">
                            Upload Photo
                        </label>
                        <input type="file" id="profile_image" name="profile_image" style="display: none;" />
                    </form>
                </div>
            </div>
        </div>

          <div class="tm-block-col tm-col-account-settings">
            <div class="tm-bg-primary-dark tm-block tm-block-settings">
              <h2 class="tm-block-title">User Info</h2>
              <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="tm-signup-form row" method="post" enctype="multipart/form-data">

                <div class="form-group col-lg-6">
                  <label for="name">Username</label>
                  <input
                    id="Username"
                    name="Username"
                    type="text"
                    class="form-control validate"
                    value="<?= htmlspecialchars($user_data['Username']); ?>"
                  />
                </div>
                <div class="form-group col-lg-6">
                  <label for="Email">Email</label>
                  <input
                    id="Email"
                    name="Email"
                    type="Email"
                    class="form-control validate"
                    value="<?= htmlspecialchars($user_data['Email']); ?>"
                  />
                </div>
                <div class="form-group col-lg-6">
                  <label for="BusinessName">Business Name</label>
                  <input
                    id="BusinessName"
                    name="BusinessName"
                    type="text"
                    class="form-control validate"
                    value="<?= htmlspecialchars($user_data['BusinessName']); ?>"
                  />
                </div>
                <div class="form-group col-lg-6">
                  <label for="Location">Location</label>
                  <input
                    id="Location"
                    name="Location"
                    type="text"
                    class="form-control validate"
                    value="<?= htmlspecialchars($user_data['Location']); ?>"
                  />
                </div>
                <div class="form-group col-lg-6">
                  <label for="Phone">Phone</label>
                  <input
                    id="Phone"
                    name="Phone"
                    type="tel"
                    class="form-control validate"
                    value="<?= htmlspecialchars($user_data['Phone']); ?>"
                  />
                </div>
                <div class="form-group col-lg-6">
                          <div class="form-group col-lg-6">
                          <button type="submit" name="update_profile" class="btn btn-primary btn-block text-uppercase">Submit</button>
                      </div>
                      </form>
                  </div>
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
    <script>
            $(document).ready(function(){
                // Listen for changes in the file input
                $('#profile_image').change(function(){
                    // Get the selected file
                    var file = this.files[0];

                    // Create a FormData object to send the file data to the server
                    var formData = new FormData();
                    formData.append('profile_image', file);
                    formData.append('upload_photo', '1'); // Add this line to indicate the upload action


                    // Use AJAX to submit the form data to the server
                    $.ajax({
                        type: 'POST',
                        url: '<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response){
                            // Display the response from the server
                            alert(response);
                        },
                        error: function(error){
                            // Handle the error
                            console.log(error);
                        }
                    });
                });
            });
        </script>
  </body>
</html>
