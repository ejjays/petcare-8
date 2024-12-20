<?php
session_start();
require_once('../config/database.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
    $query = "SELECT user_id, email, password_hash, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        if (password_verify($password, $row['password_hash'])) {
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['role'] = $row['role'];
            
            $updateQuery = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE user_id = ?";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bind_param("i", $row['user_id']);
            $updateStmt->execute();
            
            switch ($row['role']) {
                case 'admin':
                    header('Location: ../admin/dashboard.php');
                    break;
                case 'user':
                    header('Location: ../user/dashboard.php');
                    break;
            }
            exit();
        } else {
            $error_message = "Invalid password";
        }
    } else {
        $error_message = "Email not found";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <meta name="google-signin-client_id" content="45592183048-6efcgc1qsog3ms8tn82bmti3jaj948g9.apps.googleusercontent.com">
    <link rel="icon" type="image" href="../uploads/logo.png">
    <title>Pet Care Login</title>
</head>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
    min-height: 100vh;
    background-image: url('../uploads/background.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-attachment: fixed;
    display: flex;
    justify-content: center;
    align-items: center;
}

.container {
    max-width: 400px;
    width: 90%;
    background: rgba(255, 255, 255, 0.95);
    padding: 2rem;
    border-radius: 20px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    position: relative;
    backdrop-filter: blur(8px);
}

        .paw-print {
            width: 60px;
            height: 60px;
            background-color: #8B5CF6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: -50px auto 20px;
        }

        .paw-print::before {
            content: "🐾";
            font-size: 2rem;
            color: white;
        }

        h1 {
            text-align: center;
            color: #1F2937;
            margin-bottom: 1.5rem;
            font-size: 2rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #E5E7EB;
            border-radius: 10px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        input:focus {
            outline: none;
            border-color: #8B5CF6;
        }

        .btn {
            width: 100%;
            padding: 0.75rem;
            background-color: #F59E0B;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.2s;
        }

        .btn:hover {
            background-color: #D97706;
        }

        .footer {
            text-align: center;
            margin-top: 1rem;
        }

        .footer a {
            color: #8B5CF6;
            text-decoration: none;
        }

        .password-requirements {
            font-size: 0.8rem;
            color: #6B7280;
            margin-top: 0.25rem;
        }

        .pet-decoration {
            position: absolute;
            bottom: -40px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 2rem;
            filter: grayscale(0.5);
        }
        

.google-signin-container {
    margin-top: 1rem;
    width: 100%;
    display: flex;
    justify-content: center;
}

.g_id_signin {
    width: 100%; !important
}

.g_id_signin > div {
    width: 100% !important; !important
    max-width: 100%; !important
}

.google-signin-container {
    margin-top: 1rem;
    width: 100%;
    display: flex;
    justify-content: center;
}

#g_id_signin {
    width: 100%; !important
}

    </style>
</head>
<body>
    <div class="container">
        <div class="paw-print"></div>
        <h1>Welcome!</h1>
        <?php if (isset($error_message)): ?>
    <div style="color: #dc2626; text-align: center; margin-bottom: 1rem;">
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>
<?php if (isset($_SESSION['signup_success'])): ?>
    <script>
        setTimeout(function() {
            const alertMessage = document.querySelector('[style*="color: #059669"]');
            if (alertMessage) {
                alertMessage.style.transition = 'opacity 0.5s';
                alertMessage.style.opacity = '0';
                setTimeout(() => alertMessage.remove(), 500);
            }
        }, 2000);
    </script>
<?php endif; ?>

        <form id="loginForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <input type="email" name="email" placeholder="Email Address" required>
            </div>
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 0.5rem;">
                    <input type="checkbox" style="width: auto">
                    <span>Remember Me</span>
                </label>
            </div>
            <button type="submit" class="btn">Log In</button>
            <div class="footer">
                <p><a href="forgot-password.php">Forgot Password?</a></p>
                <p style="margin-top: 0.5rem;">Don't have an account? <a href="signUP.php">Register</a></p>
            </div>
            </form>
        
<div class="google-signin-container" style="margin-top: 1rem;">
    <div id="g_id_signin"></div>
</div>
    
    
<script>
function handleCredentialResponse(response) {
    console.log("Google response received:", response);
    
    fetch('api/auth/callback/google/google-auth.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            id_token: response.credential
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log("Server response:", data);
        if (data.success) {
            window.location.href = '../user/dashboard.php';
        } else {
            alert('Login failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during login: ' + error.message);
    });
}

function initializeGoogle() {
    if (typeof google !== 'undefined') {
        try {
            google.accounts.id.initialize({
                client_id: "45592183048-6efcgc1qsog3ms8tn82bmti3jaj948g9.apps.googleusercontent.com",
                callback: handleCredentialResponse,
                auto_select: false, // Don't automatically select the first account
                cancel_on_tap_outside: true 
            });
            
            google.accounts.id.renderButton(
                document.getElementById("g_id_signin"),
                { 
                    theme: "outline", 
                    size: "large", 
                    width: "335",
                    text: "continue_with" 
                }
            );
        } catch (error) {
            console.error('Error initializing Google Sign-In:', error);
        }
    } else {
        setTimeout(initializeGoogle, 100);
    }
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeGoogle);
} else {
    initializeGoogle();
}
</script>
    
</body>
</html>