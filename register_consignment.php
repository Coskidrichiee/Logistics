<?php
// Database configuration
$servername = "localhost"; // Replace with your server name
$username = "root"; // Replace with your database username
$password = ""; // Replace with your database password
$dbname = "logistics"; // Replace with your database name

// Create a connection to the database
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Function to generate a unique tracking code with prefix
function generateTrackingCode() {
    $code = str_pad(rand(0, 99999999), 8, '0', STR_PAD_LEFT); // Generates an 8-digit tracking code
    return "HJC" . $code; // Add "HJC" prefix
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input data to prevent SQL injection
    $senderName = $conn->real_escape_string(trim($_POST['senderName']));
    $senderAddress = $conn->real_escape_string(trim($_POST['senderAddress']));
    $receiverName = $conn->real_escape_string(trim($_POST['receiverName']));
    $receiverAddress = $conn->real_escape_string(trim($_POST['receiverAddress']));
    $packageDetails = $conn->real_escape_string(trim($_POST['packageDetails']));
    
    // Generate a new tracking code
    $trackingCode = generateTrackingCode();

    // Prepare the SQL statement to insert consignment data into the database
    $sql = "INSERT INTO consignments (senderName, senderAddress, receiverName, receiverAddress, packageDetails, trackingCode, created_at)
            VALUES ('$senderName', '$senderAddress', '$receiverName', '$receiverAddress', '$packageDetails', '$trackingCode', NOW())";

    // Execute the query and check for success
    if ($conn->query($sql) === TRUE) {
        // Output JavaScript to alert and redirect
        echo "<script type='text/javascript'>
                alert('Consignment registered successfully.');
                window.location.href = 'feature.html'; // Redirect to feature.html
              </script>";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error; // Show any SQL error
    }
}

// Close the database connection
$conn->close();
?>

