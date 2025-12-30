<?php
// home.php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require 'db.php';

// Reload user
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit;
}

// Get notes with course info and ratings
$stmt = $pdo->query("
    SELECT 
        n.*, 
        u.fullName AS uploaderName, 
        c.code AS courseCode, 
        c.name AS courseName, 
        c.id AS courseId,
        COALESCE(AVG(r.rating), 0) as avg_rating,
        COUNT(r.id) as total_votes,
        (SELECT rating FROM ratings WHERE note_id = n.id AND user_id = " . $_SESSION['user_id'] . ") as user_rating
    FROM notes n 
    JOIN users u ON n.uploaderId = u.id 
    JOIN courses c ON n.course_id = c.id 
    LEFT JOIN ratings r ON n.id = r.note_id
    GROUP BY n.id
    ORDER BY c.code, n.id DESC
");
$allNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by course
$notesByCourse = [];
foreach ($allNotes as $note) {
    $courseKey = $note['courseCode'] . ' - ' . $note['courseName'];
    $notesByCourse[$courseKey][] = $note;
}

// Get note counts per course
$stmt = $pdo->query("
    SELECT c.code, c.name, COUNT(n.id) as count 
    FROM courses c 
    LEFT JOIN notes n ON c.id = n.course_id 
    GROUP BY c.id
");
$noteCounts = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $courseKey = $row['code'] . ' - ' . $row['name'];
    $noteCounts[$courseKey] = $row['count'];
}

// Get courses data
$stmt = $pdo->query("SELECT id, code, name, level, semester, programs FROM courses");
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle rating submission via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'rate_note') {
    $note_id = intval($_POST['note_id']);
    $rating = intval($_POST['rating']);
    $user_id = $_SESSION['user_id'];
    
    // Validate rating
    if ($rating >= 1 && $rating <= 5) {
        // Check if user already rated this note
        $check_stmt = $pdo->prepare("SELECT id FROM ratings WHERE note_id = ? AND user_id = ?");
        $check_stmt->execute([$note_id, $user_id]);
        
        if ($check_stmt->rowCount() > 0) {
            // Update existing rating
            $update_stmt = $pdo->prepare("UPDATE ratings SET rating = ? WHERE note_id = ? AND user_id = ?");
            $update_stmt->execute([$rating, $note_id, $user_id]);
        } else {
            // Insert new rating
            $insert_stmt = $pdo->prepare("INSERT INTO ratings (note_id, user_id, rating) VALUES (?, ?, ?)");
            $insert_stmt->execute([$note_id, $user_id, $rating]);
        }
        
        // Get updated average
        $avg_stmt = $pdo->prepare("SELECT COALESCE(AVG(rating), 0) as avg_rating, COUNT(*) as total_votes FROM ratings WHERE note_id = ?");
        $avg_stmt->execute([$note_id]);
        $result = $avg_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'avg_rating' => round($result['avg_rating'], 1),
            'total_votes' => $result['total_votes'],
            'user_rating' => $rating
        ]);
        exit;
    }
    echo json_encode(['success' => false]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-JUST Notes - Home</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="home.css">
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
        <header class="header-banner">
            <h1>CSIT Knowledge Base</h1>
            <p>Hello <?php echo htmlspecialchars($user['fullName']); ?>, welcome to your academic resource portal.</p>
        </header>

        <div class="float-container">
            <aside class="sidebar-float">
                <div class="sidebar-card">
                    <h3>Program</h3>
                    <div id="program-filters"></div>

                    <h3 style="margin-top: 2rem;">Academic Level</h3>
                    <div>
                        <button class="level-btn <?php echo $user['level'] == 1 ? 'active' : ''; ?>" data-level="1">1</button>
                        <button class="level-btn <?php echo $user['level'] == 2 ? 'active' : ''; ?>" data-level="2">2</button>
                        <button class="level-btn <?php echo $user['level'] == 3 ? 'active' : ''; ?>" data-level="3">3</button>
                        <button class="level-btn <?php echo $user['level'] == 4 ? 'active' : ''; ?>" data-level="4">4</button>
                    </div>

                    <h3 style="margin-top: 2rem;">Semester</h3>
                    <div class="semester-toggle">
                        <button class="semester-btn active" data-semester="Fall">Fall</button>
                        <button class="semester-btn" data-semester="Spring">Spring</button>
                    </div>
                </div>
            </aside>

            <div class="content-float">
                <div class="courses-header">
                    <h2 class="courses-title" id="courses-title"><?php echo $user['program']; ?> • L<?php echo $user['level']; ?></h2>
                    <span class="courses-count" id="courses-count">0 Courses</span>
                </div>

                <div class="courses-grid" id="courses-grid"></div>

                <div id="course-notes-container"></div>
            </div>
        </div>
    </div>

    <script>
        // Pass PHP data to JavaScript
        const phpData = {
            currentUser: {
                id: <?php echo $_SESSION['user_id']; ?>,
                fullName: "<?php echo addslashes($user['fullName']); ?>",
                level: <?php echo $user['level']; ?>,
                program: "<?php echo $user['program']; ?>",
                profilePicture: "<?php echo htmlspecialchars($user['profilePicture']); ?>"
            },
            noteCounts: <?php echo json_encode($noteCounts); ?>,
            notesByCourse: <?php echo json_encode($notesByCourse); ?>,
            courses: <?php echo json_encode($courses); ?>
        };
    </script>
    <script src="home.js"></script>
</body>
</html>