<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email     = trim($_POST['email']);
    $password  = trim($_POST['password']);
    $age       = intval($_POST['age']);

    // Validate inputs
    if (empty($full_name) || empty($email) || empty($password) || empty($age)) {
        die('Please fill all fields.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die('Invalid email format.');
    }

    if ($age < 1 || $age > 120) {
        die('Invalid age.');
    }

    // Handle file upload
    $profile_pic_path = NULL;
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($_FILES['profile_pic']['tmp_name']);
        $file_size = $_FILES['profile_pic']['size'];

        if (!in_array($file_type, $allowed_types)) {
            die('Invalid file type.');
        }

        if ($file_size > 2 * 1024 * 1024) {  // 2MB limit
            die('File size exceeds limit.');
        }

        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = uniqid() . '_' . basename($_FILES['profile_pic']['name']);
        $profile_pic_path = $upload_dir . $file_name;

        move_uploaded_file($_FILES['profile_pic']['tmp_name'], $profile_pic_path);
    }

    // Hash password securely using bcrypt
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Database connection
    $mysqli = new mysqli('localhost', 'root', '', 'user_registration');

    if ($mysqli->connect_error) {
        die('Database connection failed: ' . $mysqli->connect_error);
    }

    // Check if email already exists
    $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        die('Email already registered.');
    }
    $stmt->close();

    // Insert user data
    $stmt = $mysqli->prepare("INSERT INTO users (full_name, email, password, age, profile_pic) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sssds', $full_name, $email, $password_hash, $age, $profile_pic_path);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "User registered successfully!";
    } else {
        echo "Error during registration.";
    }

    $stmt->close();
    $mysqli->close();
} else {
    echo "Invalid request.";
}
?>
