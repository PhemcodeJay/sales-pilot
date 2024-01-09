<?php

session_start();
include('config.php');

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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, "email", FILTER_VALIDATE_EMAIL);
    $newPassword = trim($_POST["new_password"]);

    if ($email && $newPassword) {
        $connection = new mysqli($hostname, $username, $password, $database);

        if ($connection->connect_error) {
            exit("Error connecting to the database: " . $connection->connect_error);
        }

        $resultMessage = resetPassword($email, $newPassword, $connection);

        echo $resultMessage;

        $connection->close();
    } else {
        echo "Invalid email or new password.";
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
  <link rel="icon" type="image/png" href="http://localhost/WEB/assets/img/favicon.png">
  <title>
    Sales Pilot - Reset Password
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
    /* Input */
.text-start .input-group-outline input[type=email]{
 transform:translatex(54px) translatey(-54px);
}

/* Input */
.main-content .page-header .my-auto .row .mx-auto .card .flex-column .card-plain .card-body .text-start .input-group-outline input[type=email]{
 width:250px !important;
}

/* Label */
.text-start > .input-group-outline > label{
 width:20%;
 position:relative;
 top:-16px;
 left:8px;
 font-weight:600;
 font-size:20px;
 transform:translatex(45px) translatey(-30px);
}

/* Input */
.text-start .input-group-outline input[type=password]{
 transform:translatex(26px) translatey(-36px);
 width:246px;
 height:47px;
}

/* Label */
.text-start .input-group-outline .input-group-outline label{
 position:relative;
 top:-25px;
 left:-49px;
 height:36px;
 font-size:20px;
 font-weight:500;
 transform:translatex(72px) translatey(0px);
}

/* Card */
.main-content .page-header .my-auto .row .mx-auto > .card{
 width:200% !important;
}

/* Card */
.page-header .my-auto .row .mx-auto > .card{
 transform:translatex(-184px) translatey(14px);
}

/* Link */
.text-start .text-sm a{
 display:inline-block;
 height:30px;
 transform:translatex(-221px) translatey(18px);
 font-size:20px;
 text-decoration:underline;
}

/* Heading */
.card-plain .card-header h4{
 transform:translatex(0px) translatey(-19px);
}

/* Paragraph */
.card-plain .card-header p{
 padding-bottom:14px;
 transform:translatex(0px) translatey(-27px);
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
          <div class="col-lg-4 col-md-8 col-12 mx-auto">
            <div class="card z-index-0 fadeIn3 fadeInBottom">
              <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-primary shadow-primary border-radius-lg py-3 pe-1">
                  <h4 class="text-white font-weight-bolder text-center mt-2 mb-0">Reset Password</h4>
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
              <div class="col-xl-10 col-lg-5 col-md-7 d-flex flex-column ms-auto me-auto ms-lg-auto me-lg-5">
                <div class="card card-plain">
                  <div class="card-header">
                    <h4 class="font-weight-bolder">Welcome</h4>
                    <p class="mb-0">Enter your details to reset password</p>
                  </div>
                    <div class="card-body">
                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" role="form" class="text-start">
                    
                    <div class="input-group input-group-outline mb-3">
                        <label for="email">Email:</label>
                        <input type="email" name="email" required><br>
                    
                    <div class="input-group input-group-outline mb-3">
                        <label for="new_password">New Password:</label>
                        <input type="password" name="new_password" required><br>
                        <p class="mt-4 text-sm text-center">
                            <a type="submit" value="Reset Password" class="text-primary text-gradient font-weight-bold">Reset Password</a>
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