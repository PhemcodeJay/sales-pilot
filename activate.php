<?php
include('config.php');

// First, check if the email and code exist in the query parameters.
if (isset($_GET['Email'], $_GET['code'])) {
    $email = $_GET['Email']; // Use consistent variable name
    $code = $_GET['code'];   // Use consistent variable name
    
    // Prepare and execute a SELECT query to check if the account exists with the given email and code.
    if ($stmt = $con->prepare('SELECT * FROM sales_pilot.business_records WHERE Email = ? AND activation_code = ?')) {
        $stmt->bind_param('ss', $Email, $code); // Use the variables here
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            // Account exists with the requested email and code.
            // Prepare an UPDATE query to activate the account.
            if ($stmt = $con->prepare('UPDATE sales_pilot.business_records SET activation_code = ? WHERE Email = ? AND activation_code = ?')) {
                $newcode = 'activated';
                $stmt->bind_param('sss', $newcode, $Email, $code); // Use the variables here
                $stmt->execute();
                echo 'Your account is now activated! You can now <a href="profile.php">login</a>!';
            }
        } else {
            echo 'The account is already activated or doesn\'t exist!';
        }
    } else {
        echo 'Database query error.';
    }
    
    // Check activation status and display content accordingly.
    $query = "SELECT activation_code FROM sales_pilot.business_records WHERE Email = ?";
    if ($stmt = $con->prepare($query)) {
        $stmt->bind_param('s', $Email);
        $stmt->execute();
        $stmt->bind_result($activationStatus);
        $stmt->fetch();
        
        if ($activationStatus == 'activated') {
            echo 'user-confirm.html.';
        }
    }
} else {
    echo 'Invalid activation link.';
}
?>
