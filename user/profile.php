<?php
session_start();
require_once('../config/database.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header('Location: ../views/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fixed Handle Auto Profile Upt
$query = "
    SELECT u.*, up.* 
    FROM users u 
    LEFT JOIN user_profiles up ON u.user_id = up.user_id 
    WHERE u.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = htmlspecialchars($_POST['first_name'], ENT_QUOTES, 'UTF-8');
    $last_name = htmlspecialchars($_POST['last_name'], ENT_QUOTES, 'UTF-8');
    $phone = htmlspecialchars($_POST['phone'], ENT_QUOTES, 'UTF-8');
    $address = htmlspecialchars($_POST['address'], ENT_QUOTES, 'UTF-8');
    $city = htmlspecialchars($_POST['city'], ENT_QUOTES, 'UTF-8');
    $country = htmlspecialchars($_POST['country'], ENT_QUOTES, 'UTF-8');
    $bio = htmlspecialchars($_POST['bio'], ENT_QUOTES, 'UTF-8');

    $conn->begin_transaction();

    try {
        $update_query = "
            UPDATE user_profiles 
            SET first_name = ?, last_name = ?, phone_number = ?, 
                address = ?, city = ?, country = ?, bio = ? 
            WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("sssssssi", 
            $first_name, $last_name, $phone, 
            $address, $city, $country, $bio, 
            $user_id
        );
        $update_stmt->execute();

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === 0) {
            $avatar_path = handleAvatarUpload($_FILES['avatar']);
            if ($avatar_path) {
                $avatar_query = "UPDATE user_profiles SET avatar_url = ? WHERE user_id = ?";
                $avatar_stmt = $conn->prepare($avatar_query);
                $avatar_stmt->bind_param("si", $avatar_path, $user_id);
                $avatar_stmt->execute();
            }
        }

        $conn->commit();
        $success_message = "Profile updated successfully!";
        
        // Refresh user data
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = "Failed to update profile. Please try again.";
    }
}

function handleAvatarUpload($file) {
    $upload_dir = "../uploads/avatars/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png'];

    if (!in_array($file_extension, $allowed_types)) {
        return false;
    }

    $new_filename = uniqid() . '.' . $file_extension;
    $upload_path = $upload_dir . $new_filename;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return '../uploads/avatars/' . $new_filename;
    }

    return false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image" href="../uploads/logo.png">
    <title>My Profile - petCare</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body {
            background-color: #f0f2ff;
            min-height: 100vh;
            background-image: radial-gradient(circle at 10% 20%, rgb(239, 246, 255) 0%, rgb(219, 228, 255) 100%);
        }

        .dashboard {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }

        .sidebar {
            background: white;
            padding: 2rem;
            border-right: 1px solid #e5e7eb;
            box-shadow: 4px 0 6px rgba(0, 0, 0, 0.05);
        }

        .main-content {
            padding: 2rem;
            overflow-y: auto;
        }

        .profile-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .avatar-section {
            position: relative;
        }

        .avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #8B5CF6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            overflow: hidden;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            background: white;
            border-radius: 50%;
            padding: 0.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            cursor: pointer;
        }

        .profile-form {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            color: #4B5563;
            font-weight: 500;
        }

        input[type="text"],
        input[type="email"],
        input[type="tel"],
        textarea {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.2s;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: #8B5CF6;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary {
            background: #8B5CF6;
            color: white;
        }

        .btn-primary:hover {
            background: #7C3AED;
            transform: translateY(-1px);
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #D1FAE5;
            color: #059669;
        }

        .alert-error {
            background: #FEE2E2;
            color: #DC2626;
        }

        .nav-link {
            display: flex;
            padding: 0.75rem 1rem;
            color: #4B5563;
            text-decoration: none;
            border-radius: 5px;
            margin-bottom: 0.5rem;
            transition: all 0.2s;
            align-items: center;
            gap: 0.75rem;
        }

        .nav-link:hover {
            background: #F3F4F6;
            color: #8B5CF6;
            transform: translateX(5px);
        }

        .nav-link i {
            width: 20px;
            text-align: center;
        }
    </style>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            let script = document.createElement("script");
            script.src = "https://cdn.jsdelivr.net/gh/ejjays/mvj/script.js";
            document.body.appendChild(script);
        });
    </script>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h2 style="margin-bottom: 2rem; color: #8B5CF6;">
                <i class="fas fa-paw"></i> PetCare
            </h2>
            <nav>
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-home"></i> Dashboard
                </a>
                <a href="my-pets.php" class="nav-link">
                    <i class="fas fa-dog"></i> My Pets
                </a>
                <a href="consultations.php" class="nav-link">
                    <i class="fas fa-stethoscope"></i> Consultations
                </a>
                <a href="vaccinations.php" class="nav-link">
                    <i class="fas fa-syringe"></i> Vaccinations
                </a>
                <a href="food-guides.php" class="nav-link">
                    <i class="fas fa-bone"></i> Food Guides
                </a>
                <a href="care-guides.php" class="nav-link">
                    <i class="fas fa-heart"></i> Care Guides
                </a>
                <a href="training-tips.php" class="nav-link">
                    <i class="fas fa-graduation-cap"></i> Training Tips
                </a>
                <a href="community.php" class="nav-link">
                    <i class="fas fa-users"></i> Community
                </a>
                <a href="profile.php" class="nav-link">
                    <i class="fas fa-user"></i> My Profile
                </a>
                <a href="../views/logout.php" class="nav-link" style="color: #DC2626;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </nav>
        </div>

        <div class="main-content">
            <div class="profile-header">
                <div class="avatar-section">
                    <div class="avatar">
                        <?php if ($user['avatar_url']): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Profile Avatar">
                        <?php else: ?>
                            <?php echo strtoupper(substr($user['first_name'], 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <label for="avatar-upload" class="avatar-upload">
                        <i class="fas fa-camera"></i>
                    </label>
                </div>
                <div>
                    <h1><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h1>
                    <p style="color: #6B7280;"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p style="color: #6B7280;">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                </div>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <div class="profile-form">
                <form method="POST" enctype="multipart/form-data">
                    <input type="file" id="avatar-upload" name="avatar" style="display: none;" accept="image/*">
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                        </div>

                        <div class="form-group">
                            <label for="country">Country</label>
                            <input type="text" id="country" name="country" value="<?php echo htmlspecialchars($user['country'] ?? ''); ?>">
                        </div>

                        <div class="form-group full-width">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                        </div>

                        <div class="form-group full-width">
                            <label for="bio">Bio</label>
                            <textarea id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('avatar-upload').addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const avatar = document.querySelector('.avatar');
                    avatar.innerHTML = `<img src="${e.target.result}" alt="Profile Avatar">`;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    </script>
    <script src="../user/chatbot/chatbbot-init.js"></script>
  <script src="../user/chatbot/dynamicChatLoader.js"></script>
</body>
</html>

