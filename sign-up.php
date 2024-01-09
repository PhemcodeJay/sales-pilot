<?php
require 'C:\xampp\htdocs\WEB\vendor\autoload.php'; // Include the Composer autoloader

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Include the database connection settings
include('config.php');

try {
    $connection = new mysqli($hostname, $username, $password, $database);

    if ($connection->connect_error) {
        throw new Exception("Error: " . $connection->connect_error);
    }
} catch (Exception $e) {
    exit($e->getMessage());
}

$mail = new PHPMailer(true);
$mail->SMTPDebug = SMTP::DEBUG_SERVER;

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['signup'])) {
    // Initialize variables with default values
    $BusinessName = $Username = $Password = $Email = $Phone = $Location = $activationCode = $ConfirmPassword = "";

    // Check if the keys exist in the $_POST array before accessing them
    if (isset($_POST["BusinessName"])) {
        $BusinessName = mysqli_real_escape_string($connection, htmlspecialchars($_POST["BusinessName"]));
    }

    if (isset($_POST["Username"])) {
        $Username = mysqli_real_escape_string($connection, htmlspecialchars($_POST["Username"]));
    }

    if (isset($_POST["Password"])) {
        $Password = mysqli_real_escape_string($connection, htmlspecialchars($_POST["Password"]));
    }

    if (isset($_POST["Email"])) {
        $Email = mysqli_real_escape_string($connection, htmlspecialchars($_POST["Email"]));
    }

    if (isset($_POST["Phone"])) {
        $Phone = mysqli_real_escape_string($connection, htmlspecialchars($_POST["Phone"]));
    }

    if (isset($_POST["Location"])) {
        $Location = mysqli_real_escape_string($connection, htmlspecialchars($_POST["Location"]));
    }

    if (isset($_POST["activation_code"])) {
        $activationCode = mysqli_real_escape_string($connection, htmlspecialchars($_POST["activation_code"]));
    }

    if (isset($_POST["ConfirmPassword"])) {
        $ConfirmPassword = mysqli_real_escape_string($connection, htmlspecialchars($_POST["ConfirmPassword"]));
    }

    if (isset($_SESSION['id_business']) && !empty($_SESSION['id_business'])) {
        header("Location: reg-success.html");
    }

    // Call the function to handle form submission
    handleFormSubmission($BusinessName, $Username, $Password, $Email, $Phone, $Location, $ConfirmPassword, $connection, $mail);
}

// Function to handle form submission and insert data in the database
function handleFormSubmission($BusinessName, $Username, $Password, $Email, $Phone, $Location, $ConfirmPassword, $connection, $mail)
{
    // Validate form data
    if (empty($BusinessName) || empty($Username) || empty($Password) || empty($Email) || empty($Phone) || empty($Location) || empty($ConfirmPassword)) {
        // Handle validation errors
    }
    
    if (strlen($Password) > 20 || strlen($Password) < 5) {
        // Handle password length error
    }
    
    if (!filter_var($Email, FILTER_VALIDATE_EMAIL)) {
        // Handle email validation error
    }
    
    if (preg_match('/^[a-zA-Z0-9]+$/', $Username) == 0) {
        // Handle username validation error
    }

    // Check if Username already exists
    $stmt = $connection->prepare('SELECT id_business, Password FROM sales_pilot.business_records WHERE Username = ?');
    $stmt->bind_param('s', $Username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo 'Username already exists, please choose another!';
    } else {
        // Insert new user record
        $passwordHash = password_hash($Password, PASSWORD_DEFAULT);
        $activationCode = uniqid();

        $insertStmt = $connection->prepare('INSERT INTO sales_pilot.business_records (BusinessName, Username, Password, Email, Phone, Location, activation_code, ConfirmPassword) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $insertStmt->bind_param('ssssssss', $BusinessName, $Username, $passwordHash, $Email, $Phone, $Location, $activationCode, $ConfirmPassword);

        if ($insertStmt->execute()) {
            // Send activation email
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->Port = 465;
                $mail->SMTPAuth = true;
                $mail->Username = 'olphemie@gmail.com'; // Replace with your Gmail email
                $mail->Password = 'itak uyjg empc blnp'; // Replace with your  app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;


                $mail->setFrom('olphemie@gmail.com', 'SalesPilot');
                $mail->addAddress($Email);
                $mail->Subject = 'Activate Your Account';
                $mail->Body = 'Hello,<br>Click the link below to activate your account:<br><a href="https://localhost/WEB/activate.php?token=your_activation_token">Activate Account</a>';

                if ($mail->send()) {
                    header("Location: reg-success.html"); // Redirect after sending activation email
                    exit(); // Add exit to stop the script execution
                } else {
                    echo 'Error sending activation email: ' . $mail->ErrorInfo;
                }
            } catch (Exception $e) {
                echo 'Mailer Error: ' . $e->getMessage();
            }
        } else {
            echo 'Error inserting user record into the database: ' . $connection->error;
        }
    }


    $stmt->close();
}

// Close the database connection
$connection->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="http://localhost/WEB/assets/img/apple-icon.png">
  <link rel="icon" type="image/png" href="http://localhost/WEB/newlogo.png">
  <title>
    Sales Pilot - Registration
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
    /* Button */
