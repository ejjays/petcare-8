<?php
session_start();
require_once('../config/database.php');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log incoming data
file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Received request\n", FILE_APPEND);

// Get the token from the POST request
$data = json_decode(file_get_contents('php://input'), true);
$id_token = $data['id_token'];

// Log the token
file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Token: " . $id_token . "\n", FILE_APPEND);

require_once '../vendor/autoload.php';

$client = new Google_Client([
    'client_id' => '45592183048-24gcf76rhb00kfbbo1k690g13h6bh54h.apps.googleusercontent.com',
    'client_secret' => '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'
]);

try {
    $payload = $client->verifyIdToken($id_token);
    
    if ($payload) {
        $google_id = $payload['sub'];
        $email = $payload['email'];
        $name = $payload['name'];
        $picture = isset($payload['picture']) ? $payload['picture'] : ''; // Get Google profile picture URL
        
        // Log user data
        file_put_contents('debug.log', date('Y-m-d H:i:s') . " - User data: " . json_encode($payload) . "\n", FILE_APPEND);
        
        // Check if user exists
        $stmt = $conn->prepare("SELECT user_id, role FROM users WHERE google_id = ? OR email = ?");
        $stmt->bind_param("ss", $google_id, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // User exists - update their profile picture and log them in
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
            
            // Update profile picture
            if (!empty($picture)) {
                $updateStmt = $conn->prepare("
                    UPDATE user_profiles 
                    SET avatar_url = ? 
                    WHERE user_id = ?
                ");
                $updateStmt->bind_param("si", $picture, $user['user_id']);
                $updateStmt->execute();
            }
            
            echo json_encode(['success' => true]);
        } else {
            // Create new user
            $conn->begin_transaction();
            try {
                // Insert into users table
                $stmt = $conn->prepare("INSERT INTO users (email, google_id, role, password_hash) VALUES (?, ?, 'user', '')");
                $stmt->bind_param("ss", $email, $google_id);
                $stmt->execute();
                $user_id = $conn->insert_id;
                
                // Split name into first and last name
                $name_parts = explode(' ', $name);
                $first_name = $name_parts[0];
                $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
                
                // Insert into user_profiles table with avatar_url
                $stmt = $conn->prepare("INSERT INTO user_profiles (user_id, first_name, last_name, avatar_url) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("isss", $user_id, $first_name, $last_name, $picture);
                $stmt->execute();
                
                $conn->commit();
                
                $_SESSION['user_id'] = $user_id;
                $_SESSION['role'] = 'user';
                
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $conn->rollback();
                file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
    }
} catch (Exception $e) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>