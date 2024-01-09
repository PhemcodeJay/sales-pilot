<?php
session_start([
    'cookie_lifetime' => 86400,
    'cookie_secure'   => true,
    'cookie_httponly' => true,
    'use_strict_mode' => true,
    'sid_length'      => 48,
]);

include('config.php');

try {
    $connection = new mysqli($hostname, $username, $password, $database);

    if ($connection->connect_error) {
        throw new Exception("Error: " . $connection->connect_error);
    }
} catch (Exception $e) {
    exit($e->getMessage());
}

// Check if the user is logged in, if yes, redirect to the dashboard page
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: user-confirm.html");
    exit;
}

$username_err = $password_err = $login_err = "";

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login'])) {
    // Initialize variables with default values
    $Username = $Password = "";
    
    // Check if username is empty
    if (empty(trim($_POST["Username"]))) {
        $username_err = "Please enter a username.";
    } else {
        $Username = filter_var(trim($_POST["Username"]),);
    }
    
    // Check if password is empty
    if (empty(trim($_POST["Password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $Password = filter_var(trim($_POST["Password"]),);
    }

    // Validate credentials
    if (empty($username_err) && empty($password_err)) {
        $stmt = $connection->prepare('SELECT id_business, Username, Password FROM business_records WHERE Username = ?');
        $stmt->bind_param('s', $Username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id_user, $Username, $passwordHash);
            $stmt->fetch();

            if (password_verify($Password, $passwordHash)) {
                // Password is correct, so start a new session
                session_start();

                // Store data in session variables
                $_SESSION["loggedin"] = true;
                $_SESSION["id_user"] = $id_user;
                $_SESSION["Username"] = $Username;

                // Redirect user to the welcome page
                header("location: user-confirm.html");
                exit();
            } else {
                $login_err = "Invalid username or password.";
            }
        } else {
            $login_err = "Invalid username or password.";
        }
    } else {
        echo "Oops! Something went wrong. Please try again later.";
    }
}

mysqli_close($connection);

function resetPassword($email, $newPassword, $connection)
{
    try {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $sql = "UPDATE users SET password = ? WHERE email = ?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("ss", $hashedPassword, $email);

        if ($stmt->execute()) {
            return "Password reset successful!";
        } else {
            return "Error updating password: " . $stmt->error;
        }

    } catch (Exception $e) {
        return "Error: " . $e->getMessage();
    }
}


// Check if 'Username' is set in the session before using it.
$Username = isset($_SESSION['Username']) ? $_SESSION['Username'] : "";
?>


<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="http://localhost/WEB/assets/img/apple-icon.png">
  <link rel="icon" type="image/png" href="http://localhost/WEB/newlogo.png">
  <title>
    Sales Pilot - Login
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
          <div class="col-lg-4 col-md-8 col-12 mx-auto">
            <div class="card z-index-0 fadeIn3 fadeInBottom">
              <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-primary shadow-primary border-radius-lg py-3 pe-1">
                  <h4 class="text-white font-weight-bolder text-center mt-2 mb-0">Sign in</h4>
                  <div class="row mt-3">
                    <div class="col-2 text-center ms-auto">
                      <a class="btn btn-link px-3" href="javascript:;">
                        <i class="fa fa-facebook text-white text-lg"></i>
                      </a>
                    </div>
                    <div class="col-2 text-center me-auto">
                      <a class="btn btn-link px-3" href="javascript:;">
                        <i class="fa fa-google text-white text-lg"></i>
                      </a>
                    </div>
                  </div>
                </div>
              </div>
              <div class="card-body">
              <form action="http://localhost/WEB/loginpage.php" method="post" role="form" class="text-start">
                      <div class="input-group input-group-outline my-3">
                          <input type="text" class="form-control" id="Username" name="Username" placeholder="Enter your username">
                      </div>
                      <div class="input-group input-group-outline mb-3">
                          <input type="password" class="form-control" id="Password" name="Password" placeholder="Enter your password">
                      </div>
                      <div class="form-check form-switch d-flex align-items-center mb-3">
                          <input class="form-check-input" type="checkbox" id="rememberMe" checked>
                          <label class="form-check-label mb-0 ms-3" for="rememberMe">Remember me</label>
                      </div>
                      <div class="text-center">
                          <button type="submit" name="login" value="Login" id="loginButton" class="btn bg-gradient-primary w-100 my-4 mb-2">Sign in</button>
                      </div>
                      <p class="mt-4 text-sm text-center">
                          Forgot your password?
                          <a href="http://localhost/WEB/reset-password.php" class="text-primary text-gradient font-weight-bold">Reset Password</a>
                      </p>
                      <p class="mt-4 text-sm text-center">
                          Don't have an account?
                          <a href="http://localhost/WEB/sign-up.html" class="text-primary text-gradient font-weight-bold">Sign up</a>
                      </p>
                  </form>
              </div>
            </div>
          </div>
        </div>
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