.page-header .my-auto .row .mx-auto .card .flex-column .card-plain .card-body #registrationForm .form-check-info .text-center .btn-lg{
 top:-23px !important;
}

/* Button */
.form-check-info .btn-lg{
 background-image:linear-gradient(195deg, rgb(236, 64, 122) 0%, rgb(216, 27, 96) 100%);
 width:29% !important;
 transform:translatex(7px) translatey(-40px);
}

/* Footer */
#registrationForm .form-check-info footer{
 transform:translatex(0px) translatey(65px);
 width:737px !important;
 background-image:linear-gradient(195deg, rgb(236, 64, 122) 0%, rgb(216, 27, 96) 100%);
}

/* Registration form */
#registrationForm{
 transform:translatex(5px) translatey(-44px);
}

</style>
</head>

<body class="bg-gray-200">
  <div class="container position-sticky z-index-sticky top-0">
    <div class="row">
      <div class="col-12">
       
      </div>
    </div>
  </div>
  <main class="main-content  mt-0">
    <div class="page-header align-items-start min-vh-100" style="background-image: url('https://images.unsplash.com/photo-1497294815431-9365093b7331?ixlib=rb-1.2.1&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1950&q=80');">
      <span class="mask bg-gradient-dark opacity-6"></span>
      <div class="container my-auto">
        <div class="row">
          <div class="col-lg-10 col-md-8 col-12 mx-auto">
            <div class="card z-index-0 fadeIn3 fadeInBottom">
              <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-primary shadow-primary border-radius-lg py-3 pe-1">
                  <h4 class="text-white font-weight-bolder text-center mt-2 mb-0">Create Account</h4>
                  <div class="row mt-3">
                    <div class="col-5 text-center ms-auto">
                      <a class="btn btn-link px-3" href="javascript:;">
                        <i class="fa fa-facebook text-white text-lg"></i>
                      </a>
                    </div>
                    <div class="col-5 text-center me-auto">
                      <a class="btn btn-link px-3" href="javascript:;">
                        <i class="fa fa-google text-white text-lg"></i>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-xl-10 col-lg-5 col-md-7 d-flex flex-column ms-auto me-auto ms-lg-auto me-lg-5">
                <div class="card card-plain">
                  <div class="card-header">
                    <h4 class="font-weight-bolder">Sign Up</h4>
                    <p class="mb-0">Enter your details to register</p>
                  </div>
                  <div class="card-body">
                  <form role="form" id="registrationForm" method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" autocomplete="off">
                        <div class="input-group input-group-outline mb-3">
                            <input type="text" class="form-control" id="Username" name="Username" placeholder="Enter your username">
                        </div>
                        <div class="input-group input-group-outline mb-3">
                            <input type="text" class="form-control" id="BusinessName" name="BusinessName" placeholder="Enter your business name">
                        </div>
                        <div class="input-group input-group-outline mb-3">
                            <input type="text" class="form-control" id="Phone" name="Phone" placeholder="Enter your phone number">
                        </div>
                        <div class="input-group input-group-outline mb-3">
                            <input type="password" class="form-control" id="Password" name="Password" placeholder="Enter your password">
                        </div>
                        <div class="input-group input-group-outline mb-3">
                            <input type="text" class="form-control" id="ConfirmPassword" name="ConfirmPassword" placeholder="Confirm your password">
                        </div>
                        <div class="input-group input-group-outline mb-3">
                            <input type="email" class="form-control" id="Email" name="Email" placeholder="Enter your email">
                        </div>
                        <div class="input-group input-group-outline mb-3">
                            <input type="text" class="form-control" id="Location" name="Location" placeholder="Enter your location">
                        </div>
                        <div class="form-check form-check-info text-start ps-0">
                            <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault" checked>
                            <label class="form-check-label" for="flexCheckDefault">
                                I agree the <a href="javascript:;" class="text-dark font-weight-bolder">Terms and Conditions</a>
                            </label>
                        <div class="text-center">
                            <button type="submit" name="signup" class="btn btn-lg bg-gradient-primary btn-lg w-100 mt-4 mb-0">Sign Up</button>
                        </div>

      <footer class="footer position-absolute bottom-2 py-2 w-100">
        <div class="container">
          <div class="row align-items-center justify-content-lg-between">
            <div class="col-12 col-md-6 my-auto">
              <div class="copyright text-center text-sm text-white text-lg-start">
                © <script>
                  document.write(new Date().getFullYear())
                </script>,
                made with <i class="fa fa-heart" aria-hidden="true"></i> by
                <a href="http://localhost/WEB/index.html" class="font-weight-bold text-white" target="_blank">Phemcode</a>
                for a better web.
              </div>
            </div>
            <div class="col-12 col-md-6">
              <ul class="nav nav-footer justify-content-center justify-content-lg-end">
                <li class="nav-item">
                  <a href="http://localhost/WEB/index.html" class="nav-link text-white" target="_blank">Phemcode</a>
                </li>
                <li class="nav-item">
                  <a href="http://localhost/WEB/index.html" class="nav-link text-white" target="_blank">About Us</a>
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
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = {
        damping: '0.5'
      }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
  </script>
 
</body>

</html>