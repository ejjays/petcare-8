<?php
session_start();
require_once('../config/database.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);

file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Received request\n", FILE_APPEND);

$data = json_decode(file_get_contents('php://input'), true);
$id_token = $data['id_token'];

file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Token: " . $id_token . "\n", FILE_APPEND);

require_once '../vendor/autoload.php';

$client = new Google_Client([
    'client_id' => '45592183048-6efcgc1qsog3ms8tn82bmti3jaj948g9.apps.googleusercontent.com',
    'client_secret' => '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'
]);

try {
    $payload = $client->verifyIdToken($id_token);
    
    if ($payload) {
        $google_id = $payload['sub'];
        $email = $payload['email'];
        $name = $payload['name'];
        $picture = isset($payload['picture']) ? $payload['picture'] : '';         
        
        file_put_contents('debug.log', date('Y-m-d H:i:s') . " - User data: " . json_encode($payload) . "\n", FILE_APPEND);        
       
        $stmt = $conn->prepare("SELECT user_id, role FROM users WHERE google_id = ? OR email = ?");
        $stmt->bind_param("ss", $google_id, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            
            $user = $result->fetch_assoc();
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = $user['role'];
                     
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
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO users (email, google_id, role, password_hash) VALUES (?, ?, 'user', '')");
                $stmt->bind_param("ss", $email, $google_id);
                $stmt->execute();
                $user_id = $conn->insert_id;
                               
                $name_parts = explode(' ', $name);
                $first_name = $name_parts[0];
                $last_name = isset($name_parts[1]) ? $name_parts[1] : '';
                               
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