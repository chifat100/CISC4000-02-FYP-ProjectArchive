<?php
require_once 'functions.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => 'Invalid request'];

    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'register':
                $response = $auth->register(
                    $_POST['name'] ?? '',
                    $_POST['email'] ?? '',
                    $_POST['password'] ?? '',
                    $_POST['confirm_password'] ?? '',
                    $_POST['profile_type'] ?? ''
                );
                break;

            case 'login':
                $response = $auth->login(
                    $_POST['email'] ?? '',
                    $_POST['password'] ?? ''
                );
                break;

            case 'logout':
                $response = $auth->logout();
                break;
        }
    }

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>