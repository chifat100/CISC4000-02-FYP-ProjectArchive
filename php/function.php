<?php
require_once 'config.php';

class Auth {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Function to sanitize input data
    private function sanitizeInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $this->conn->real_escape_string($data);
    }

    // Function to validate email
    private function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    // Function to check if email exists
    private function emailExists($email) {
        $stmt = $this->conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    // Function to register new user
    public function register($name, $email, $password, $confirm_password, $profile_type) {
        // Sanitize inputs
        $name = $this->sanitizeInput($name);
        $email = $this->sanitizeInput($email);
        $profile_type = $this->sanitizeInput($profile_type);

        // Validate inputs
        if (empty($name) || empty($email) || empty($password) || empty($confirm_password) || empty($profile_type)) {
            return ["success" => false, "message" => "All fields are required"];
        }

        if (!$this->validateEmail($email)) {
            return ["success" => false, "message" => "Invalid email format"];
        }

        if ($password !== $confirm_password) {
            return ["success" => false, "message" => "Passwords do not match"];
        }

        if ($this->emailExists($email)) {
            return ["success" => false, "message" => "Email already exists"];
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT, ["cost" => HASH_COST]);

        // Insert user into database
        $stmt = $this->conn->prepare("INSERT INTO users (name, email, password, profile_type, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $name, $email, $hashed_password, $profile_type);

        if ($stmt->execute()) {
            return ["success" => true, "message" => "Registration successful"];
        } else {
            return ["success" => false, "message" => "Registration failed: " . $this->conn->error];
        }
    }

    // Function to login user
    public function login($email, $password) {
        // Sanitize inputs
        $email = $this->sanitizeInput($email);

        // Validate inputs
        if (empty($email) || empty($password)) {
            return ["success" => false, "message" => "All fields are required"];
        }

        if (!$this->validateEmail($email)) {
            return ["success" => false, "message" => "Invalid email format"];
        }

        // Get user from database
        $stmt = $this->conn->prepare("SELECT id, name, email, password, profile_type FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            if (password_verify($password, $user['password'])) {
                // Start session and store user data
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['profile_type'] = $user['profile_type'];
                
                return ["success" => true, "message" => "Login successful"];
            } else {
                return ["success" => false, "message" => "Invalid password"];
            }
        } else {
            return ["success" => false, "message" => "User not found"];
        }
    }

    // Function to logout user
    public function logout() {
        session_unset();
        session_destroy();
        return ["success" => true, "message" => "Logout successful"];
    }

    // Function to check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    // Function to get user data
    public function getUserData() {
        if ($this->isLoggedIn()) {
            return [
                "id" => $_SESSION['user_id'],
                "name" => $_SESSION['user_name'],
                "email" => $_SESSION['user_email'],
                "profile_type" => $_SESSION['profile_type']
            ];
        }
        return null;
    }
}

// Create database tables if they don't exist
function createTables($conn) {
    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            profile_type ENUM('student', 'teacher', 'admin') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )"
    ];
    foreach ($queries as $query) {
        if (!$conn->query($query)) {
            die("Error creating tables: " . $conn->error);
        }
    }
}

// Initialize tables
createTables($conn);

// Create Auth instance
$auth = new Auth($conn);
?>