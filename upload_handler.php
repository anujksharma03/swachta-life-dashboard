<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "swachta_life_db";

    $conn = new mysqli($servername, $username, $password, $dbname);

    $user_id = $_SESSION['user_id'];
    $user_query = "SELECT post_office_id FROM users WHERE user_id = $user_id";
    $result = $conn->query($user_query);
    $user = $result->fetch_assoc();
    $post_office_id = $user['post_office_id'];

    // Upload file
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    $filename = basename($_FILES['image']['name']);
    $target_file = $target_dir . time() . '_' . $filename;

    if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
        // Generate cleanliness score (placeholder)
        $cleanliness_score = rand(50, 95);
        $detected_issues = 'Minor dust observed';
        $waste_classification = 'organic';
        $status = $cleanliness_score >= 80 ? 'clean' : 'needs_attention';

        // Store in database
        $insert_query = "INSERT INTO cleanliness_records \
                        (post_office_id, image_path, cleanliness_score, detected_issues, waste_classification, status) \
                        VALUES ($post_office_id, '$target_file', $cleanliness_score, '$detected_issues', '$waste_classification', '$status')";

        if ($conn->query($insert_query)) {
            // Send alert if needed
            if ($status != 'clean') {
                $alert_query = "INSERT INTO alert_logs \
                               (post_office_id, alert_type, message, severity) \
                               VALUES ($post_office_id, 'Cleanliness Issue', 'Issues detected in uploaded image', 'high')";
                $conn->query($alert_query);
            }

            echo json_encode(['success' => true, 'score' => $cleanliness_score]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Upload failed']);
    }

    $conn->close();
}