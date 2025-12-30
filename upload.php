<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require 'db.php';

// Reload user safely
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Get courses for dropdown from courses table
$stmt = $pdo->prepare("
    SELECT id, code, name, level 
    FROM courses 
    WHERE FIND_IN_SET(?, programs) > 0 AND level = ?
    ORDER BY code
");
$stmt->execute([$user['program'], $user['level']]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle upload
$message = '';
$messageType = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $course_id = $_POST['course_id']; // Now using course_id instead of courseCode
    $description = trim($_POST['description']);
    $file = $_FILES['file'];

    if (empty($title) || empty($course_id) || $file['error'] !== UPLOAD_ERR_OK) {
        $message = "Please fill all fields and select a valid file";
        $messageType = 'error';
    } else {
        $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
        if (!in_array($file['type'], $allowedTypes)) {
            $message = "Only PDF, JPG, PNG files allowed";
            $messageType = 'error';
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $message = "File too large (max 10MB)";
            $messageType = 'error';
        } else {
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $fileName = time() . '_' . basename($file['name']);
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Get course info for display
                $stmt = $pdo->prepare("SELECT code, name FROM courses WHERE id = ?");
                $stmt->execute([$course_id]);
                $course = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $stmt = $pdo->prepare("INSERT INTO notes (title, course_id, description, fileName, filePath, uploaderId, uploaderName, uploadDate) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$title, $course_id, $description, $fileName, $filePath, $user['id'], $user['fullName']]);
                
                $message = "Resource shared successfully for {$course['code']} - {$course['name']}! 🎉";
                $messageType = 'success';
                
                // Clear form on success
                $_POST = array();
            } else {
                $message = "Failed to upload file";
                $messageType = 'error';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-JUST Notes - Upload</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="upload.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <div class="logo">📘 <span>E-JUST Notes</span></div>
            <div class="nav-links">
                <a href="home.php">Home</a>
                <a href="upload.php">Upload Note</a>
                <a href="profile.php">Profile</a>
            </div>
            <div class="user-info">
                <img src="<?php echo htmlspecialchars($user['profilePicture']); ?>" alt="User">
                <span>Level <?php echo $user['level']; ?> - <?php echo htmlspecialchars($user['fullName']); ?></span>
                <a href="logout.php" class="logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="main">
        <div class="upload-card">
            <div class="upload-header">
                <div>
                    <h2 class="upload-title">Share Resource</h2>
                    <p class="upload-subtitle">Contribute to the <?php echo $user['program']; ?> library</p>
                </div>
                <div class="icon-box">📝</div>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo $messageType; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data">
                <div>
                    <label>Title</label>
                    <input type="text" name="title" placeholder="e.g., Data Structures - Final Revision" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                </div>

                <div class="grid-3">
                    <div>
                        <label>Course</label>
                        <select name="course_id" required>
                            <option value="">Choose Course</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?php echo $course['id']; ?>" <?php echo ($_POST['course_id'] ?? '') == $course['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($course['code'] . ' - ' . $course['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Program</label>
                        <div class="program-box"><?php echo $user['program']; ?></div>
                    </div>
                    <div>
                        <label>Level</label>
                        <div class="level-box">Level <?php echo $user['level']; ?></div>
                    </div>
                </div>

                <div>
                    <label>Description</label>
                    <textarea name="description" placeholder="What makes this note special?"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div>
                    <label>File Attachment</label>
                    <div class="file-zone">
                        <span class="text-5xl">📁</span>
                        <p>Click to select file</p>
                        <p>PDF, JPG, PNG (Max 10MB)</p>
                        <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                </div>

                <!-- SUBMIT BUTTON IS HERE IN THE BTN-GROUP -->
                <div class="btn-group">
                    <button type="button" class="btn-assistant" onclick="alert('Make sure to select the exact course name from the dropdown!')">Format Help</button>
                    <button type="submit" class="btn-upload">Share Resource</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>