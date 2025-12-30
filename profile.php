<?php
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

// Handle note deletion via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_note') {
    $note_id = intval($_POST['note_id']);
    $user_id = $_SESSION['user_id'];
    
    // Verify the note belongs to the user
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ? AND uploaderId = ?");
    $stmt->execute([$note_id, $user_id]);
    $note = $stmt->fetch();
    
    if ($note) {
        // Delete the note from database
        $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ?");
        $stmt->execute([$note_id]);
        
        // Also delete associated ratings
        $stmt = $pdo->prepare("DELETE FROM ratings WHERE note_id = ?");
        $stmt->execute([$note_id]);
        
        // Delete the file from server
        if (file_exists($note['filePath'])) {
            unlink($note['filePath']);
        }
        
        echo json_encode(['success' => true, 'message' => 'Note deleted successfully']);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Note not found or permission denied']);
        exit;
    }
}

// Count notes shared
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notes WHERE uploaderId = ?");
$stmt->execute([$user['id']]);
$notesShared = $stmt->fetchColumn();

// Extract student ID from email
$email = $user['email'];
preg_match('/\.(\d+)@/', $email, $matches);
$studentId = $matches[1] ?? 'N/A';
$enrollmentYear = substr($studentId, 0, 4) ?? 'N/A';

// Fetch all notes uploaded by this user
$stmt = $pdo->prepare("SELECT * FROM notes WHERE uploaderId = ? ORDER BY id DESC");
$stmt->execute([$user['id']]);
$userNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-JUST Notes - Profile</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="profile.css">
    <style>
        .note-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .delete-note-btn {
            background: #fee2e2;
            color: #dc2626;
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s;
        }

        .delete-note-btn:hover {
            background: #fecaca;
            transform: scale(1.1);
        }

        .note-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
        }

        .delete-confirmation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }

        .delete-modal {
            background: white;
            padding: 2rem;
            border-radius: 1.5rem;
            max-width: 400px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }

        .delete-modal h3 {
            color: #dc2626;
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .delete-modal p {
            color: #4b5563;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .cancel-btn {
            background: #e5e7eb;
            color: #374151;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 0.75rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .confirm-delete-btn {
            background: #dc2626;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 0.75rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .cancel-btn:hover {
            background: #d1d5db;
        }

        .confirm-delete-btn:hover {
            background: #b91c1c;
        }

        .note-meta {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 0.5rem;
        }

        .delete-loading {
            display: none;
            color: #dc2626;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .user-note-card {
            position: relative;
        }

        .file-size {
            font-size: 0.8rem;
            color: #94a3b8;
            margin-left: 0.5rem;
        }
    </style>
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
        <div class="profile-card">
            <img src="<?php echo htmlspecialchars($user['profilePicture']); ?>" alt="Profile" class="profile-avatar">
            <h1 class="profile-name"><?php echo htmlspecialchars($user['fullName']); ?></h1>
            <p class="profile-level"><?php echo htmlspecialchars($user['program']); ?> - Level <?php echo $user['level']; ?></p>

            <div class="stats">
                <div class="stat-item">
                    <div class="stat-label">Enrollment</div>
                    <div class="stat-value"><?php echo htmlspecialchars($enrollmentYear); ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Notes Shared</div>
                    <div class="stat-value" id="notes-count"><?php echo $notesShared; ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">ID Code</div>
                    <div class="stat-value"><?php echo htmlspecialchars($studentId); ?></div>
                </div>
            </div>
        </div>

        <h2 class="section-title">Your Uploaded Notes</h2>

        <?php if ($notesShared == 0): ?>
            <div class="empty-state">
                <p>You haven't shared any notes yet.</p>
            </div>
        <?php else: ?>
            <div class="notes-grid" id="notes-grid">
                <?php foreach ($userNotes as $note): ?>
                    <?php 
                    $extension = strtolower(pathinfo($note['fileName'], PATHINFO_EXTENSION));
                    // Get file size
                    $fileSize = file_exists($note['filePath']) ? filesize($note['filePath']) : 0;
                    $fileSizeFormatted = $fileSize > 0 ? formatFileSize($fileSize) : 'Unknown size';
                    ?>
                    <div class="user-note-card" id="note-<?php echo $note['id']; ?>">
                        <div class="note-header">
                            <div class="note-course"><?php echo htmlspecialchars($note['courseCode']); ?></div>
                            <button class="delete-note-btn" onclick="showDeleteConfirmation(<?php echo $note['id']; ?>, '<?php echo htmlspecialchars(addslashes($note['title'])); ?>')" title="Delete this note">
                                🗑️
                            </button>
                        </div>
                        <h3 class="note-title"><?php echo htmlspecialchars($note['title']); ?></h3>
                        <div class="note-meta">
                            Uploaded: <?php echo date('M d, Y', strtotime($note['uploadDate'])); ?>
                        </div>
                        <?php if (!empty($note['description'])): ?>
                            <p class="note-description"><?php echo nl2br(htmlspecialchars($note['description'])); ?></p>
                        <?php endif; ?>
                        <div class="note-actions">
                            <a href="<?php echo htmlspecialchars($note['filePath']); ?>" download class="download-btn">
                                📥 Download <?php echo htmlspecialchars($note['fileName']); ?>
                                <span class="file-size">(<?php echo $fileSizeFormatted; ?>)</span>
                            </a>
                        </div>

                        <?php if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                            <img src="<?php echo htmlspecialchars($note['filePath']); ?>" class="preview-img" alt="Preview">
                        <?php elseif ($extension === 'pdf'): ?>
                            <iframe src="<?php echo htmlspecialchars($note['filePath']); ?>" class="preview-pdf"></iframe>
                        <?php endif; ?>
                        
                        <div class="delete-loading" id="loading-<?php echo $note['id']; ?>">Deleting...</div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="delete-confirmation" id="deleteConfirmation">
        <div class="delete-modal">
            <h3>🗑️ Delete Note</h3>
            <p id="deleteMessage">Are you sure you want to delete this note? This action cannot be undone.</p>
            <div class="modal-buttons">
                <button class="cancel-btn" onclick="cancelDelete()">Cancel</button>
                <button class="confirm-delete-btn" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>

    <script>
        let noteToDelete = null;
        let noteTitleToDelete = '';

        function showDeleteConfirmation(noteId, noteTitle) {
            noteToDelete = noteId;
            noteTitleToDelete = noteTitle;
            
            document.getElementById('deleteMessage').textContent = 
                `Are you sure you want to delete "${noteTitle}"? This action cannot be undone.`;
            document.getElementById('deleteConfirmation').style.display = 'flex';
        }

        function cancelDelete() {
            noteToDelete = null;
            noteTitleToDelete = '';
            document.getElementById('deleteConfirmation').style.display = 'none';
        }

        async function confirmDelete() {
            if (!noteToDelete) return;
            
            const loadingElement = document.getElementById(`loading-${noteToDelete}`);
            const deleteButton = document.querySelector(`#note-${noteToDelete} .delete-note-btn`);
            
            // Show loading
            if (loadingElement) loadingElement.style.display = 'block';
            if (deleteButton) deleteButton.disabled = true;
            
            try {
                const response = await fetch('profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_note&note_id=${noteToDelete}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Remove the note card from DOM
                    const noteElement = document.getElementById(`note-${noteToDelete}`);
                    if (noteElement) {
                        noteElement.style.opacity = '0.5';
                        noteElement.style.transform = 'scale(0.95)';
                        
                        setTimeout(() => {
                            noteElement.remove();
                            
                            // Update notes count
                            const notesCountElement = document.getElementById('notes-count');
                            if (notesCountElement) {
                                const currentCount = parseInt(notesCountElement.textContent);
                                notesCountElement.textContent = currentCount - 1;
                            }
                            
                            // Show success message
                            showMessage('Note deleted successfully!', 'success');
                            
                            // If no notes left, show empty state
                            const notesGrid = document.getElementById('notes-grid');
                            if (notesGrid && notesGrid.children.length === 0) {
                                notesGrid.innerHTML = `
                                    <div class="empty-state" style="grid-column: 1 / -1;">
                                        <p>You haven't shared any notes yet.</p>
                                    </div>
                                `;
                            }
                        }, 300);
                    }
                } else {
                    showMessage(result.message || 'Failed to delete note', 'error');
                }
            } catch (error) {
                showMessage('Failed to delete note. Please try again.', 'error');
                console.error('Delete error:', error);
            } finally {
                // Hide loading and modal
                if (loadingElement) loadingElement.style.display = 'none';
                if (deleteButton) deleteButton.disabled = false;
                cancelDelete();
            }
        }

        function showMessage(message, type) {
            // Create message element
            const messageDiv = document.createElement('div');
            messageDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                border-radius: 0.75rem;
                color: white;
                font-weight: 600;
                z-index: 1001;
                animation: slideIn 0.3s ease;
            `;
            
            if (type === 'success') {
                messageDiv.style.background = '#10b981';
            } else {
                messageDiv.style.background = '#dc2626';
            }
            
            messageDiv.textContent = message;
            document.body.appendChild(messageDiv);
            
            // Remove after 3 seconds
            setTimeout(() => {
                messageDiv.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => messageDiv.remove(), 300);
            }, 3000);
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>

<?php
// Helper function to format file size
function formatFileSize($bytes) {
    if ($bytes == 0) return '0 Bytes';
    $k = 1024;
    $sizes = ['Bytes', 'KB', 'MB', 'GB'];
    $i = floor(log($bytes) / log($k));
    return round($bytes / pow($k, $i), 2) . ' ' . $sizes[$i];
}
?>