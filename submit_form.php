<?php
// Database connection details
$servername = "localhost";
$username = "root";
$password = "";
$database = "vntravels";

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get form data
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validate form data
$errors = [];

if (empty($name)) {
    $errors[] = "Name is required";
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Valid email is required";
}

if (empty($subject)) {
    $errors[] = "Subject is required";
}

if (empty($message)) {
    $errors[] = "Message is required";
}

// If there are validation errors, return them
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Validation failed',
        'errors' => $errors
    ]);
    exit;
}

// Sanitize inputs
$name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
$subject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

// Insert data into database
$sql = "INSERT INTO contact_messages (name, email, phone, subject, message, submitted_at) 
        VALUES (?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param("sssss", $name, $email, $phone, $subject, $message);

if ($stmt->execute()) {
    // Send confirmation email to user
    $user_email = $email;
    $user_subject = "We received your message - VN Travels";
    
    $user_body = "Dear " . $name . ",\n\n";
    $user_body .= "Thank you for contacting VN Travels.\n";
    $user_body .= "We have received your message and will get back to you as soon as possible.\n\n";
    $user_body .= "Subject: " . $subject . "\n";
    $user_body .= "Message: " . $message . "\n\n";
    $user_body .= "Best regards,\nVN Travels Team";
    
    $headers = "From: info@vntravels.com\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    mail($user_email, $user_subject, $user_body, $headers);
    
    // Send notification email to admin
    $admin_email = "info@vntravels.com";
    $admin_subject = "New Contact Form Submission - VN Travels";
    
    $admin_body = "New message received:\n\n";
    $admin_body .= "Name: " . $name . "\n";
    $admin_body .= "Email: " . $email . "\n";
    $admin_body .= "Phone: " . $phone . "\n";
    $admin_body .= "Subject: " . $subject . "\n";
    $admin_body .= "Message: " . $message . "\n";
    
    mail($admin_email, $admin_subject, $admin_body, $headers);
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Message sent successfully! We will get back to you soon.'
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error saving message: ' . $stmt->error
    ]);
}

$stmt->close();
$conn->close();
?>
