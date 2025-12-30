<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: home.php');
    exit;
}

require 'db.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $action = $_POST['action'] ?? 'login';

    // Fixed: Use preg_match instead of direct regex
    if (!preg_match('/^[a-z]+\.3(20\d{2})\d{4}@ejust\.edu\.eg$/i', $email)) {
        $message = 'Email must be valid E-JUST format: name.32024XXXX@ejust.edu.eg';
    } elseif ($action == 'signup') {
        $fullName = trim($_POST['fullName']);
        $program = $_POST['program'];

        if (strlen($fullName) < 3) {
            $message = 'Please enter your full name (minimum 3 characters)';
        } elseif (strlen($password) < 6 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            $message = 'Password must be at least 6 characters and contain letters and numbers.';
        } else {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $message = 'Account already exists.';
            } else {
                // Create user
                preg_match('/\.3(20\d{2})\d{4}/i', $email, $match);
                $enrollmentYear = $match[1] ?? 2025;
                $level = min(max(2025 - $enrollmentYear + 1, 1), 4);
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $avatar = "https://api.dicebear.com/7.x/avataaars/svg?seed=" . urlencode($email);

                $stmt = $pdo->prepare("INSERT INTO users (fullName, email, password, program, level, enrollmentYear, profilePicture) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$fullName, $email, $hashed, $program, $level, $enrollmentYear, $avatar]);
                $userId = $pdo->lastInsertId();

                $newUser = [
                    'id' => $userId,
                    'fullName' => $fullName,
                    'email' => $email,
                    'program' => $program,
                    'level' => $level,
                    'profilePicture' => $avatar
                ];
                $_SESSION['user_id'] = $userId;
                $_SESSION['user'] = $newUser;
                header('Location: home.php');
                exit;
            }
        }
    } else {
        // Login
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user'] = $user;
            header('Location: home.php');
            exit;
        } else {
            $message = 'Invalid credentials. Check your email or sign up.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-JUST Hub</title>
    <link rel="stylesheet" href="style.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #0f172a;
            padding: 1rem;
            font-family: 'Segoe UI', sans-serif;
        }
        .auth-card {
            max-width: 420px;
            width: 100%;
            background: white;
            padding: 2.5rem;
            border-radius: 2.5rem;
            box-shadow: 0 25px 50px rgba(0,0,0,0.4);
            text-align: center;
        }
        .icon {
            font-size: 5rem;
            margin-bottom: 1rem;
        }
        .title {
            font-size: 1.8rem;
            font-weight: 900;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }
        .subtitle {
            color: #64748b;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }
        .tabs {
            display: flex;
            background: #f1f5f9;
            border-radius: 2rem;
            padding: 0.5rem;
            margin-bottom: 2rem;
        }
        .tab {
            flex: 1;
            padding: 0.8rem;
            border: none;
            background: transparent;
            font-weight: bold;
            font-size: 0.9rem;
            border-radius: 1rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        .tab.active {
            background: white;
            color: #2563eb;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        input, select {
            width: 100%;
            padding: 1rem;
            margin: 0.8rem 0;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 1rem;
            font-size: 0.95rem;
            outline: none;
        }
        input:focus, select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59,130,246,0.2);
        }
        .error {
            padding: 1rem;
            background: #fee2e2;
            color: #991b1b;
            font-size: 0.85rem;
            font-weight: bold;
            border-radius: 1rem;
            border: 1px solid #fecaca;
            margin: 1rem 0;
        }
        .submit-btn {
            width: 100%;
            padding: 1.2rem;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 2rem;
            font-size: 0.95rem;
            font-weight: 900;
            cursor: pointer;
            margin-top: 1rem;
            box-shadow: 0 10px 20px rgba(37,99,235,0.3);
            transition: all 0.3s;
        }
        .submit-btn:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }
        .hidden { display: none; }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="icon">🎓</div>
        <h2 class="title">E-JUST Hub</h2>
        <p class="subtitle" id="subtitle">Sign in to access notes</p>

        <div class="tabs">
            <button class="tab active" onclick="switchTab(true)">Sign In</button>
            <button class="tab" onclick="switchTab(false)">Sign Up</button>
        </div>

        <form method="POST">
            <input type="hidden" name="action" id="action" value="login">
            <div id="signup-fields" class="hidden">
                <input type="text" name="fullName" placeholder="Full Name" />
                <select name="program">
                    <option value="CSC">CSC Program</option>
                    <option value="AID">AID Program</option>
                    <option value="CNC">CNC Program</option>
                    <option value="BIF">BIF Program</option>
                </select>
            </div>

            <input type="email" name="email" placeholder="name.32024XXXX@ejust.edu.eg" required />
            <input type="password" name="password" placeholder="Password" required />

            <?php if ($message): ?>
                <div class="error">⚠️ <?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <button type="submit" class="submit-btn" id="submitBtn">Login</button>
        </form>
    </div>

    <script>
        function switchTab(login) {
            document.getElementById('action').value = login ? 'login' : 'signup';
            document.getElementById('signup-fields').classList.toggle('hidden', login);
            document.getElementById('subtitle').textContent = login ? 'Sign in to access notes' : 'Create your student account';
            document.getElementById('submitBtn').textContent = login ? 'Login' : 'Create Account';
        }
    </script>
</body>
</html>