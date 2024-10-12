<?php
// Database connection settings
$servername = "localhost"; // Update as necessary
$username = "root"; // Update with your database username
$password = ""; // Update with your database password
$dbname = "logistics"; // Update with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to get consignment location and status from AfterShip API
function getConsignmentDetails($trackingCode, $apiKey) {
    $url = "https://api.aftership.com/v4/trackings/" . urlencode($trackingCode); // AfterShip API URL

    // Initialize cURL
    $ch = curl_init($url);
    // Set options
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return response as a string
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "aftership-api-key: $apiKey", // Set the AfterShip API key in the header
        "Content-Type: application/json" // Set the content type to JSON
    ));

    // Execute the request
    $response = curl_exec($ch);

    // Check for cURL errors
    if (curl_errno($ch)) {
        return false; // Return false on error
    }

    // Close the cURL session
    curl_close($ch);

    // Decode and return the JSON response
    return json_decode($response, true); // Assuming the API returns JSON
}

// Check if the tracking code form has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['track'])) {
    // Retrieve and sanitize the tracking code
    $trackingCode = $conn->real_escape_string(trim($_POST['trackingCode']));

    // SQL query to select the consignment details based on the tracking code
    $sql = "SELECT * FROM consignments WHERE trackingCode = '$trackingCode'"; // Ensure 'trackingCode' is the correct column name
    $result = $conn->query($sql);

    // Check if a consignment was found
    if ($result && $result->num_rows > 0) {
        // Output the consignment details
        $consignment = $result->fetch_assoc();
        echo "<h2 style='text-align: center; color: rgb(122, 4, 4);text-transform:uppercase;'>Consignment Details</h2>";

        echo "<div id='consignment-details' style='max-width: 600px; margin: auto; padding: 20px; background-color: #f8f9fa; border-radius: 8px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);'>";
        echo "<p style='text-transform:uppercase;'><strong>Sender Name:</strong> " . htmlspecialchars($consignment['senderName']) . "</p>";
        echo "<p style='text-transform:uppercase;'><strong>Sender Address:</strong> " . htmlspecialchars($consignment['senderAddress']) . "</p>";
        echo "<p style='text-transform:uppercase;'><strong>Receiver Name:</strong> " . htmlspecialchars($consignment['receiverName']) . "</p>";
        echo "<p style='text-transform:uppercase;'><strong>Receiver Address:</strong> " . htmlspecialchars($consignment['receiverAddress']) . "</p>";
        echo "<p style='text-transform:uppercase;'><strong>Package Details:</strong> " . htmlspecialchars($consignment['packageDetails']) . "</p>";
        echo "<p style='text-transform:uppercase;'><strong>Tracking Code:</strong> " . htmlspecialchars($consignment['trackingCode']) . "</p>";
        echo "<p style='text-transform:uppercase;'><strong>Registered On:</strong> " . htmlspecialchars($consignment['created_at']) . "</p>"; // Ensure 'created_at' is in your table

        // Fetch the consignment details from the AfterShip API
        $apiKey = "asat_afdb906471054ff58e2b4a483db7bf58"; // Your AfterShip API key
        $locationData = getConsignmentDetails($trackingCode, $apiKey);

        // Check if location data was fetched successfully
        if ($locationData) {
            // Get the current location and status
            $currentLocation = isset($locationData['data']['tracking']['location']) ? strtoupper($locationData['data']['tracking']['location']) : 'CLICK HERE';
            $trackingStatus = isset($locationData['data']['tracking']['tag']) ? strtoupper($locationData['data']['tracking']['tag']) : 'SHIPPED';            
        
            // Modify the status based on trackingStatus
            if ($trackingStatus == 'DELIVERED') { // Use uppercase 'DELIVERED' for comparison
                $status = 'DELIVERED'; // Keep the delivered status as is
                $statusStyle = "background-color: lightgreen;"; // Background color for delivered status
            } else {
                $status = 'SHIPPED'; // Change to "SHIPPED" for all other statuses
                $statusStyle = "color: Green;";
            }
        
            // Create a Google Maps link for the current location
            if ($currentLocation !== 'N/A') {
                $googleMapsLink = "https://www.google.com/maps/search/?api=1&query=" . urlencode($currentLocation);
                echo "<p id='current-location'><strong>CURRENT LOCATION:</strong> <a href='$googleMapsLink'  style='color: blue; text-decoration: underline;'>" . htmlspecialchars($currentLocation) . "</a></p>";
            } else {
                echo "<p id='current-location'><strong>CURRENT LOCATION:</strong> N/A</p>";
            }
        
            // Display status
            echo "<p id='status' style='$statusStyle padding: 5px; border-radius: 5px;'><strong>STATUS:</strong> " . htmlspecialchars($status) . "</p>"; // Display status with background color
        } else {
            echo "<p style='color: orange;'>Unable to fetch current location and status. Please try again later.</p>";
        }
        
        echo "</div>";
        } else {
            echo "<p style='color: red; text-align: center;'>No consignment found with the provided tracking code.</p>";
        }
        
}

// Handle AJAX request for updated tracking info
if (isset($_GET['tracking_code'])) {
    $trackingCode = $conn->real_escape_string(trim($_GET['tracking_code']));
    $apiKey = "asat_afdb906471054ff58e2b4a483db7bf58"; // Your AfterShip API key
    $locationData = getConsignmentDetails($trackingCode, $apiKey);

    if ($locationData) {
        $currentLocation = isset($locationData['data']['tracking']['location']) ? $locationData['data']['tracking']['location'] : 'N/A';
        $status = isset($locationData['data']['tracking']['tag']) ? $locationData['data']['tracking']['tag'] : 'N/A'; // Assuming 'tag' contains the status
        echo json_encode(array('location' => $currentLocation, 'status' => $status));
    } else {
        echo json_encode(array('location' => 'N/A', 'status' => 'SHIPPED'));
    }
    exit; // Prevent further script execution
}

// Close the database connection
$conn->close();
?>
